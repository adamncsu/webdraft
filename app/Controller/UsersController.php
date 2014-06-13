<?php

class UsersController extends AppController {

    public function beforeFilter() {
        parent::beforeFilter();
        $this->Auth->allow(array('add', 'login', 'logout'));
    }
	
	public function isAuthorized($user){
		if(isset($user['role']) && $user['role'] === 'admin')
			return true;
			
		$this->Session->setFlash(__('You are not authorized to access that location.'));
		return false;
	}
	
	public function login(){
		$this->layout = 'home';
		if($this->request->is('post')){
			if ($this->Auth->login()) {
				//$this->Session->setFlash(__('Successfully logged in'));
				$this->redirect($this->Auth->redirect());
			} else {
				unset($this->request->data['User']['password']);
				$this->Session->setFlash(__('Invalid username or password, try again'));
			}
		}
	}
	
	public function logout() {
		$this->Session->setFlash(__('You are now logged out'));
		$this->redirect($this->Auth->logout());
	}

    public function index() {
        $this->set('users', $this->User->find('all', array('fields' => array('id', 'username', 'created'))));
    }

    public function add() {
		$this->layout = 'home';
		
		// get registration setting
		$this->loadModel('Setting');
		$reg = $this->Setting->find('first', array('conditions' => array('prop'=>'registrationEnabled')));
		$this->set('reg', $reg);
		
        if ($this->request->is('post')) {
            $this->User->create();
            if ($this->User->save($this->request->data)) {
                $id = $this->User->id;
				$this->request->data['User'] = array_merge($this->request->data['User'], array('id' => $id));
				$this->Auth->login($this->request->data['User']);
                $this->Session->setFlash(__('User registered.'));
				$this->redirect('/');
            } else {
                $this->Session->setFlash(__('The user could not be saved. Please, try again.'));
            }
        }
    }

    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }
        $this->User->id = $id;
        if (!$this->User->exists()) {
            throw new NotFoundException(__('Invalid user'));
        }
        if ($this->User->delete()) {
            $this->Session->setFlash(__('User deleted'));
            $this->redirect(array('action' => 'index'));
        }
        $this->Session->setFlash(__('User was not deleted'));
        $this->redirect(array('action' => 'index'));
    }
}

?>