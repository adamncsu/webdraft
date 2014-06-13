<?php

class SetsController extends AppController {

    /*public function beforeFilter() {
        parent::beforeFilter();
        $this->Auth->allow(array('add', 'login', 'logout'));
    }*/
	
	public function isAuthorized($user){
		if(isset($user['role']) && $user['role'] === 'admin')
			return true;
			
		$this->Session->setFlash(__('You are not authorized to access that location.'));
		return false;
	}
	
	public function index() {
        $this->set('sets', $this->Set->find('all'));
    }

    public function add(){
		if($this->request->is('post')){
			$this->Set->create();
			if($this->Set->save($this->request->data)){
				$this->Session->setFlash('Set saved.');
				$this->redirect(array('action' => 'index'));
			}
			else{
				$this->Session->setFlash('Unable to save set.');
			}
		}
	}
	
	public function edit($id=null){
		if(!$id)
			throw new NotFoundException(__('Invalid set'));
			
		$set = $this->Set->findById($id);
		
		if(!$set)
			throw new NotFoundException(__('Invalid set'));
		
		if($this->request->is('post') || $this->request->is('put')){
			$this->Set->id = $id;
			if($this->Set->save($this->request->data)){
				$this->Session->setFlash('Set updated.');
				$this->redirect(array('action' => 'index'));
			}
			else
				$this->Session->setFlash('Unable to update set.');
		}
		
		if(!$this->request->data){
			$this->request->data = $set;
		}
	}
	
	public function delete($id){
		if ($this->request->is('get')) 
			throw new MethodNotAllowedException();
			
		if ($this->Set->delete($id)) {
			$this->Session->setFlash('The set with id: ' . $id . ' has been deleted.');
			$this->redirect(array('action' => 'index'));
		}
	}
}

?>