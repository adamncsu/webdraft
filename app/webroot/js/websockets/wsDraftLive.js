var sess;
var topic;

$(document).ready(function() {
	log("Connecting...");
	
	var uri = 'ws://'+location.host+':8080';
	var prefix = ['draft', getHost()+'drafts/'];
	topic = "draft:"+draftID;
	
	ab.connect(uri,
		// connection open
		function(session){
			sess = session;
			sess.prefix(prefix[0], prefix[1]);
			
			// set user info
			remoteCall(topic, 'setUserInfo', [username, userID], function(result){
				
				// subscribe to topic
				sess.subscribe(topic, messageReceived);
				
				// load card set
				draftman.loadCardSets(sets, function(){
					
					// check into draft
					remoteCall(topic, 'checkIn', [], function(checkInResult){
				
						// get seat list
						$.getJSON(getHost()+"drafts/seatlist/"+draftID, function(data){
							draftman.seatInfo = [];
							$.each(data, function(key, val){
								if(key.substr(0,4) == 'Seat' && val.id != null)
									draftman.seatInfo.push(parseInt(val.id));
							});
							
							// get previously drafted cards, if they exist
							$.getJSON(getHost()+"cards/draftedCards/"+draftID, function(draftedCards){
								// show drafted cards
								$.each(draftedCards, function(key, val){
									draftman.addCardToDrafted(draftman.cardLookupById(val['Card']['number']));
								});
								
								// get highest & lowest card count
								var minCardCount = checkInResult.readyData[userID]['cardCount'];
								var maxCardCount = checkInResult.readyData[userID]['cardCount'];
								$.each(checkInResult.readyData, function(id, val){
									if(id != userID && val['cardCount'] > maxCardCount)
										maxCardCount = val['cardcount'];
									else if(id != userID && val['cardCount'] < minCardCount)
										minCardCount = val['cardcount'];
								});
								
								// check if user already has a pack open
								var alreadyPicked = (maxCardCount != minCardCount && checkInResult.readyData[userID]['cardCount'] == maxCardCount);
								var playerID = draftman.getNeighborId(userID, alreadyPicked);
								var postData = {playerID:playerID, draftID:draftID};
								$.post(getHost()+"cards/userPack", postData, function(response){
									var o = $.parseJSON(response);
									
									if(o.success){
										draftman.showPack(o.cards);
									
										if(alreadyPicked){
											$('.packcard').each(function(i){
												$(this).unbind('click');
											});
										}
									}
								
									// ready check
									var userReady = false;
									if(maxCardCount == 0 && draftman.currentPack.length == 0)
										userReady = true;
									if(maxCardCount != minCardCount && checkInResult.readyData[userID]['cardCount'] == maxCardCount)
										userReady = true;
										
									// send ready notification
									if(userReady){
										remoteCall(topic, 'drafted', [draftman.draftedCards.length], function(readyResponse){
											if(readyResponse.allReady && draftman.currentRound() == 0){
												// update draft status
												var postData = {draftID:draftID, status:2};
												$.post(getHost()+"drafts/updateStatus", postData, function(response){});
											}
										});
									}
								
									// set ready indicators
									$.each(checkInResult.readyData, function(id, val){
										//if(id != userID)
											$('img.player'+id).attr("src","/img/draft-ind-"+(val['ready']?"green":((maxCardCount>0 || o.success)?"off":"red"))+".png");
									});
								});
							});
						});
					});
				});
			});
		},
		
		// connection closed
		function(code, reason){
			sess = null;
			log(reason);
		}
	);
	
	// keyboard handler for ENTER key
	$('#message').keypress(function(e) {
		if(e.keyCode == 13) 
			chatsend(this.value);
	});
	
	// click handler for SEND button
	$('#btn_send').click(function(){
		if($('#message')[0].value)
			chatsend($('#message')[0].value);
	});
	
	// click handler for DRAFT CARD button
	$('#btn_draft').click(function(){
		if(!$.isEmptyObject(draftman.selectedCard))
			draftman.draftSelectedCard(function(){
				// tell the ws server you are ready
				remoteCall(topic, 'drafted', [draftman.draftedCards.length]);
			},
			function(){
				log(o.message);
			});
	});
});

function messageReceived(topic, data){
	var messageType = data[0];
	
	if(messageType == 'message')
		log(data[1].name+": "+data[2]);
		
	else if(messageType == 'announce')
		log(data[2]);
		
	else if(messageType == 'connection'){
		log(data[1].name+" has "+(data[2] == 1 ? "connected" : "disconnected"));
		if(data[2] == 0){
			$('img.player'+data[1].id).attr("src","/img/draft-ind-red.png");
		}
	}
	
	else if(messageType == 'isReady'){
		$('img.player'+data[1].id).attr("src","/img/draft-ind-green.png");
		//log(data[1].name+' is ready');
	}
	
	else if(messageType == 'allReady'){
		$('img.player'+data[1].id).attr("src","/img/draft-ind-green.png");
		//log('All ready');
		
		setTimeout(function(){
			// reset indicators
			$('#live_indicators').children().attr("src", "/img/draft-ind-off.png");
			
			// start draft
			if(!draftman.inProgress()){
				log('Starting draft...');
				log('Opening a new pack... ('+sets[0]+')');
				
				// generate first pack
				var pack = draftman.generatePack(sets[0]);
				var postData = {cards:pack, draftID:draftID};
				$.post(getHost()+"cards/addPack", postData, function(response){
					var o = $.parseJSON(response);
					draftman.showPack(o.cards);
				});
			}
			
			// end draft
			else if(draftman.draftedCards.length == 45){
				var postData = {draftID:draftID, status:3};
				$.post(getHost()+"drafts/updateStatus", postData, function(response){
					remoteCall(topic, 'endDraft', [], function(result){
						log("Draft has ended.");
						window.location = getHost()+"drafts/build/"+draftID;
					});
				});
			}
			
			// open a new pack
			else if(draftman.draftedCards.length > 0 && draftman.draftedCards.length % 15 == 0){
				var setnum = draftman.draftedCards.length == 15 ? 1 : 2;
				log('Opening a new pack... ('+sets[setnum]+')');
				var pack = draftman.generatePack(sets[setnum]);
				var postData = {cards:pack, draftID:draftID};
				$.post(getHost()+"cards/addPack", postData, function(response){
					var o = $.parseJSON(response);
					draftman.showPack(o.cards);
				});
			}
			
			// mid-round: swap packs
			else{
				var postData = {playerID:draftman.getNeighborId(userID), draftID:draftID};
				$.post(getHost()+"cards/userPack", postData, function(response){
					var o = $.parseJSON(response);
					draftman.showPack(o.cards);
				});
			}
			
		}, draftman.delay);
	}
	
	else
		console.log("Other message received from "+topic+" - "+data);
}

function toggleAudio(){
	if(typeof(draftman.soundEnabled) === 'undefined')
		draftman.soundEnabled = true;
	draftman.soundEnabled = !draftman.soundEnabled;
	$('#btn_sound').attr('src', draftman.soundEnabled?'/img/audio-low.png':'/img/audio-off.png');
}

function chatsend(text){
	if(text != ''){
		publish(topic, text);
		$('#message').val('');
	}
}

function log(text) {
	$log = $('#log');
	$log.append(($log.val()?"\n":'')+text);
	$log[0].scrollTop = $log[0].scrollHeight - $log[0].clientHeight;
}