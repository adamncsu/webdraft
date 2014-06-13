<div id="home-login">

<?php if(!$this->Session->read('Auth.User')){ ?>

	<div class="users form">
	<?php echo $this->Session->flash('auth'); ?>
	<?php echo $this->Form->create('User', array('action' => 'login')); ?>
		<h2>Log In</h2>
			<?php echo $this->Form->input('username', array('class' => 'text-field', 'placeholder' => 'Username'));
			echo $this->Form->input('password', array('class' => 'text-field', 'placeholder' => 'Password'));
		?>
	<?php //echo $this->Form->end(__('Login')); ?>
	<input type="submit" value="Log In" class="button" />
	<p><?php echo $this->Html->link('Register', '/register'); ?></p>
	</div>

<?php } else{ ?>

	Logged in as <strong><?php echo $this->Session->read('Auth.User.username'); ?></strong> (<?php echo $this->Html->link('Logout', '/logout'); ?>)
	<br/><br/>
	<span style="font-size:200%; font-weight:bold;"><?php echo $this->Html->link('Enter Draft Lobby', '/drafts'); ?></span>

<?php } ?>

<script>
	window.onload = function() { document.getElementById('UserUsername').focus(); }
</script>

</div>