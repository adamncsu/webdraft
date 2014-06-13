var sess;
var topic;

var gameCardDragOptions = {
	grid: [gman.gridSize, gman.gridSize],
	distance: 20,
	scroll: false,
	stack: '#gameboard .gamecard',
	revert: 'invalid',
	opacity: 0.3,
	containment: 'window',
	zIndex: 1000,
	start: function(event, ui){ 
		gman.dragging = true;
		
		// fix for revert+grid bug
		ui.originalPosition.top = $(this).position().top;
		ui.originalPosition.left = $(this).position().left;
	},
	stop: function(event, ui){ 
		gman.dragging = false;
	}
};

var handCardDragOptions = {
	scroll: false,
	revert: 'invalid',
	opacity: 0.3,
	cursorAt: {left:43, top:60},
	snap: '#gameboard_window',
	snapMode: 'inner',
	helper: function(event){
		var cID = gman.getCardId($(event.currentTarget));
		var gameCard = $('<div class="movingcard cid_'+cID+'"></div>');
		gameCard.html('<img src="'+gman.cardLookupById(cID).arturl+'" width=85 height=120 />');
		return gameCard;
	},
	start: function(event, ui){ 
		gman.dragging = true;
	},
	stop: function(event, ui){ 
		gman.dragging = false;
	}
}

