<?php

class TablesController extends AppController{

	public function beforeFilter() {
        parent::beforeFilter();
		if(in_array($this->action, array('play')))
			$this->Components->unload('DebugKit.Toolbar');
    }

	
	public function play($id){
		if(!$id){
			throw new NotFoundException(__('Invalid game'));
		}
		$this->Table->bindModel(array('belongsTo' => array('Draft')));
		$this->Table->Draft->bindModel(array('belongsTo' => array('Set')));
		$table = $this->Table->find('first', array(
			'conditions' => array('Table.id' => $id),
			'fields' => array('Table.id', 'Table.draft_id', 'Table.status', 'Seat1.username', 'Seat2.username', 'Draft.set_id')
		));
		
		// todo: combine these queries
		$draft = $this->Table->Draft->find('first', array(
			'conditions' => array('Draft.id' => $table['Table']['draft_id']),
			'fields' => array('Set.name'),
			'recursive' => 0
		));
		
		$this->layout = 'play';
		$this->set('setName', $draft['Set']['name']);
		$this->set('table', $table);
	}
	
	public function info($id){
		$this->Table->recursive = -1;
		$table = $this->Table->findById($id);
	
		$this->layout = null;
		$this->set('data', $table);
		$this->render('/Pages/json');
	}
	
	public function all($id){
		$tables = $this->Table->find('all', array(
			'conditions' => array('Table.draft_id' => $id),
			'fields' => array('Table.id', 'Table.draft_id', 'Table.status', 'Seat1.id', 'Seat2.id', 'Seat1.username', 'Seat2.username')
		));
	
		$this->set('data', $tables);
		$this->render('/Pages/json', 'json');
	}
	
	public function sit(){
		if(!$this->request->is('post'))
			throw new NotFoundException();
		
		$tableID = intval($this->request->data['tableID']);
		$draftID = intval($this->request->data['draftID']);
		$userID = $this->Auth->user('id');
		$username = $this->Auth->user('username');
		
		$response = array('success'=>true, 'message'=>'');
		
		$tables = $this->Table->find('all', array(
			'conditions' => array('Table.draft_id' => $draftID),
			'fields' => array('Table.id', 'Table.status', 'Seat1.id', 'Seat1.username', 'Seat2.id', 'Seat2.username'),
		));
		$seatArray = [];
		$tableIndex = 0;
		foreach($tables as $i => $table){
			array_push($seatArray, $table['Seat1']['id'], $table['Seat2']['id']);
			if(intval($table['Table']['id']) === $tableID)
				$tableIndex = $i;
		}
		
		// check if user is already seated at a table
		if(in_array($userID, $seatArray))
			$response = array('success' => false, 'message' => 'You are already seated');
			
		// check if table is occupied
		else if(!is_null($tables[$tableIndex]['Seat1']['id']) && !is_null($tables[$tableIndex]['Seat2']['id']))
			$response = array('success' => false, 'message' => 'Table is full');
			
		// check if table status is not 0
		else if($tables[$tableIndex]['Table']['status'] != 0)
			$response = array('success' => false, 'message' => 'Game has already started');
		
		else{
			$openIndex = is_null($tables[$tableIndex]['Seat1']['id']) ? '1' : '2';
			
			$tables[$tableIndex]['Seat'.$openIndex]['id'] = $userID;
			$tables[$tableIndex]['Seat'.$openIndex]['username'] = $username;
			
			$table = array('Table' => array('id' => $tableID, 'seat'.$openIndex.'_id' => $userID));
			$this->Table->save($table['Table']);
		}
		
		$response['tables'] = $tables;
		
		$this->set('data', $response);
		$this->render('/Pages/json', 'json');
	}
	
	public function stand(){
		if(!$this->request->is('post'))
			throw new NotFoundException();
		
		$draftID = intval($this->request->data['draftID']);
		$userID = $this->Auth->user('id');
		$username = $this->Auth->user('username');
		
		$response = array('success'=>true, 'message'=>'');
		
		$tables = $this->Table->find('all', array(
			'conditions' => array('Table.draft_id' => $draftID),
			'fields' => array('Table.id', 'Table.status', 'Seat1.id', 'Seat1.username', 'Seat2.id', 'Seat2.username'),
		));
		
		$tableIndex = -1;
		foreach($tables as $i => $table){
			if($table['Seat1']['id'] == $userID)
				$tableIndex = $i;
			else if($table['Seat2']['id'] == $userID)
				$tableIndex = $i;
		}
		
		if($tableIndex < 0)
			$response = array('success' => false, 'message' => "You aren't seated at this table");
			
		else{
			$this->Table->id = $tables[$tableIndex]['Table']['id'];
			$this->Table->recursive = -1;
			if($tables[$tableIndex]['Seat1']['id'] == $userID){
				$tables[$tableIndex]['Seat1']['id'] = null;
				$tables[$tableIndex]['Seat1']['username'] = null;
				$this->Table->saveField('seat1_id', null);
			}
			else if($tables[$tableIndex]['Seat2']['id'] == $userID){
				$tables[$tableIndex]['Seat2']['id'] = null;
				$tables[$tableIndex]['Seat2']['username'] = null;
				$this->Table->saveField('seat2_id', null);
			}
		}
		
		$response['tables'] = $tables;
		
		$this->set('data', $response);
		$this->render('/Pages/json', 'json');
	}
	
	
}

?>