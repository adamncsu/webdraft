<h1>Select Set</h1>
<?php
echo $this->Form->create('Draft');
echo $this->Form->input('set1_id', array('options' => $sets, 'label' => 'Pack #1'));
echo $this->Form->input('set2_id', array('options' => $sets, 'label' => 'Pack #2'));
echo $this->Form->input('set3_id', array('options' => $sets, 'label' => 'Pack #3'));
echo $this->Form->input('user_id', array('type' => 'hidden'));
echo $this->Form->end('Create Draft');
?>