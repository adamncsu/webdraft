function remoteCall(topic, command, params, success, error){
	success = (typeof(success) !== 'undefined' ? success : ab.log);
	error = (typeof(error) !== 'undefined' ? error : ab.log);
	if(typeof(params) == 'undefined')
		params = [];
	params.unshift(command);
	
	sess.call(topic, params).then(success, error);
}

function announce(topic, message){
	sess.publish(topic, ['announce', message], false);
}

function publish(topic, message){
	sess.publish(topic, ['message', message], false);
}

function getHost(){
	return location.protocol+"//"+location.host+"/";
}