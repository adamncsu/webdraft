<?php

class DraftsController extends AppController{

	public function beforeFilter() {
        parent::beforeFilter();
        if($this->action != 'delete')
			$this->Draft->unbindModel(array('hasMany' => array('Pack', 'Table')));
    }
	
	public function isAuthorized($user){
		// only the host can delete a draft
		if(in_array($this->action, array('delete'))) {
			$draftId = $this->request->params['pass'][0];
			
			if((isset($user['role']) && $user['role'] === 'admin') || $this->Draft->isOwnedBy($draftId, $user['id'])){
				return true;
			}
			else{
				$this->Session->setFlash("Only the host may delete this draft.");
				return false;
			}
		}

		return parent::isAuthorized($user);
	}
	
	public function index(){
		$this->Draft->unbindModel(array('belongsTo' => array('Seat1','Seat2','Seat3','Seat4','Seat5','Seat6','Seat7','Seat8')));
		$this->Draft->bindModel(array('belongsTo' => array(
			'Set1'  => array('className'=>'Set', 'foreignKey'=>'set1_id'),
			'Set2'  => array('className'=>'Set', 'foreignKey'=>'set2_id'),
			'Set3'  => array('className'=>'Set', 'foreignKey'=>'set3_id')
		)));
		
		$this->set('drafts', $this->Draft->find('all', array(
			'fields' => array('Host.id', 'Host.username', 'Draft.id', 'Draft.status', 'Draft.created', 'Set1.shortname', 'Set2.shortname', 'Set3.shortname'),
			'order' => array('Draft.created DESC')
		)));
	}
	
	public function create(){
		$this->Draft->bindModel(array('belongsTo' => array('Set')));
		$sets = $this->Draft->Set->find('list', array(
			'fields' => array('Set.id', 'Set.name'),
			'recursive' => -1
		));
		$this->set('sets', $sets);
		
		if($this->request->is('post')){
			$this->Draft->create();
			$this->request->data['Draft']['user_id'] = $this->Auth->user('id');
			
			if($this->Draft->save($this->request->data)){
				//$this->Session->setFlash('Draft created.');
				$this->redirect(array('action' => 'join', $this->Draft->getInsertId()));
			}
			else{
				$this->Session->setFlash('Unable to create draft');
			}
		}
	}
	
	public function join($id){
		$this->layout = "draft";
	
		if(!$id)
			throw new NotFoundException(__('Invalid draft'));
		
		$draft = $this->Draft->find('first', 
			array(
				'conditions' => array('Draft.id' => $id),
				'fields' => array('Draft.id', 'Draft.status', 'Host.id', 'Host.username', 'Seat1.id', 'Seat1.username', 
					'Seat2.id', 'Seat2.username', 'Seat3.id', 'Seat3.username', 'Seat4.id', 'Seat4.username', 
					'Seat5.id', 'Seat5.username', 'Seat6.id', 'Seat6.username', 'Seat7.id', 'Seat7.username', 
					'Seat8.id', 'Seat8.username', 'Draft.created')
			)
		);
		
		if(!$draft)
			throw new NotFoundException(__('Invalid draft'));
			
		if($draft['Draft']['status'] != 0){
			$this->Session->setFlash('Draft can no longer be joined.');
			$this->redirect(array('action' => 'index'));
		}
			
		$this->set('draft', $draft);
	}
	
	public function seatlist($id){
		if(!$id)
			throw new NotFoundException(__('Invalid draft'));
			
		$draft = $this->Draft->find('first', 
			array(
				'conditions' => array('Draft.id' => $id),
				'fields' => array('Draft.id', 'Seat1.id', 'Seat1.username', 'Seat2.id', 'Seat2.username', 
					'Seat3.id', 'Seat3.username', 'Seat4.id', 'Seat4.username', 'Seat5.id', 'Seat5.username', 
					'Seat6.id', 'Seat6.username', 'Seat7.id', 'Seat7.username', 'Seat8.id', 'Seat8.username')
			)
		);
		
		$this->set('data', $draft);
		$this->render('/Pages/json', 'json');
	}
	
