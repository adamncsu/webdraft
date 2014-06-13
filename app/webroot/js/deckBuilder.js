var deckman = {

	cardSetData: [],		// all cards from currently loaded set
	draftedCards: [],		// all cards player has drafted
	tableID: null,			// your table ID
	

	loadCardSets : function(cardSets, callback){
		if(cardSets.length != 3)
			return;
			
		// set 1
		$.getJSON(getHost()+'cardsets/'+ cardSets[0] +'.json', function(data){
			$.each(data.MTGCardInfo, function(key, val){
				val.arturl = deckman.getCardArtUrl(val);
				if(!val.color)
					val.color = deckman.getColor(val);
				deckman.cardSetData.push(val);
			});
			
			// set 2
			$.getJSON(getHost()+'cardsets/'+ cardSets[1] +'.json', function(data){
				if(cardSets[1] != cardSets[0]){
					$.each(data.MTGCardInfo, function(key, val){
						val.arturl = deckman.getCardArtUrl(val);
						if(!val.color)
							val.color = deckman.getColor(val);
						deckman.cardSetData.push(val);
					});
				}
				
				// set 3
				$.getJSON(getHost()+'cardsets/'+ cardSets[2] +'.json', function(data){
					if(cardSets[2] != cardSets[0] && cardSets[2] != cardSets[1]){
						$.each(data.MTGCardInfo, function(key, val){
							val.arturl = deckman.getCardArtUrl(val);
							if(!val.color)
								val.color = deckman.getColor(val);
							deckman.cardSetData.push(val);
						});
					}
					
					console.log('het');
					callback();
				});
			});
		});
	},
	
	addCardToBuilder : function (card, selected){
		selected = (typeof(selected) !== 'undefined' ? selected : true);
		if(typeof card.arturl === 'undefined')
			card.arturl = this.getCardArtUrl(card);
			
		var carddiv = $('<div class="buildcard '+card.color+'"></div>');
			
		$('<div class="raritybox '+(card.rarity=='Land'?'Common':card.rarity).toLowerCase()+'"></div>').appendTo(carddiv);
		$('<div class="cardname">'+card.name+'</div>').appendTo(carddiv);
		$('<div class="checkboxdiv '+(selected?"on":"off")+'"><input type="checkbox" class="checkbox" style="display:none" '+(selected?"checked":"")+'/></div>').appendTo(carddiv);
		
		var column = Math.floor(this.draftedCards.length / 9) + 1;
		carddiv.appendTo($('#drafted_cards_'+column.toString()));
		
		this.draftedCards.push(card);
		
		// mouse handlers
		carddiv.mouseover(function(){
			var lookup = deckman.cardLookupByName($(this).find('.cardname').text());
			$('#cardpreview').html('<img src="'+lookup.arturl+'"/>');
		});
		carddiv.mouseleave(function(){
			$('#cardpreview').html('');
		});
		carddiv.click(function(){
			var cb = $(this).find('input:checkbox');
			var cbd = $(this).find('.checkboxdiv');
			
			cbd.attr('class', 'checkboxdiv '+(cb.prop('checked')?'off':'on'));
			cb.prop('checked', !cb.prop('checked'));
			
			deckman.countCards();
		});
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
	
	getCardArtUrl : function(card){
		var artName = card.name + (card.altart ? card.altart : '');
		artName = artName.replace(/ \/\/ /g, '');
		return '/img/sets/'+ card.set +'/'+ artName + '.jpg';
	},
	
	countCards : function(){
		var cardCount = 0;
		
		// cound draft board
		$('.buildcard').each(function(key, val){
			if($(val).find('.checkbox').prop('checked'))
				cardCount++;
		});
		
		// count lands
		$('.landpicker').find('.value').each(function(key, val){
			cardCount += parseInt($(val).text());
		});
		
		$('#draftboard_count').text(cardCount.toString());
		$('#draftboard_count').css('color', cardCount!=40 ? '#d82c2c':'#ffffff');
		
		return cardCount;
	},
	
	getDeck : function(){
		var d = [];
		$('.buildcard').each(function(i, val){
			d.push($(val).find('.checkbox').prop('checked'));
		});
		
		return d;
	},
	
	getLands : function(){
		var l = [];
		$('.landpicker').each(function(i, val){
			l.push(parseInt($(val).find('.value').text()));
		});
		
		return l;
	},
	
	getDeckAsText : function(){
		var str = '';
		$('.buildcard').each(function(i, val){
			if($(val).find('.checkbox').prop('checked')){
				if(str != '')
					str += "\n";
				str += "1 " + $(val).find('.cardname').text();
			}
		});
		
		var lnames = ['Forest', 'Island', 'Mountain', 'Plains', 'Swamp'];
		$('.landpicker').each(function(i, val){
			var qty = $(val).find('.value').text();
			if(qty == '0')
				return;
				
			if(str != '')
				str += "\n";
				
			str += qty + " " + lnames[i];
		});
		
		return str;
	}
	
};