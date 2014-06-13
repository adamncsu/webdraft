<?php
namespace Drafter;

class Player {
	
	public $id;
	public $ready;
	public $cardCount;
	public $deck;
	public $lands;
	public $tableID;
	
	
	public function __construct($id) {
		$this->id = $id;
		$this->ready = false;
		$this->cardCount = 0;
		$this->deck = [];
		$this->lands = [];
		$this->tableID = null;
    }
	
	public function drafted($cardCount){
		$this->ready = true;
		$this->cardCount = $cardCount;
	}
	
	public function saveDeck($deck, $lands){
		$this->deck = $deck;
		$this->lands = $lands;
	}
	
	public function setTable($tableID){
		$this->tableID = intval($tableID);
		$this->ready = true;
	}
	public function leaveTable(){
		$this->tableID = null;
		$this->ready = false;
	}
	
	protected function escape($string) {
        return htmlspecialchars($string);
    }
	
	protected function log($message){
		echo date('Y-m-d H:i:s ') . (string)$message . "\n";
	}
}

?>