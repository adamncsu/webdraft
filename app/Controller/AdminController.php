<?php

class AdminController extends AppController{
	public $uses = array('Setting');

	public function isAuthorized($user){
		if(isset($user['role']) && $user['role'] === 'admin')
			return true;
			
		$this->Session->setFlash(__('You are not authorized to access that location.'));
		return false;
	}
	
	public function index(){
		$this->set('settings', $this->Setting->find('all'));
	}
	
	public function add(){
		if($this->request->is('post')){
			$this->Setting->create();
			if($this->Setting->save($this->request->data)){
				$this->Session->setFlash('Settings saved.');
				$this->redirect(array('action' => 'index'));
			}
			else{
				$this->Session->setFlash('Unable to save settings.');
			}
		}
	}
	
	public function edit($id=null){
		if(!$id)
			throw new NotFoundException(__('Invalid property'));
			
		$setting = $this->Setting->findById($id);
		
		if(!$setting)
			throw new NotFoundException(__('Invalid post'));
		
		if($this->request->is('post') || $this->request->is('put')){
			$this->Setting->id = $id;
			if($this->Setting->save($this->request->data)){
				$this->Session->setFlash('Settings updated.');
				$this->redirect(array('action' => 'index'));
			}
			else
				$this->Session->setFlash('Unable to update settings.');
		}
		
		if(!$this->request->data){
			$this->request->data = $setting;
		}
	}
	
	public function delete($id){
		if ($this->request->is('get')) 
			throw new MethodNotAllowedException();
			
		if ($this->Setting->delete($id)) {
			$this->Session->setFlash('The setting with id: ' . $id . ' has been deleted.');
			$this->redirect(array('action' => 'index'));
		}
	}
}

?>