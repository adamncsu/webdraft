<?php
namespace Drafter;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;

class WampDraft implements WampServerInterface {
	
	protected $drafts = array();
	
    public function __construct() {
		date_default_timezone_set('America/New_York');
		$this->log("Server initialized");
    }
	
	public function onOpen(ConnectionInterface $conn) {
		$conn->User = new User($conn->WAMP->sessionId);
		
		$this->log($conn->WAMP->sessionId . " (". $conn->remoteAddress . ") connected");
	}
	public function onClose(ConnectionInterface $conn) {
		$this->log($conn->WAMP->sessionId . " (". $conn->remoteAddress . ") disconnected");
		
		// force unsubscribe when user disconnects & set ready flag
		foreach($conn->User->topics as $i => $topic){
			$draftID = explode(':', $topic)[1];
			$this->drafts[$draftID]->players[$conn->User->id]->ready = false;
			$this->onUnSubscribe($conn, $topic);
		}
	}
	
	public function onSubscribe(ConnectionInterface $conn, $topic) {
		$this->log($topic." - ".$conn->User->name." has subscribed");
		$conn->User->joinTopic($topic);
		$topic->broadcast(array('connection', $conn->User, 1));
	}
	public function onUnSubscribe(ConnectionInterface $conn, $topic) {
		$this->log($topic." - ".$conn->User->name." has unsubscribed");
		$conn->User->leaveTopic($topic);
		$topic->broadcast(array('connection', $conn->User, 0));
	}
	
	public function onCall(ConnectionInterface $conn, $id, $topic, array $params){
		$draftID = explode(':', $topic)[1];
		
		switch($params[0]){
		
		/*----- GLOBAL ------*/
		
			// set user info
			case 'setUserInfo':
				$conn->User->setUserInfo($params[2], $params[1]);
				return $conn->callResult($id);
			break;
			
			
			
		/*----- JOIN DRAFT ------*/
		
			// update seat list
			case 'updateSeats':
				$topic->broadcast(array('seatlist', $params[1]));
				return $conn->callResult($id);
			break;
			
			// begin draft initialization
			case 'startDraft':
				$this->log($topic." - Starting draft");
				
				$seatIDs = $params[1];
				
				$this->drafts[$draftID] = new Draft($topic, $seatIDs);
					
				// prune old drafts from memory
				foreach($this->drafts as $key => $val){
					if(time() - $this->drafts[$key]->initDate > 604800){ // 1 week
						$this->log('Topic '.$key.' pruned ('.$this->drafts[$key]->initDate.')');
						unset($this->drafts[$key]);
					}
				}
				
				$topic->broadcast(array('starting'));
				return $conn->callResult($id);
			break;
			
			
			
		
		/*------ LIVE DRAFT ------*/
		
			// check-in to draft
			case 'checkIn':
			
				// check if draft data has been initialized
				if(!isset($this->drafts[$draftID]))
					return $conn->callError($id, 'init', 'Draft has not been initialized.');
					
				// check if subscribed yet
				if(!$topic->has($conn))
					return $conn->callError($id, 'subscribe', 'Error checking-in.');
				
				$this->log($topic." - ".$conn->User->name." checked in");
					
				return $conn->callResult($id, array('readyData' => $this->drafts[$draftID]->players));
			break;
			
			// player drafted a card and is ready
			case 'drafted':
				$this->drafts[$draftID]->players[$conn->User->id]->drafted($params[1]);
				
				// check if others are ready
				$allReady = $this->drafts[$draftID]->readyCheck();
				
				if($allReady){
					$topic->broadcast(array('allReady', $conn->User));
					$this->drafts[$draftID]->newRound();
				}
				else
					$topic->broadcast(array('isReady', $conn->User));
				
				return $conn->callResult($id, array('allReady' => $allReady));
			break;
			
			// player is ready
			case 'isReady':
				$this->drafts[$draftID]->players[$conn->User->id]->ready = true;
				return $conn->callResult($id);
			break;
			
			// player is not ready
			case 'notReady':
				$this->drafts[$draftID]->players[$conn->User->id]->ready = false;
				return $conn->callResult($id);
			break;
			
			// end draft (live)
			case 'endDraft':
				$this->log('Ending draft...');
				$this->drafts[$draftID]->endDraft();
					
				return $conn->callResult($id);
			break;
		
		
		
		/*------ POST-DRAFT ------*/
		
			// build check-in
			case 'startBuild':
				if(!isset($this->drafts[$draftID]))
					$this->drafts[$draftID] = new Draft($topic);
				if(!isset($this->drafts[$draftID]->players[$conn->User->id]->deck))
					$this->drafts[$draftID]->players[$conn->User->id] = new Player($conn->User->id);
				
				return $conn->callResult($id, array('player' => $this->drafts[$draftID]->players[$conn->User->id]));
			break;
			
			// save deck
			case 'saveDeck':
				$this->drafts[$draftID]->players[$conn->User->id]->saveDeck($params[1], $params[2]);
				return $conn->callResult($id);
			break;
			
			// update table list
			case 'updateTables':
				$this->drafts[$draftID]->players[$conn->User->id]->leaveTable();
				$topic->broadcast(array('tablelist', $params[1]));
				return $conn->callResult($id);
			break;
			
			// set ready flag for game table
			case 'isReadyTable':
				$tableID = explode(':', $topic)[1];
				$draftID = $params[1];
				
				if(count($this->drafts[$draftID]->players[$conn->User->id]->deck) == 0)
					return $conn->callError($id, 'error', 'You must save your deck before starting the game.');
				else{
					$this->drafts[$draftID]->players[$conn->User->id]->setTable($tableID);
					
					$tableReady = $this->drafts[$draftID]->tableReadyCheck($tableID);
					if($tableReady)
						$topic->broadcast(array('allReady'));
					else
						$topic->broadcast(array('isReady', $conn->User));
						
					return $conn->callResult($id, array('tableReady' => $tableReady));
				}
			break;
			
			
			
		/*------ GAME PLAY ------*/	
			
			// join game
			case 'joinGame':
				$draftID = $params[1];
				
				if(!isset($this->drafts[$draftID]))
					$this->drafts[$draftID] = new Draft($topic);
				if(!isset($this->drafts[$draftID]->players[$conn->User->id]->deck))
					$this->drafts[$draftID]->players[$conn->User->id] = new Player($conn->User->id);
					
				return $conn->callResult($id, array('deck' => $this->drafts[$draftID]->players[$conn->User->id]->deck, 'lands' => $this->drafts[$draftID]->players[$conn->User->id]->lands));
			break;
			
			
			default:
				return $conn->callError($id, $params[0], 'Call to undefined procedure');
			break;
		}
	}
	
	public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible){
		if(count($event) < 2)
			return;
		$messageType = $event[0];
		$message = (string)$event[1];
		if(empty($message))
			return;
		
		$topic->broadcast(array($messageType, $conn->User, $message));
	}

	public function onError(ConnectionInterface $conn, \Exception $e) {
		$this->log("Error - ".$e);
		$conn->close();
	}
	
	
	protected function escape($string) {
        return htmlspecialchars($string);
    }
	
	protected function log($message){
		echo date('Y-m-d H:i:s ') . (string)$message . "\n";
	}
}

?>