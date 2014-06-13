var draftman = {

	cardSetData: [],		// all cards from currently loaded set
	currentPack: [],		// currently opened pack
	selectedCard: {},		// currently selected card
	draftedCards: [],		// all cards player has drafted
	seatInfo: [],			// list of user IDs
	soundEnabled: true,		// sound option
	delay: 500,				// delay between pack swaps
	

	loadCardSets : function(cardSets, callback){
		if(cardSets.length != 3)
			return;
			
		// set 1
		$.getJSON(getHost()+'cardsets/'+ cardSets[0] +'.json', function(data){
			$.each(data.MTGCardInfo, function(key, val){
				val.arturl = draftman.getCardArtUrl(val);
				if(!val.color)
					val.color = draftman.getColor(val);
				draftman.cardSetData.push(val);
			});
			
			// set 2
			$.getJSON(getHost()+'cardsets/'+ cardSets[1] +'.json', function(data){
				if(cardSets[1] != cardSets[0]){
					$.each(data.MTGCardInfo, function(key, val){
						val.arturl = draftman.getCardArtUrl(val);
						if(!val.color)
							val.color = draftman.getColor(val);
						draftman.cardSetData.push(val);
					});
				}
				
				// set 3
				$.getJSON(getHost()+'cardsets/'+ cardSets[2] +'.json', function(data){
					if(cardSets[2] != cardSets[0] && cardSets[2] != cardSets[1]){
						$.each(data.MTGCardInfo, function(key, val){
							val.arturl = draftman.getCardArtUrl(val);
							if(!val.color)
								val.color = draftman.getColor(val);
							draftman.cardSetData.push(val);
						});
					}
					
					callback();
				});
			});
		});
	},
	
	generatePack : function(set){
		var pack = [];
	
		pack.push(this.randomCard(set, Math.random() < 0.125 ? 'Mythic' : 'Rare').id);
		for(var i=0; i<3; i++)
			pack.push(this.randomCard(set, 'Uncommon').id);
		for(var i=0; i<10; i++)
			pack.push(this.randomCard(set, 'Common').id);
			
		if(set == 'Modern Masters')
			pack.push(this.randomCard(set).id);
		else
			pack.push(this.randomCard(set, 'Land').id);
		
		return pack;
	},
	
	showPack: function(pack){
		var packdiv = $('<div></div>');
		this.currentPack = [];
		
		for(var i=0; i<pack.length; i++){
			var card = this.cardLookupById(pack[i]['Card']['number']);
			card.dbid = pack[i]['Card']['id'];
			this.currentPack.push(card);
			
			var carddiv = $('<div class="packcard '+card.color+'"></div>');
			
			$('<div class="raritybox '+(card.rarity=='Land'?'Common':card.rarity).toLowerCase()+'"></div>').appendTo(carddiv);
			$('<div class="cardname">'+card.name+'</div>').appendTo(carddiv);
			
			// mana cost images
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
			manacost.appendTo(carddiv);
			
			// mouse handlers
			carddiv.mouseover(function(){
				$('#cardpreview').html('<img src="'+draftman.getCardArtUrl(draftman.currentPack[$(this).index()])+'"/>');
			});
			carddiv.click(function(){
				draftman.selectCard($(this));
			});
			
			// add card to stack
			carddiv.appendTo(packdiv);
		}
		$('#packstack').html(packdiv);
		
		// stack mouse handler
		$('#packstack').mouseleave(function(){
			if(draftman.selectedCard.arturl)
				$('#cardpreview').html('<img src="'+draftman.selectedCard.arturl+'"/>');
			else
				$('#cardpreview').html('');
		});
		
		if(this.currentPack.length > 0)
			$('#packstack').show();
			
		$('#btn_draft').hide();
		this.selectedCard = {};
		
		// play sound
		if(this.soundEnabled)
			document.getElementById('bell').play();
	},
	
	selectCard : function(carddiv){
		this.selectedCard = this.currentPack[carddiv.index()];
		this.selectedCard.index = carddiv.index();
		
		// deselect other cards
		$('.packcard').each(function(i){
			$(this).find('.cardname').html($(this).find('.cardname').text());
		});
		
		carddiv.find('.cardname').html('<img class="ind_select" src="/img/tri_r.png"/>'+carddiv.find('.cardname').text());
		$('#btn_draft').show();
	},
	
	draftSelectedCard : function(success, error){
		var postData = {cardID:parseInt(this.selectedCard.dbid)};
		$.post(getHost()+"cards/draftCard", postData, function(response){
			var o = $.parseJSON(response);
			if(o.success){
				// add card to drafted cards div
				draftman.addSelectedToDrafted();
			
				// remove card from pack div
				draftman.removeSelectedCard();
				if(draftman.draftedCards.length % 15 == 0)
					$('#packstack').hide();
				
				// callback function
				success();
			}
			else
				error();
		});
	},
	
	addSelectedToDrafted : function(){
		this.addCardToDrafted(this.selectedCard);
	},
	
	addCardToDrafted : function(card){
		if(typeof card.arturl === 'undefined')
			card.arturl = this.getCardArtUrl(card);
			
		var carddiv = $('<div class="yourcard '+card.color+'"></div>');
		$('<div class="raritybox '+(card.rarity=='Land'?'Common':card.rarity).toLowerCase()+'"></div>').appendTo(carddiv);
		$('<div class="cardname">'+card.name+'</div>').appendTo(carddiv);
		
		var column = Math.floor(this.draftedCards.length / 9) + 1;
		carddiv.appendTo($('#drafted_cards_'+column.toString()));
		
		this.draftedCards.push(card);
		
		// mouse handlers
		carddiv.mouseover(function(){
			var lookup = draftman.cardLookupByName($(this).find('.cardname').text());
			$('#cardpreview').html('<img src="'+lookup.arturl+'"/>');
		});
		carddiv.mouseleave(function(){
			//$('#cardpreview').html('');
			if(draftman.selectedCard.arturl)
				$('#cardpreview').html('<img src="'+draftman.selectedCard.arturl+'"/>');
			else
				$('#cardpreview').html('');
		});
	},
	
	removeSelectedCard : function(){
		var packdiv = $('<div></div>');
		
		// remove the display div
		$('.packcard').each(function(i){
			$(this).unbind('click');
			
			if(i != draftman.selectedCard.index)
				$(this).appendTo(packdiv);
		});
		$('#packstack').html(packdiv);
		
		// remove the card from the draftman pack
		this.currentPack.splice(this.selectedCard.index, 1);
		
		$('#btn_draft').hide();
		this.selectedCard = {};
	},
	
	cardLookupById : function(id){
		for(var i=0; i<this.cardSetData.length; i++){
			if(this.cardSetData[i].id == id)
				return this.cardSetData[i];
		}
		return false;
	},
	
	cardLookupByName : function(name){
		for(var i=0; i<this.cardSetData.length; i++){
			if(this.cardSetData[i].name == name)
				return this.cardSetData[i];
		}
		return false;
	},
	
	randomCard : function(set, rarity){
		rarity = (typeof(rarity) !== 'undefined' ? rarity : 'all');
		
		var arr = [];
		
		$.each(draftman.cardSetData, function(key, val){
			if(val.set != set)
				return true; // continue
			
			if(rarity == 'all')
				arr.push(val);
			else if(rarity == 'Land' && val.type.substr(0,4) == 'Land')
				arr.push(val);
			else if(val.rarity == rarity)
				arr.push(val);
		});
		
		if(arr.length == 0)
			return null;
		
		return arr[Math.floor(Math.random() * arr.length)];
	},
	
	getColor : function(card){
		var cardType = card.type.toLowerCase();
		if(cardType.indexOf('artifact') > -1)
			return 'artifact';
		else if(cardType.indexOf('land') > -1)
			return 'land';
			
		// multi color mana symbols
		if(card.cost.indexOf("(") > -1){
			var c = card.cost.substr(card.cost.indexOf("(")+1,3);
			return 'multi';
		}
		
		var c = [];
		for(var i=0; i<card.cost.length; i++){
			if(card.cost.charAt(i) == 'R' && c.indexOf('red') < 0)
				c.push('red');
			else if(card.cost.charAt(i) == 'U' && c.indexOf('blue') < 0)
				c.push('blue');
			else if(card.cost.charAt(i) == 'G' && c.indexOf('green') < 0)
				c.push('green');
			else if(card.cost.charAt(i) == 'B' && c.indexOf('black') < 0)
				c.push('black');
			else if(card.cost.charAt(i) == 'W' && c.indexOf('white') < 0)
				c.push('white');
		}
		
		if(c.length == 1)
			return c[0];
		else
			return 'multi';
	},
	
	currentRound : function(){
		return Math.ceil(this.draftedCards.length / 15);
	},
	
	getCardArtUrl : function(card){
		var artName = card.name + (card.altart ? card.altart : '');
		artName = artName.replace(/ \/\/ /g, '');
		return '/img/sets/'+ card.set +'/'+ artName + '.jpg';
	},
	
	getNeighborId : function(id, alreadyPicked){
		id = (typeof(id) !== 'string' ? id : parseInt(id));
		alreadyPicked = (typeof(alreadyPicked) !== 'undefined' ? alreadyPicked : false);
		
		if(this.draftedCards.length % 15 == 0)
			return id;
		var yourIndex = this.seatInfo.indexOf(id);
		var theirIndex = yourIndex;
		var round = this.currentRound();
		var myCards = this.draftedCards.length - (alreadyPicked ? 1 : 0);
		var cardsGone = myCards - (15 * (round-1));
		
		var delta = cardsGone - (this.seatInfo.length * Math.floor(cardsGone/this.seatInfo.length));
		
		// going left
		if(round == 1 || round == 3)
			theirIndex -= delta;
		
		// going right
		else if(round == 2)
			theirIndex += delta
		
		if(theirIndex < 0)
			theirIndex = theirIndex + this.seatInfo.length;
		else if(theirIndex > this.seatInfo.length - 1)
			theirIndex = theirIndex - this.seatInfo.length;
		
		return this.seatInfo[theirIndex];
	},
	
	inProgress : function(){
		return !(draftman.currentPack.length == 0 && draftman.draftedCards.length == 0); 
	}
	
};