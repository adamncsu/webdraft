<?php
namespace Drafter;

class User {
	
	public $topics;
	public $name;
	public $id;
	
    public function __construct($name) {
		$this->topics = new \SplObjectStorage;
		$this->name = $name;
    }
	
	public function setUserInfo($id, $name){
		$this->id = intval($id);
		$this->name = $this->escape((string)$name);
	}
	
	public function joinTopic($topic){
		$this->topics->attach($topic);
	}
	public function leaveTopic($topic){
		$this->topics->detach($topic);
	}
	
	protected function escape($string) {
        return htmlspecialchars($string);
    }
	
	protected function log($message){
		echo date('Y-m-d H:i:s ') . (string)$message . "\n";
	}
}

?>