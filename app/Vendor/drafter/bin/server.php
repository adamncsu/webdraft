<?php
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Wamp\WampServer;
use Drafter\WampDraft;

    require dirname(dirname(__DIR__)) . '/autoload.php';
	
    $server = IoServer::factory(
        new WsServer(
			new WampServer(
				new WampDraft
			)
        ),
		8080
    );

    $server->run();
?>