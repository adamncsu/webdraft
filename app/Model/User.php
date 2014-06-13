<?php

App::uses('AuthComponent', 'Controller/Component', 'Security');

class User extends AppModel {

	public $validate = array(
        'username' => array(
            'required' => array(
                'rule' => array('notEmpty'),
                'message' => 'A username is required'
            ),
			'unique' => array(
				'rule' => array('isUnique'),
				'message' => 'That username is already taken'
			)
        ),
        'password' => array(
            'required' => array(
                'rule' => array('notEmpty'),
                'message' => 'A password is required'
            )
        ),
		'repeatpassword' => array(
			'required' => array(
				'rule' => array('notEmpty'),
				'message' => 'Please confirm your password'
			),
			'passwordmatch' => array(
				'rule' => array('checkPasswords'),
				'message' => "Passwords don't match"
			)
		)
    );
	
	public function beforeSave($options = array()) {
		if (isset($this->data[$this->alias]['password'])) {
			//$this->data[$this->alias]['password'] = AuthComponent::password($this->data[$this->alias]['password']);
			$this->data[$this->alias]['password'] = Security::hash($this->data[$this->alias]['password'], 'blowfish');
			$this->data[$this->alias]['role'] = 'user';
		}
		return true;
	}
	
	public function checkPasswords(){
		return $this->data[$this->alias]['password'] === $this->data[$this->alias]['repeatpassword'];
	}
}

?>