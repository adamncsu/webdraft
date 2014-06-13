<h1>Edit Set</h1>
<?php
echo $this->Form->create('Set');
echo $this->Form->input('name');
echo $this->Form->input('shortname', array('label'=>'Code'));
echo $this->Form->input('id', array('type' => 'hidden'));
echo $this->Form->end('Save');
?>