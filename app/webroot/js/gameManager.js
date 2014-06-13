var gman = {

	cardSetData: [],		// all cards from currently loaded set
	dragging: false,		// currently dragging a card
	hideImages: false,		// shows a dummy image instead of cards
	gridSize: 10,			// game board snap size
	drawCounter: {			// keeps track of multiple card draws
		timeout: null,
		count: 0
	},			
	
	library: [],			// your cards
	hand: [],
	graveyard: [],
	exiled: [],
	active: [],
	
	
	loadCardSet: function(cardSetName, callback){
		$.getJSON(getHost()+'cardsets/'+ cardSetName +'.json', function(data){
			
			$.each(data.MTGCardInfo, function(key, val){
				val.arturl = gman.getCardArtUrl(val);
				if(!val.color)
					val.color = gman.getColor(val);
				gman.cardSetData.push(val);
			});
			
			// add basic lands
			var l = ['Forest', 'Island', 'Mountain', 'Plains', 'Swamp'];
			for(var i=0; i<l.length; i++){
				for(var j=1; j<=4; j++){
					gman.cardSetData.push({
						id: 		'bl'+l[i].charAt(0).toLowerCase()+j.toString(),
						name:		l[i],
						type: 		'Basic Land - '+l[i],
						set:		'Basic Lands',
						rarity: 	'Land',
						color:		'land',
						arturl:		'/img/sets/Basic Lands/'+l[i]+j.toString()+'.jpg'
					});
				}
			}
			
			callback();
		});
	},
	
	getCardArtUrl : function(card){
		var artName = card.name + (card.altart ? card.altart : '');
		artName = artName.replace(/ \/\/ /g, '');
		if(this.hideImages)
			return '/img/carddummy.jpg';
		return '/img/sets/'+ card.set +'/'+ artName + '.jpg';
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

	cardLookupById : function(id){
		for(var i=0; i<this.cardSetData.length; i++){
			if(this.cardSetData[i].id == id)
				return this.cardSetData[i];
		}
		return false;
	},
	
	getCardId : function(obj){
		var classList = obj.attr('class').split(/\s+/);
		
		for(var i=0; i<classList.length; i++){
			if(classList[i].substr(0,3) == 'cid')
				return classList[i].substr(4);
		}
		
		return false;
	},
	
	drawCard : function(){
		if(this.library.length == 0)
			return false;
			
		var c = this.library.shift();
		this.hand.push(c);
		
		return this.cardLookupById(c);
	},
	
	takeMulligan : function(){
		while(this.hand.length > 0)
			this.library.push(this.hand.shift());
			
		this.shuffleLibrary();
	},
	
	shuffleLibrary: function(){
		for(var j, x, i = this.library.length; i; j = parseInt(Math.random() * i), x = this.library[--i], this.library[i] = this.library[j], this.library[j] = x);
	},
	
	removeCard : function(cID, from){
		var i = from.indexOf(cID);
		
		if(i == -1)
			return false;
			
		return from.splice(i, 1);
	},
	
	addCard : function(cID, to){
		return to.push(cID);
	},
	
	printCardData : function(){
		console.log('library:\t'+this.library.length);
		console.log('hand:\t'+this.hand.length);
		console.log('active:\t'+this.active.length);
		console.log('graveyard:\t'+this.graveyard.length);
	}
	
};