	public function updateStatus(){
		if(!$this->request->is('post'))
			throw new MethodNotAllowedException(__('Illegal request'));
			
		$response = array('success'=>true, 'message'=>'');
		
		// get post data
		$status = $this->request->data['status'];
		$draftID = $this->request->data['draftID'];
		
		// save status
		$this->Draft->id = $draftID;
		$this->Draft->recursive = -1;
		$draft = $this->Draft->saveField('status', $status);
		$response['draft'] = $draft;
		
		$this->set('data', $response);
		$this->render('/Pages/json', 'json');
	}
	
	public function sit(){
		if(!$this->request->is('post'))
			throw new NotFoundException();
		
		$draftID = intval($this->request->data['draft']);
		$userID = $this->Auth->user('id');
		$username = $this->Auth->user('username');
		$seatNum = $this->request->data['seat'];
		
		$response = array('success'=>true, 'message'=>'');
		
		$draft = $this->Draft->find('first', array(
			'conditions' => array('Draft.id' => $draftID), 
			'fields' => array('Draft.id', 'Draft.status', 'Seat1.id', 'Seat1.username', 'Seat2.id', 'Seat2.username', 
					'Seat3.id', 'Seat3.username', 'Seat4.id', 'Seat4.username', 'Seat5.id', 'Seat5.username', 
					'Seat6.id', 'Seat6.username', 'Seat7.id', 'Seat7.username', 'Seat8.id', 'Seat8.username')
		));
		$seatArray = array($draft['Seat1']['id'], $draft['Seat2']['id'], $draft['Seat3']['id'], $draft['Seat4']['id'], 
			$draft['Seat5']['id'], $draft['Seat6']['id'], $draft['Seat7']['id'], $draft['Seat8']['id']);
		
		
		// draft has already started
		if($draft['Draft']['status'] != 0){
			$response['success'] = false;
			$response['message'] = 'Draft is already in progress, or has completed';
		}
			
		// seat is occupied
		else if($draft['Seat'.$seatNum]['id'] != null){
			$response['success'] = false;
			$response['message'] = 'Seat '.$seatNum.' is already taken';
		}
		
		// swapped seats, need to make old seat null
		else if(in_array($userID, $seatArray)){
			$oldSeat = strval(array_search($userID, $seatArray) + 1);
			$draft['Draft']['seat'.$oldSeat.'_id'] = null;
			$draft['Seat'.$oldSeat]['id'] = null;
			$draft['Seat'.$oldSeat]['username'] = null;
		}
		
		// save draft data
		if($response['success']){
			$draft['Draft']['seat'.$seatNum.'_id'] = $userID;
			$draft['Seat'.$seatNum]['id'] = $userID;
			$draft['Seat'.$seatNum]['username'] = $username;
			$this->Draft->save($draft['Draft']);
		}
		
		$draft['response'] = $response;
		//CakeLog::write('debug', $draft);
		
		$this->set('data', $draft);
		$this->render('/Pages/json', 'json');
	}
	
