var sess;
var topic;

$(document).ready(function() {
	log("Connecting...");
	
	var uri = 'ws://'+location.host+':8080';
	var prefix = ['draft', getHost()+'drafts/'];
	topic = "draft:"+draftID;
	
	//ab._debugrpc = true;
	//ab._debugpubsub = true;
	
	/*var conn = new WebSocket('ws://localhost:8000');
	conn.onmessage = function(e) {
		console.log(e.data);
	};
	conn.onopen = function () {
		conn.send('Hello World!');
	};*/
	
	ab.connect(uri,
		// connection open
		function(session){
			sess = session;
			sess.prefix(prefix[0], prefix[1]);
			
			// set username, then subscribe to draft room
			remoteCall(topic, 'setUserInfo', [username, userID], function(result){
				sess.subscribe(topic, messageReceived);
				
				// show controls
				$('#draft_lobby_controls').show();
			});
		},
		
		// connection closed
		function(code, reason, detail){
			console.log(uri);
			console.log(code);
			console.log(reason);
			console.log(detail);
			sess = null;
			log(reason);
		}
 
		// optional dictionary of options
		//,{'maxRetries': 5, 'retryDelay': 2000, 'skipSubprotocolAnnounce': true, 'skipSubprotocolCheck': true}
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
		
	else if(messageType == 'connection')
		log(data[1].name+" has "+(data[2] == 1 ? "connected" : "disconnected"));
	
	else if(messageType == 'seatlist'){
		var seats = [];
		var sitting = false;
		
		for(var i=1; i<=8; i++){
			var li = '<li>Seat ' + i + ': ';
			if(!data[1]['Seat'+i.toString()]['id'])
				li += '<a href="#" onclick="takeSeat('+i+')">Open</a>';
			else
				li += data[1]['Seat'+i.toString()]['username'];
			li += '</li>';
			seats.push(li);
			
			if(data[1]['Seat'+i.toString()]['id'] == userID)
				sitting = true;
		}
			
		$('#seat_list').html(seats.join(''));
		
		if(sitting)
			$('#seat_leave').show();
		else
			$('#seat_leave').hide();
	}
	
	else if(messageType == 'starting'){
		window.location = getHost()+"drafts/live/"+draftID;	
	}
}

function takeSeat(num){
	var data = {draft:draftID, seat:num};
	$.post(getHost()+"drafts/sit", data, function(response){
		var o = $.parseJSON(response);
		
		if(o.response.success){
			announce(topic, username+' is now at Seat '+num);
			remoteCall(topic, 'updateSeats', [o]);
		}
		else
			log(o.response.message);
	});
}
function standUp(){
	var data = {draft:draftID};
	$.post(getHost()+"drafts/stand", data, function(response){
		var o = $.parseJSON(response);
		
		if(o.response.success)
			remoteCall(topic, 'updateSeats', [o]);
		else
			log(o.response.message);
	});
}

function startDraft(){
	$("#start_btn").hide();
	
	// check player count
	$.getJSON(getHost()+"drafts/seatlist/"+draftID, function(data){
		var seatIDs = [];
		
		$.each(data, function(key, val){
			if(key.substr(0,4) == 'Seat' && val.id != null)
				seatIDs.push(val.id);
		});
		
		if(seatIDs.length > 0){
		
			// initialize draft
			$.post(getHost()+"drafts/init/"+draftID, null, function(response){
				var o = $.parseJSON(response);
				
				// start draft
				if(o.success)
					remoteCall(topic, 'startDraft', [seatIDs]);
				else
					log(o.message);
			});
		}
		else{
			log('At least 1 player must be seated before the draft can begin.');
			$("#start_btn").show();
		}
	});
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