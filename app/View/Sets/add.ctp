<h1>Add Set</h1>
<?php
echo $this->Form->create('Set');
echo $this->Form->input('name');
echo $this->Form->input('shortname', array('label'=>'Code'));
echo $this->Form->end('Save');
?>