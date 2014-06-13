<h1>Add Property</h1>
<?php
echo $this->Form->create('Setting');
echo $this->Form->input('prop');
echo $this->Form->input('value');
echo $this->Form->end('Save');
?>