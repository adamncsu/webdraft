<?php

class CardsController extends AppController{

	public function beforeFilter(){
		//$this->Security->requirePost();
	}
	
	public function addPack(){
		if(!$this->request->is('post'))
			throw new MethodNotAllowedException(__('Illegal request'));
	
		$this->Card->bindModel(array('belongsTo' => array('Pack')));
		$response = array('success'=>true, 'message'=>'', 'cards'=>'');
		
		// get post data
		$cardNumbers = $this->request->data['cards'];
		$draftID = $this->request->data['draftID'];
		
		// nullify any old packs you have in this draft
		$this->Card->Pack->recursive = -1;
		$oldpacks = $this->Card->Pack->find('all', array(
			'conditions'=>array('Pack.draft_id'=>$draftID, 'Pack.user_id'=>$this->Auth->user('id')),
			'fields'=>array('Pack.id', 'Pack.draft_id', 'Pack.user_id')
		));
		if(count($oldpacks) > 0){
			foreach($oldpacks as $key => $oldpack)
				$oldpacks[$key]['Pack']['user_id'] = null;
			$this->Card->Pack->saveMany($oldpacks);
		}
		
		// delete any old packs you have in this draft
		//$this->Card->Pack->deleteAll(array('Pack.draft_id'=>$draft_ID, 'Pack.user_id'=>$this->Auth->user('id')), false);
		
		// create a new pack
		$this->Card->Pack->create();
		$this->Card->Pack->save(array('Pack'=>array('draft_id'=>$draftID, 'user_id'=>$this->Auth->user('id'))));
		$packID = $this->Card->Pack->getInsertId();
		
		// create and save card array
		$data = array();
		foreach($cardNumbers as $num){
			array_push($data, array('number'=>$num, 'pack_id'=>$packID));
		}
		$this->Card->saveMany($data);
		
		// load saved cards (need IDs)
		$cards = $this->Card->find('all', array(
			'conditions' => array('pack_id' => $packID),
			'recursive' => -1,
			'fields' => array('id', 'number', 'pack_id')
		));
		$response['cards'] = $cards;
		
		$this->set('data', $response);
		$this->render('/Pages/json', 'json');
	}
	
	public function draftCard(){
		if(!$this->request->is('post'))
			throw new MethodNotAllowedException(__('Illegal request'));
			
		$response = array('success'=>true, 'message'=>'');
		
		// get post data
		$cardID = $this->request->data['cardID'];
		
		/*$data = array('user_id' => $this->Auth->user('id'), 'pack_id' => null);
		$this->Card->recursive = -1;
		$this->Card->id = $cardID;
		if(!$this->Card->save($data)){
			$response['success'] = false;
			$response['message'] = 'Database error. Try again.';
		}*/
		$this->Card->id = $cardID;
		if(!$this->Card->saveField('user_id', $this->Auth->user('id'))){
			$response['success'] = false;
			$response['message'] = 'Database error. Try again.';
		}
		
		$this->set('data', $response);
		$this->render('/Pages/json', 'json');
	}
	
	public function draftedCards($id){
		$this->Card->bindModel(array('belongsTo' => array('Pack')));
		
		$cards = $this->Card->find('all', array(
			'conditions' => array('Pack.draft_id'=>$id, 'Card.user_id'=>$this->Auth->user('id')),
			'fields' => array('Card.id', 'Card.number', 'Card.user_id', 'Pack.id', 'Pack.draft_id', 'Card.modified'),
			'order' => array('Card.modified' => 'asc')
		));
	
		$this->set('data', $cards);
		$this->render('/Pages/json', 'json');
	}
	
	public function userPack(){
		if(!$this->request->is('post'))
			throw new MethodNotAllowedException(__('Illegal request'));
			
		$response = array('success'=>true, 'message'=>'');
		
		// get post data
		$userID = $this->request->data['playerID'];
		$draftID = $this->request->data['draftID'];
	
		$this->Card->bindModel(array('belongsTo' => array('Pack')));
		
		$this->Card->Pack->recursive = -1;
		$pack = $this->Card->Pack->find('first', array('conditions'=>array('Pack.user_id'=>$userID, 'Pack.draft_id'=>$draftID)));
		
		if(isset($pack['Pack'])){
			$cards = $this->Card->find('all', array(
				'conditions' => array('Card.pack_id' => $pack['Pack']['id'], 'Card.user_id' => null),
				'recursive' => -1,
				'fields' => array('Card.id', 'Card.number', 'Card.pack_id')
			));
			$response['cards'] = $cards;
		}
		else
			$response['success'] = false;
		
		$this->set('data', $response);
		$this->render('/Pages/json', 'json');
	}
}

?>