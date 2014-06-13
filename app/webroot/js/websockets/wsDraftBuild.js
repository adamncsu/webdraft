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
			
			// add another prefix for when a player joins a table
			//sess.prefix('table', getHost()+'tables/');
			
			// set user info
			remoteCall(topic, 'setUserInfo', [username, userID], function(result){
				
				// subscribe to topic
				sess.subscribe(topic, messageReceived);
				
				// load card set
				deckman.loadCardSets(sets, function(){
				
					// check into draft
					remoteCall(topic, 'startBuild', [], function(checkInResult){
						
						// get previously drafted cards, if they exist
						$.getJSON(getHost()+"cards/draftedCards/"+draftID, function(draftedCards){
							
							// see if deck is saved on ws server
							if($('#drafted_cards_1 div').length == 0){
								var savedDeck = checkInResult.player.deck;
								$.each(draftedCards, function(i, val){
									var inDeck = true;
									if(savedDeck.length > 0 && !savedDeck[i])
										inDeck = false;
									deckman.addCardToBuilder(deckman.cardLookupById(val['Card']['number']), inDeck);
								});
							}
							
							// init land pickers
							if($('#drafted_lands_1 div').length == 0){
								var landnames = [['Forest','green'], ['Island','blue'], ['Mountain','red'], ['Plains','white'], ['Swamp','black']];
								for(var i=0; i<5; i++){
									var minus = $('<div class="minus"><img src="/img/minus.png"/></div>');
									var plus = $('<div class="plus"><img src="/img/plus.png"/></div>');
									
									minus.click(function(){
										var val = $(this).parent().find('.value');
										var num = parseInt(val.text());
										if(num > 0)
											num--;
										val.text(num.toString());
										deckman.countCards();
									});
									plus.click(function(){
										var val = $(this).parent().find('.value');
										var num = parseInt(val.text());
										if(num < 40)
											num++;
										val.text(num.toString());
										deckman.countCards();
									});
									
									var yourcard = $('<div class="yourcard land"></div>');
									$('<div class="raritybox '+landnames[i][1]+'"></div>').appendTo(yourcard);
									$('<div class="cardname">'+landnames[i][0]+'</div>').appendTo(yourcard);
									var landpicker = $('<div class="landpicker"></div>');
									minus.appendTo(landpicker);
									$('<div class="value">0</div>').appendTo(landpicker);
									plus.appendTo(landpicker);
									landpicker.appendTo(yourcard);
									
									yourcard.appendTo($('#drafted_lands').children().eq(i));
								}
							}
							$('#draftboard_subtitle').show();
							
							// check lands from ws server
							var savedLands = checkInResult.player.lands;
							if(savedLands.length > 0){
								for(var i=0; i<5; i++)
									$('.landpicker').eq(i).find('.value').text(savedLands[i].toString());
							}
							
							// update card count
							deckman.countCards();
							
							// build tools
							$('#build_tools li').eq(0).click(function(){
								saveDeck();
								return false;
							});
							$('#build_tools li').eq(1).click(function(){
								deckText();
								return false;
							});
							$('#build_tools li').eq(2).click(function(){
								analyzeDeck();
								return false;
							});
							$('#build_tools li').eq(3).click(function(){
								sortCards();
								return false;
							});
							
							// save deck button
							/*if(!$('#btn_save').is(':visible')){
								$('#btn_save').show();
								$('#btn_save').click(function(){
									saveDeck();
								});
							}*/
							
							// load table data
							/*$.getJSON(getHost()+"tables/all/"+draftID, function(tables){
								$('#tables').html('');
								$.each(tables, function(i, table){
									var myTable = (table['Seat1']['id'] == userID || table['Seat2']['id'] == userID);
									if(myTable){
										deckman.tableID = table['Table']['id'];
										sess.subscribe("table:"+deckman.tableID, tableMessage);
									}
				
									var draftTable = $('<div class="drafttable"></div>');
									var topLeft = $('<div class="dt_topleft"></div>');
									$('<div class="dt_title">Table '+(i+1)+'</div>').appendTo(topLeft);
									
									if(table['Table']['status'] == 0){
										if(myTable)
											$('<div class="dt_actions"><a href="#" onclick="standUp()">Leave</a></div>').appendTo(topLeft);
										else
											$('<div class="dt_actions"><a href="#" onclick="takeSeat('+table['Table']['id']+')">Join</a></div>').appendTo(topLeft);
									}
									else
										$('<div class="dt_actions">In progress</div>').appendTo(topLeft);
										
									topLeft.appendTo(draftTable);
									
									var tableMain = $('<div class="dt_main"></div>');
									
									var s1 = table['Seat1']['username'] == null ? '' : table['Seat1']['username'];
									var s2 = table['Seat2']['username'] == null ? '' : table['Seat2']['username'];
									$('<div class="dt_main_left">'+s1+'<br/>'+s2+'</div>').appendTo(tableMain);
									
									var btnd = ((myTable && table['Table']['status'] == 0 && table['Seat1']['id'] != null && table['Seat2']['id'] != null) ? 'inline' : 'none');
									$('<div class="dt_main_right"><input type="submit" value="Ready" onclick="isReady()" class="button btn_ready" style="display:'+btnd+'"/></div>').appendTo(tableMain);
									
									tableMain.appendTo(draftTable);
									draftTable.appendTo('#tables');
								});
							});*/
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
});

function messageReceived(topic, data){
	var messageType = data[0];
	
	if(messageType == 'message')
		log(data[1].name+": "+data[2]);
		
	else if(messageType == 'announce')
		log(data[2]);
		
	else if(messageType == 'connection'){
		log(data[1].name+" has "+(data[2] == 1 ? "connected" : "disconnected"));
	}
	
	/*else if(messageType == 'tablelist'){
		$.each(data[1], function(i, table){
			var myTable = (table['Seat1']['id'] == userID || table['Seat2']['id'] == userID);
			if(myTable)
				deckman.tableID = table['Table']['id'];
		
			// update names
			var namestring = "";
			if(table['Seat1']['id'] != null)
				namestring = table['Seat1']['username']+'<br/>';
			if(table['Seat2']['id'] != null)
				namestring += table['Seat2']['username']+'<br/>';
			$('.drafttable').eq(i).find('.dt_main_left').html(namestring);
			
			// update sit/leave
			var actions = $('.drafttable').eq(i).find('.dt_actions');
			if(table['Table']['status'] != 0)
				actions.html('In progress');
			else if(myTable)
				actions.html('<a href="#" onclick="standUp()">Leave</a>');
			else
				actions.html('<a href="#" onclick="takeSeat('+table['Table']['id']+')">Join</a>');
			
			// ready button
			var readyBtn = $('.drafttable').eq(i).find('.dt_main_right');
			var showReadyBtn = (myTable && table['Seat1']['id'] != null && table['Seat2']['id'] != null);
			readyBtn.html('<input type="submit" value="Ready" class="button btn_ready" onclick="isReady()" style="display:'+(showReadyBtn?'inline':'none')+'"/>');
		});
	}*/
	
	else
		console.log("Other message received from "+topic+" - "+data);
}

function storeDeck(){
	var c = deckman.countCards();

	var d = deckman.getDeck();
	var l = deckman.getLands();
	
	remoteCall(topic, 'saveDeck', [d, l], function(result){
		log('Deck saved to server');
	});
}

function saveDeck(){
	storeDeck();
	
	var url = getHost() + 'drafts/cod';
	var form = $('<form action="' + url + '" method="post" target="_blank" style="display:none"></form>');
	form.append('<textarea name="deck">'+deckman.getDeckAsText()+'</textarea>');
	form.submit();
}

function analyzeDeck(){
	storeDeck();
	
	var url = 'http://deckstats.net/';
	var form = $('<form action="' + url + '" method="post" target="_blank" style="display:none"></form>');
	form.append('<input type="text" name="hide_statistics" value="0"/>');
	form.append('<input type="text" name="builder" value="0"/>');
	form.append('<input type="text" name="group_by" value="none"/>');
	form.append('<input type="text" name="smart_hybrid" value="on"/>');
	form.append('<input type="text" name="auto_singles" value="on"/>');
	form.append('<input type="text" name="merge_same_cards" value="on"/>');
	form.append('<textarea name="deck">'+deckman.getDeckAsText()+'</textarea>');
	
	form.submit();
}

function deckText(){
	storeDeck();
	
	var d = $('<div id="deck_dialog" title="Deck List"><textarea class="ui-dialog-textarea">'+deckman.getDeckAsText()+'</textarea></div>');
	
	d.dialog({
		resizable: false,
		close: function() {
			$(this).remove();
		},
		buttons: [ 
			{text: "Select All", click: function() {
				$(this).children().eq(0).select();
			}},
			{text: "Close", click: function() {
				$(this).remove();
			}},
		]
	});
}

function sortCards(){
	/*var curr = $('.buildcard');
	var sorted = [];
	
	$.each(curr, function(i, val){
		if($(val).find('.checkbox').prop('checked')){
	});
	
	$('#drafted_cards div').empty();*/
	
	//deckman.addCardToBuilder(deckman.cardLookupById(val['Card']['number']), inDeck);
}

/*function takeSeat(tableID){
	var data = {tableID:tableID, draftID:draftID};
	$.post(getHost()+"tables/sit", data, function(response){
		var o = $.parseJSON(response);
		
		if(o.success){
			remoteCall(topic, 'updateTables', [o.tables]);
			sess.subscribe("table:"+deckman.tableID, tableMessage);
		}
		else
			log(o.message);
	});
}

function standUp(){
	var data = {draftID:draftID};
	$.post(getHost()+"tables/stand", data, function(response){
		var o = $.parseJSON(response);
		
		if(o.success){
			remoteCall(topic, 'updateTables', [o.tables]);
			sess.unsubscribe("table:"+deckman.tableID);
			deckman.tableID = null;
		}
		else
			log(o.message);
	});
}

function isReady(){
	remoteCall("table:"+deckman.tableID, 'isReadyTable', [draftID], function(success){
		if(!success['tableReady'])
			window.location = getHost()+"play/"+deckman.tableID;
			//log('Ready. Waiting for your oppenent...');
	}, function(error){
		log(error.desc);
	});
}

function tableMessage(topic, data){
	var messageType = data[0];
	
	if(messageType == 'message')
		log(data[1].name+": "+data[2]);
		
	else if(messageType == 'announce')
		log(data[2]);
	
	else if(messageType == 'isReady')
		console.log(data[1].name+' is ready');
		
	else if(messageType == 'allReady'){
		log('Both players are ready. Starting game...');
		window.location = getHost()+"play/"+deckman.tableID;
	}
}*/

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