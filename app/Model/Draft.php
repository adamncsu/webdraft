<?php

class Draft extends AppModel{

	//var $belongsTo = array('User');
	
	var $belongsTo = array(
		'Host'  => array('className'=>'User', 'foreignKey'=>'user_id'),
		'Seat1' => array('className'=>'User', 'foreignKey'=>'seat1_id'),
		'Seat2' => array('className'=>'User', 'foreignKey'=>'seat2_id'),
		'Seat3' => array('className'=>'User', 'foreignKey'=>'seat3_id'),
		'Seat4' => array('className'=>'User', 'foreignKey'=>'seat4_id'),
		'Seat5' => array('className'=>'User', 'foreignKey'=>'seat5_id'),
		'Seat6' => array('className'=>'User', 'foreignKey'=>'seat6_id'),
		'Seat7' => array('className'=>'User', 'foreignKey'=>'seat7_id'),
		'Seat8' => array('className'=>'User', 'foreignKey'=>'seat8_id')
	);
	
	var $hasMany = array(
		'Pack' => array(
			'className' => 'Pack',
			'foreignKey' => 'draft_id',
			'dependent' => true
		),
		'Table' => array(
			'className' => 'Table',
			'foreignKey' => 'draft_id',
			'dependent' => true
		)
	);
	
	/*public function isOwnedBy($draft, $user) {
		return $this->field('id', array('id' => $draft, 'user_id' => $user)) === $draft;
	}*/
	
	/*public $validate = array(
        'user_id' => array(
            'unique' => array(
				'rule' => array('isUnique'),
				'message' => 'You must wait until your draft is finished or deleted before you start a new one.'
			)
        )
    );*/
}

?>