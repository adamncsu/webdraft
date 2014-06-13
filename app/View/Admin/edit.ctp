<h1>Edit Property</h1>
<?php
echo $this->Form->create('Setting');
echo $this->Form->input('prop');
echo $this->Form->input('value');
echo $this->Form->input('id', array('type' => 'hidden'));
echo $this->Form->end('Save');
?>