	public function stand(){
		if(!$this->request->is('post'))
			throw new NotFoundException();
		
		$draftID = intval($this->request->data['draft']);
		$userID = $this->Auth->user('id');
		
		$response = array('success'=>true, 'message'=>'');
		
		$draft = $this->Draft->find('first', array(
			'conditions' => array('Draft.id' => $draftID), 
			'fields' => array('Draft.id', 'Draft.status', 'Seat1.id', 'Seat1.username', 'Seat2.id', 'Seat2.username', 
					'Seat3.id', 'Seat3.username', 'Seat4.id', 'Seat4.username', 'Seat5.id', 'Seat5.username', 
					'Seat6.id', 'Seat6.username', 'Seat7.id', 'Seat7.username', 'Seat8.id', 'Seat8.username')
		));
		$seatArray = array($draft['Seat1']['id'], $draft['Seat2']['id'], $draft['Seat3']['id'], $draft['Seat4']['id'], 
			$draft['Seat5']['id'], $draft['Seat6']['id'], $draft['Seat7']['id'], $draft['Seat8']['id']);
		
		
		// draft has already started
		if($draft['Draft']['status'] != 0){
			$response['success'] = false;
			$response['message'] = 'Draft is already in progress, or has completed';
		}
		
		// user isn't currently seated
		else if(!in_array($userID, $seatArray)){
			$response['success'] = false;
			$response['message'] = 'Unable to clear seat';
		}
		
		// clear seat and save draft data
		else{
			$oldSeat = strval(array_search($userID, $seatArray) + 1);
			$draft['Draft']['seat'.$oldSeat.'_id'] = null;
			$draft['Seat'.$oldSeat]['id'] = null;
			$draft['Seat'.$oldSeat]['username'] = null;
			$this->Draft->save($draft['Draft']);
		}
		
		$draft['response'] = $response;
		
		$this->set('data', $draft);
		$this->render('/Pages/json', 'json');
	}
	
	public function init($id){
		if(!$this->request->is('post'))
			throw new MethodNotAllowedException(__('Error initializing draft.'));
			
		$draft = $this->Draft->find('first', array(
			'conditions' => array('Draft.id' => $id), 
			'recursive' => -1
		));
		
		$response = array('success'=>true, 'message'=>'');
		
		// draft has already started
		if($draft['Draft']['status'] != 0){
			$response['success'] = false;
			$response['message'] = 'Draft is already in progress, or has completed';
		}
		
		// user is not the host
		else if($this->Auth->user('id') != $draft['Draft']['user_id']){
			$response['success'] = false;
			$response['message'] = 'Only the host can begin the draft';
		}
		
		// initialize draft
		else{
			// move all users to front of list
			/*$seatArray = [];
			foreach($draft['Draft'] as $key => $value){
				if(substr($key, 0, 4) == 'seat' && $value != null)
					array_push($seatArray, $value);
			}	
			for($i=0; $i<8; $i++)
				$draft['Draft']['seat'.($i+1).'_id'] = $i<count($seatArray) ? $seatArray[$i] : null;
			
			// set draft status
			$draft['Draft']['status'] = 1;
			
			// save draft data
			$this->Draft->save($draft['Draft']);*/
			$this->Draft->id = $id;
			$this->Draft->saveField('status', 1);
		}
		
		$this->set('data', $response);
		$this->render('/Pages/json', 'json');
	}
	
	public function live($id){
		$this->layout = "draft";
		
		if(!$id)
			throw new NotFoundException(__('Invalid draft'));
			
		$this->Draft->bindModel(array('belongsTo' => array(
			'Set1'  => array('className'=>'Set', 'foreignKey'=>'set1_id'),
			'Set2'  => array('className'=>'Set', 'foreignKey'=>'set2_id'),
			'Set3'  => array('className'=>'Set', 'foreignKey'=>'set3_id')
		)));
		$draft = $this->Draft->find('first', 
			array(
				'conditions' => array('Draft.id' => $id),
				'fields' => array('Draft.id', 'Draft.status', 'Host.id', 'Host.username', 'Seat1.id', 'Seat1.username', 
					'Seat2.id', 'Seat2.username', 'Seat3.id', 'Seat3.username', 'Seat4.id', 'Seat4.username', 
					'Seat5.id', 'Seat5.username', 'Seat6.id', 'Seat6.username', 'Seat7.id', 'Seat7.username', 
					'Seat8.id', 'Seat8.username', 'Draft.created', 'Set1.name', 'Set2.name', 'Set3.name')
			)
		);
		$seatArray = array($draft['Seat1']['id'], $draft['Seat2']['id'], $draft['Seat3']['id'], $draft['Seat4']['id'], 
			$draft['Seat5']['id'], $draft['Seat6']['id'], $draft['Seat7']['id'], $draft['Seat8']['id']);
		
		if(!$draft)
			throw new NotFoundException(__('Invalid draft'));
			
		// draft has not started
		if($draft['Draft']['status'] == 0){
			$this->Session->setFlash('Draft has not yet begun.');
			$this->redirect(array('action' => 'index'));
		}
		
		// draft has completed
		else if($draft['Draft']['status'] == 3){
			$this->Session->setFlash('Draft has already completed.');
			$this->redirect(array('action' => 'index'));
		}
		
		// user is not in the draft
		else if(!in_array($this->Auth->user('id'), $seatArray)){
			$this->Session->setFlash('You are not seated at this draft.');
			$this->redirect(array('action' => 'index'));
		}
			
		$this->set('draft', $draft);
		$this->set('seatArray', $seatArray);
	}
	
