<div id="home-login">
	
<?php if($reg['Setting']['value'] == 'true'){ ?>

	<div class="users form">
	<?php echo $this->Form->create('User'); ?>
		<h2>Register</h2>
		<?php echo $this->Form->input('username', array('placeholder' => 'Username'));
		echo $this->Form->input('password', array('placeholder' => 'Password'));
		echo $this->Form->input('repeatpassword', array('label'=>'Confirm Password', 'type'=>'password', 'placeholder'=>'Confirm Password'));
		?>
	<?php echo $this->Form->end(__('Submit')); ?>
	</div>
	
<?php } else{ ?>
	
	<p style="font-size: 32px;">Registration Closed</p>
	
<?php } ?>

</div>