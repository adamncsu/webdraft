<?php

class Pack extends AppModel{

	var $hasMany = array(
		'Card' => array(
			'className' => 'Card',
			'foreignKey' => 'pack_id',
			'dependent' => true
		)
	);
	
}

?>