	public function build($id){
		$this->layout = "draft";
		
		if(!$id)
			throw new NotFoundException(__('Invalid draft'));
		
		$this->Draft->bindModel(array('belongsTo' => array(
			'Set1'  => array('className'=>'Set', 'foreignKey'=>'set1_id'),
			'Set2'  => array('className'=>'Set', 'foreignKey'=>'set2_id'),
			'Set3'  => array('className'=>'Set', 'foreignKey'=>'set3_id')
		)));
		$draft = $this->Draft->find('first', 
			array(
				'conditions' => array('Draft.id' => $id),
				'fields' => array('Draft.id', 'Draft.status', 'Host.id', 'Host.username', 'Seat1.id', 'Seat1.username', 
					'Seat2.id', 'Seat2.username', 'Seat3.id', 'Seat3.username', 'Seat4.id', 'Seat4.username', 
					'Seat5.id', 'Seat5.username', 'Seat6.id', 'Seat6.username', 'Seat7.id', 'Seat7.username', 
					'Seat8.id', 'Seat8.username', 'Draft.created', 'Set1.name', 'Set2.name', 'Set3.name')
			)
		);
		$seatArray = array($draft['Seat1']['id'], $draft['Seat2']['id'], $draft['Seat3']['id'], $draft['Seat4']['id'], 
			$draft['Seat5']['id'], $draft['Seat6']['id'], $draft['Seat7']['id'], $draft['Seat8']['id']);
		
		if(!$draft)
			throw new NotFoundException(__('Invalid draft'));
			
		// draft has not started
		if($draft['Draft']['status'] < 3){
			$this->Session->setFlash('Draft has not ended.');
			$this->redirect(array('action' => 'index'));
		}
		
		// user is not in the draft
		else if(!in_array($this->Auth->user('id'), $seatArray)){
			$this->Session->setFlash('You are not seated at this draft.');
			$this->redirect(array('action' => 'index'));
		}
		
		// create game tables, if they don't exist
		$this->Draft->Table->recursive = -1;
		$tableCount = $this->Draft->Table->find('count', array('conditions' => array('Table.draft_id' => $id)));
		if($tableCount == 0){
			// create tables based on # of players
			$tabledata = [];
			for($i=0; $i<ceil(count(array_filter($seatArray))/2); $i++)
				array_push($tabledata, array('draft_id' => $id));
				
			$this->Draft->Table->create();
			$this->Draft->Table->saveMany($tabledata);
		}
		
		$this->set('draft', $draft);
		//$this->set('tables', $tables);
	}
	
	public function cod(){
		if(!$this->request->is('post'))
			throw new MethodNotAllowedException(__('Illegal request'));
			
		$deck = $this->request->data['deck'];
		//$deck = html_entity_decode(preg_replace("/%u([0-9a-f]{3,4})/i", "&#x\\1;", urldecode($deck)), null, 'UTF-8');
		
		$this->layout = 'json';
		$this->set('data', $deck);
	}
	
	public function delete($id){
		if ($this->request->is('get')) 
			throw new MethodNotAllowedException();
			
		if ($this->Draft->delete($id, true)) {
			$this->Session->setFlash('Draft #' . $id . ' has been deleted.');
			$this->redirect(array('action' => 'index'));
		}
	}
}

?>