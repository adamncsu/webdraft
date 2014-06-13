<?php
namespace Drafter;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;

class Draft{
	
	public $initDate;
	public $players;
	public $draftID;
	
	public function __construct($topic, $seatIDs=null) {
		$this->log('Initializing '.$topic);
		$this->draftID = explode(':', $topic)[1];
		$this->players = array();
		$this->initDate = time();
		
		if(!is_null($seatIDs)){
			$this->log($seatIDs);
			foreach($seatIDs as $i => $uid)
				$this->players[$uid] = new Player($uid);
		}
    }
	
	public function readyCheck(){
		foreach($this->players as $uid => $player){
			if(!$player->ready)
				return false;
		}
		return true;
	}
	
	public function tableReadyCheck($tableID){
		$readyCount = 0;
		
		foreach($this->players as $uid => $player){
			if($player->tableID == $tableID && $player->ready)
				$readyCount++;
		}
		
		return ($readyCount == 2);
	}
	
	public function newRound(){
		$this->resetReady();
	}
	
	public function endDraft(){
		$this->resetReady();
	}
	
	public function resetReady(){
		foreach($this->players as $uid => $player)
			$this->players[$uid]->ready = false;
	}
	
	protected function log($message){
		echo date('Y-m-d H:i:s ') . (string)$message . "\n";
	}
}

?>