$(document).ready(function() {

	log("Connecting...");
	
	var uri = 'ws://'+location.host+':8080';
	var prefix = ['play', getHost()+'play/'];
	topic = "play:"+tableID;
	
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
				gman.loadCardSet(setname, function(){
				
					remoteCall(topic, 'joinGame', [draftID], function(wsCards){
				
						// get drafted cards
						$.getJSON(getHost()+"cards/draftedCards/"+draftID, function(draftedCards){
							$.each(draftedCards, function(i, card){
								if(wsCards.deck[i])
									gman.library.push(card['Card']['number']);
							});
							
							// lands
							var l = ['Forest', 'Island', 'Mountain', 'Plains', 'Swamp'];
							$.each(wsCards.lands, function(i, land){
								for(var j=0; j<parseInt(land); j++)
									gman.library.push('bl'+l[i].charAt(0).toLowerCase()+(Math.round(Math.random()*3)+1).toString());
							});
							
							gman.shuffleLibrary();
							for(var i=0; i<7; i++)
								drawCard();
						});
						
						// game board droppable
						$('#gameboard_window').droppable({
							accept: '.gamecard, .handcard, .tokenholder',
							drop: dropToGameBoard,
							tolerance: 'pointer'
						});
						/*$('#gameboard_window').droppable({
							accept: '.box2',
							tolerance: 'pointer'
						});*/
						
						// hand droppable
						$('#hand_container').droppable({
							accept: '.gamecard',
							greedy: true,
							hoverClass: "ui-highlight",
							drop: dropToHand,
							tolerance: 'pointer'
						});
						
						// graveyard
						$('.graveyard').droppable({
							accept: '.gamecard, .handcard',
							greedy: true,
							hoverClass: "ui-highlight",
							drop: dropToGraveyard,
							tolerance: 'pointer'
						});
						
						// tokens
						$('.tokenholder').draggable({
							scroll: false,
							revert: 'invalid',
							opacity: 0.3,
							//cursorAt: {left:43, top:60},
							helper: function(event){
								return '<div class="tokenclone"></div>';
							}
						});
						$('.tokenholder').droppable({
							accept: '.token, .box2',
							greedy: true,
							hoverClass: "ui_highlight",
							drop: dropToToken,
							tolerance: 'pointer'
						});
						
						// keyboard handler for ENTER key
						$('#message').keypress(function(e) {
							if(e.keyCode == 13) 
								chatsend(this.value);
						});
						
						// click handler for DRAW CARD button
						$('#btn_draw').click(drawCard);
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
		
	// chat collapse button handler
	$('#chat_collapse').click(function(){
		$('#chat_content').animate({width:'toggle'}, 250, 'swing', function(){
			$('#chat_collapse').text($('#chat_content').css('display')=='none'?'<':'>');
			$log[0].scrollTop = $log[0].scrollHeight - $log[0].clientHeight;
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
	}
	
	else
		console.log("Other message received from "+topic+" - "+data);
}

function cardHover(event){
	if(event.type == 'mouseenter'){
		var cID = gman.getCardId($(event.currentTarget));
		$('#card_preview').html('<img src="'+gman.cardLookupById(cID).arturl+'" />');
	}
	else if(event.type == 'mouseleave'){
		if(!gman.dragging)
			$('#card_preview').html('');
	}
}

function dropToGameBoard(event, ui){
	var o = $(ui.draggable);
	
	if(o.hasClass('gamecard'))
		snapToGrid(ui.draggable);
	else if(o.hasClass('tokenholder')){
		var gameCard = $('<div class="tokencard"></div>');
		gameCard.html('<img src="/img/carddummy.jpg" width=85 height=120 />');
		
		gameCard.draggable(gameCardDragOptions);
		//gameCard.hover(cardHover);
		gameCard.click(tapCard);
				
		gameCard.appendTo($('#gameboard'));
		gameCard.css({top:(event.pageY - gameCard.height()/2), left:(event.pageX - gameCard.width()/2)});
		snapToGrid(gameCard);
		
		log('You created a token');
		//o.remove();
	}
	else{
		var cID = gman.getCardId(o);
		var card = gman.cardLookupById(cID);
		
		var gameCard = $('<div class="gamecard cid_'+cID+'"></div>');
		gameCard.html('<img src="'+card.arturl+'" width=85 height=120 />');
		
		gameCard.draggable(gameCardDragOptions);
		gameCard.hover(cardHover);
		gameCard.click(tapCard);
				
		gameCard.appendTo($('#gameboard'));
		gameCard.css({top:(event.pageY - gameCard.height()/2), left:(event.pageX - gameCard.width()/2)});
		snapToGrid(gameCard);
		
		if(o.hasClass('handcard'))
			gman.removeCard(cID, gman.hand);
		else if(o.hasClass('libcard'))
			gman.removeCard(cID, gman.library);
		else if(o.hasClass('gravecard'))
			gman.removeCard(cID, gman.graveyard);
			
		gman.addCard(cID, gman.active);
		
		log('You moved '+card.name+' into play');
		
		gman.dragging = false;
		o.remove();
	
		gman.printCardData();
	}
}

function dropToHand(event, ui){
	var o = $(ui.draggable);
	
	if(!o.hasClass('handcard')){
		var cID = gman.getCardId(o);
		var card = gman.cardLookupById(cID);
		
		var handCard = $('<div class="handcard '+card.color+' cid_'+cID+'">');
		$('<div class="cardname">'+card.name+'</div>').appendTo(handCard);
		var manacost = $('<div class="manacost"></div>');
		if(card.cost){
			for(var j=0; j<card.cost.length; j++){
				var ch = card.cost.charAt(j);
				if(ch == '('){
					$('<img src="/img/mana/' + card.cost.charAt(j+1) + card.cost.charAt(j+3) + '.png"/>').appendTo(manacost);
					j += 4;
				}
				else if(['/',' ','(',')'].indexOf(ch) == -1)
					$('<img src="/img/mana/' + ch + '.png"/>').appendTo(manacost);
			}
		}
		manacost.appendTo(handCard);
		handCard.appendTo($('#hand'));
		
		handCard.draggable(handCardDragOptions);
		handCard.hover(cardHover);
		
		if(o.hasClass('gamecard'))
			gman.removeCard(cID, gman.active);
		else if(o.hasClass('libcard'))
			gman.removeCard(cID, gman.library);
		else if(o.hasClass('gravecard'))
			gman.removeCard(cID, gman.graveyard);
			
		log('You moved '+card.name+' to your hand');
		
		gman.addCard(cID, gman.hand);
		gman.dragging = false;
		o.remove();
		
		gman.printCardData();
	}
}

function dropToGraveyard(event, ui){
	var o = $(ui.draggable);
	
	if(!o.hasClass('gravecard')){
		var cID = gman.getCardId(o);
		var card = gman.cardLookupById(cID);
		
		if(o.hasClass('gamecard'))
			gman.removeCard(cID, gman.active);
		else if(o.hasClass('handcard'))
			gman.removeCard(cID, gman.hand);
		else if(o.hasClass('libcard'))
			gman.removeCard(cID, gman.library);
			
		log('You moved '+card.name+' to your graveyard');
		
		gman.addCard(cID, gman.graveyard);
		gman.dragging = false;
		o.remove();
		
		gman.printCardData();
	}
}

function tapCard(event){
	var carddiv = event.currentTarget;
	if($(carddiv).hasClass('tapped'))
		$(carddiv).removeClass('tapped');
	else
		$(carddiv).addClass('tapped');
		
	snapToGrid(carddiv);
}

function snapToGrid(obj){
	var o = $(obj);
	var pos = o.offset();
	var board = $('#gameboard_window').offset();
	
	var newPos = {left:pos.left, top:pos.top};
	
	if(pos.left < board.left)
		newPos.left = 250;
	if(pos.top < board.top)
		newPos.top = 30;
	
	if(newPos.top % gman.gridSize != 0 || newPos.left % gman.gridSize != 0){
		newPos.left = Math.round(newPos.left/gman.gridSize)*gman.gridSize;
		newPos.top = Math.round(newPos.top/gman.gridSize)*gman.gridSize;
	}
	
	o.offset({left:newPos.left, top:newPos.top});
}

function drawCard(){
	var card = gman.drawCard();
	if(card){
		var handCard = $('<div class="handcard '+card.color+' cid_'+card.id+'">');
		$('<div class="cardname">'+card.name+'</div>').appendTo(handCard);
		var manacost = $('<div class="manacost"></div>');
		if(card.cost){
			for(var j=0; j<card.cost.length; j++){
				var ch = card.cost.charAt(j);
				if(ch == '('){
					$('<img src="/img/mana/' + card.cost.charAt(j+1) + card.cost.charAt(j+3) + '.png"/>').appendTo(manacost);
					j += 4;
				}
				else if(['/',' ','(',')'].indexOf(ch) == -1)
					$('<img src="/img/mana/' + ch + '.png"/>').appendTo(manacost);
			}
		}
		manacost.appendTo(handCard);
		handCard.appendTo($('#hand'));
		
		handCard.draggable(handCardDragOptions);
		handCard.hover(cardHover);
		
		gman.drawCounter.count++;
		if(gman.drawCounter.timeout)
			clearTimeout(gman.drawCounter.timeout);
		gman.drawCounter.timeout = setTimeout(function(){
			log('You drew '+(gman.drawCounter.count>1?gman.drawCounter.count+' cards':'a card'));
			gman.drawCounter.count = 0;
		}, 1000);
	}
	else{
		log('Draw failed. No cards in library.');
	}
}

function chatsend(text){
	if(text != ''){
		if(text.charAt(0) == '/')
			parseCommand(text.substr(1));
		else
			publish(topic, text);
		$('#message').val('');
	}
}

function parseCommand(text){
	var args = text.split(' ');
	
	switch(args[0]){
		case 'roll':
			log('You rolled '+(Math.round(Math.random()*(args[1]-1))+1).toString()+' (1-'+args[1]+')');
		break;
		
		case 'coin':
			log('You flip a coin -- '+(Math.round(Math.random())==0?'Heads':'Tails'));
		break;
		
		case 'mulligan':
			log('You took a mulligan');
			$('#hand').empty();
			var newHandSize = gman.hand.length - 1;
			gman.takeMulligan();
			for(var i=0; i<newHandSize; i++)
				drawCard();
		break;
		
		case 'help':
			var str = 'Console commands:\n' +
			'/help' + '\n\t' + 'Displays a list of console commands' + '\n' +
			'/roll [maximum]' + '\n\t' + 'Rolls a dice from 1 to [maximum]' + '\n' +
			'/coin' + '\n\t' + 'Flips a 2-sided coin' + '\n' +
			'/mulligan' + '\n\t' + 'Shuffle hand into library and draw a new hand';
			log(str);
		break;
		
		default:
			log('Type /help for a list of commands.');
		break;
	}
}

function log(text) {
	$log = $('#log');
	var t = (new Date()).toTimeString();
	$log.append(($log.val()?"\n":'')+t.split(' ')[0]+'&nbsp;&nbsp;'+text);
	$log[0].scrollTop = $log[0].scrollHeight - $log[0].clientHeight;
	
	// highlight chat button if log is hidden
	if($('#chat_content').css('display') == 'none')
		$('#chat_collapse').effect('highlight', {color:'#aaaaaa'}, 250);
}