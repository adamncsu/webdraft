<?php
namespace Drafter;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;

class WampDraft implements WampServerInterface {
	
	protected $topics = array();
	
    public function __construct() {
		date_default_timezone_set('America/New_York');
		$this->log("Server initialized");
    }
	
	public function onOpen(ConnectionInterface $conn) {
		$conn->User = new \StdClass;
		$conn->User->topics = new \SplObjectStorage;
		//$conn->User->drafts = array();
		$conn->User->name = $conn->WAMP->sessionId;
		
		$this->log($conn->WAMP->sessionId . " (". $conn->remoteAddress . ") connected");
	}
	public function onClose(ConnectionInterface $conn) {
		$this->log($conn->WAMP->sessionId . " (". $conn->remoteAddress . ") disconnected");
		
		// unsubscribe when user disconnects
		foreach($conn->User->topics as $i => $topic){
			$draftID = str_replace(':', '', $topic);
			$this->topics[$draftID]['users'][$conn->User->id]['ready'] = false;
			$this->onUnSubscribe($conn, $topic);
		}
	}
	
	public function onCall(ConnectionInterface $conn, $id, $topic, array $params){
		$draftID = explode(':', $topic)[1];
		
		switch($params[0]){
		
			// set user info
			case 'setUserInfo':
				$username = (string)$params[1];
				$userID = intval($params[2]);
					
				$conn->User->name = $this->escape($username);
				$conn->User->id = $userID;
				
				return $conn->callResult($id, array('sessionId' => $conn->WAMP->sessionId, 'name' => $conn->User->name));
			break;
			
			// update seat list (join)
			case 'updateSeats':
				$topic->broadcast(array('seatlist', $params[1]));
				return $conn->callResult($id);
			break;
			
			// begin draft initialization (join)
			case 'startDraft':
				$this->log($topic." - Starting draft");
				
				$seatIDs = $params[1];
				//$draftID = $params[2];
				
				// save draft info for everyone in draft
				foreach($seatIDs as $i => $uid)
					$this->initDraftInfo($uid, $topic);
					
				// prune old drafts from memory
				foreach($this->topics as $key => $val){
					if(time() - $this->topics[$key]['initDate'] > 604800){ // 1 week
						$this->log('Topic '.$key.' pruned ('.$this->topics[$key]['initDate'].')');
						unset($this->topics[$key]);
					}
				}
				
				$topic->broadcast(array('starting'));
				return $conn->callResult($id);
			break;
			
			// check-in to draft (live)
			case 'checkIn':
				//$draftID = explode(':',$topic)[1];
				
				// check if draft data has been initialized
				if(!isset($this->topics[$draftID]))
					return $conn->callError($id, 'init', 'Draft has not been initialized.');
					
				// check if subscribed yet
				if(!$topic->has($conn))
					return $conn->callError($id, 'subscribe', 'Error checking-in.');
				
				$this->log($topic." - ".$conn->User->name." checked in");
					
				// add this draft to user's draft list
				//if(!in_array($draftID, $conn->User->drafts))
				//	array_push($conn->User->drafts, $draftID);
				
				return $conn->callResult($id, array('readyData' => $this->topics[$draftID]['users']));
			break;
			
			// player is ready (live)
			case 'isReady':
				//$draftID = explode(':',$topic);
				$cardCount = $params[1];
				$this->topics[$draftID]['users'][$conn->User->id]['ready'] = true;
				$this->topics[$draftID]['users'][$conn->User->id]['cardCount'] = $cardCount;
				
				// check if others are ready
				$allReady = $this->allReady($topic);
				if($allReady){
					$topic->broadcast(array('allReady', $conn->User));
					
					// reset ready flag
					foreach($this->topics[$draftID]['users'] as $uid => $val)
						$this->topics[$draftID]['users'][$uid]['ready'] = false;
				}
				else
					$topic->broadcast(array('isReady', $conn->User));
				
				return $conn->callResult($id, array('allReady' => $allReady));
			break;
			
			// player is not ready (live)
			case 'notReady':
				//$draftID = $params[1];
				$this->topics[$draftID]['users'][$conn->User->id]['ready'] = false;
				return $conn->callResult($id);
			break;
			
			// end draft (live)
			case 'endDraft':
				//$draftID = $params[1];
				$this->log('Ending draft...');
				
				// reset ready flags for tables
				foreach($this->topics[$draftID]['users'] as $uid => $val)
					$this->topics[$draftID]['users'][$uid]['ready'] = false;
					
				return $conn->callResult($id);
			break;
			
			// build check-in (build)
			case 'startBuild':
				//$draftID = $params[1];
				
				if(!isset($this->topics[$draftID]['users'][$conn->User->id]['deck']))
					$this->initDraftInfo($conn->User->id, $topic);
				
				return $conn->callResult($id, array('deck' => $this->topics[$draftID]['users'][$conn->User->id]['deck'], 'lands' => $this->topics[$draftID]['users'][$conn->User->id]['lands']));
			break;
			
			// save deck (build)
			case 'saveDeck':
				//$draftID = $params[1];
				$this->topics[$draftID]['users'][$conn->User->id]['deck'] = $params[1];
				$this->topics[$draftID]['users'][$conn->User->id]['lands'] = $params[2];
				return $conn->callResult($id);
			break;
			
			// update table list (build)
			case 'updateTables':
				//$draftID = $params[1];
				$tablelist = $params[1];
				
				$this->topics[$draftID]['users'][$conn->User->id]['ready'] = false;
				$topic->broadcast(array('tablelist', $tablelist));
				return $conn->callResult($id);
			break;
			
			// set ready flag for game table
			case 'isReadyTable':
				//$draftID = $params[1];
				
				if(count($this->topics[$draftID]['users'][$conn->User->id]['deck']) > 0){
					$this->topics[$draftID]['users'][$conn->User->id]['ready'] = true;
					
					$topic->broadcast(array('isReady', $conn->User));
					return $conn->callResult($id);
				}
				else
					return $conn->callError($id, 'error', 'You must save your deck before starting the game.');
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
	
	public function onSubscribe(ConnectionInterface $conn, $topic) {
		$this->log($topic." - ".$conn->User->name." has subscribed");
		$conn->User->topics->attach($topic);
		$topic->broadcast(array('connection', $conn->User, 1));
	}
	public function onUnSubscribe(ConnectionInterface $conn, $topic) {
		$this->log($topic." - ".$conn->User->name." has unsubscribed");
		$conn->User->topics->detach($topic);
		$topic->broadcast(array('connection', $conn->User, 0));
	}

	public function onError(ConnectionInterface $conn, \Exception $e) {
		$this->log("Error - ".$e);
		$conn->close();
	}
	
	protected function initDraftInfo($id, $topic){
		$draftID = explode(':', $topic)[1];
		
		if(!isset($this->topics[$draftID])){
			$this->log('Initializing '.$topic);
			$this->topics[$draftID] = array();
			$this->topics[$draftID]['users'] = array();
			$this->topics[$draftID]['initDate'] = time();
		}
		if(!isset($this->topics[$draftID]['users'][$id])){
			$this->log('Initializing '.$topic.' -> User #'.$id);
			$this->topics[$draftID]['users'][$id] = array();
		}
		
		$this->topics[$draftID]['users'][$id]['ready'] = false;
		$this->topics[$draftID]['users'][$id]['cardCount'] = 0;
		$this->topics[$draftID]['users'][$id]['deck'] = [];
		$this->topics[$draftID]['users'][$id]['lands'] = [];
	}
	
	protected function allReady($topic){
		$draftID = explode(':', $topic)[1];
		
		//$this->log('Ready check...');
		foreach($this->topics[$draftID]['users'] as $id => $user){
			//$this->log('User '.$id.': '.($this->topics[$draftID][$id]['ready']?'ready':'not ready'));
			if(!$this->topics[$draftID]['users'][$id]['ready'])
				return false;
		}
		return true;
	}
	
	protected function escape($string) {
        return htmlspecialchars($string);
    }
	
	protected function log($message){
		echo date('Y-m-d H:i:s ') . (string)$message . "\n";
	}
}

?>