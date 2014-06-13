<?php

class Table extends AppModel{

	var $belongsTo = array(
		'Seat1' => array('className'=>'User', 'foreignKey'=>'seat1_id'),
		'Seat2' => array('className'=>'User', 'foreignKey'=>'seat2_id')
	);
	
}

?>