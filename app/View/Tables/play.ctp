<script>
	var userID = "<?php echo $this->Session->read('Auth.User.id'); ?>";
	var username = "<?php echo $this->Session->read('Auth.User.username'); ?>";
	var draftID = "<?php echo $table['Table']['draft_id']; ?>";
	var tableID = "<?php echo $table['Table']['id']; ?>";
	var setname = "<?php echo $setName; ?>";
</script>

<div id="gameboard">
	<div id="gameboard_window"></div>

	<div id="left_panel">
		<div id="card_preview"></div>
		
		<div id="boxes">
			<div class="box graveyard"></div>
			<div class="box tokenholder"></div>
		</div>
		
		<div id="hand_container">
			<div><input id='btn_draw' type="submit" value="Draw Card" class="button" /></div>
			<div id="hand"></div>
		</div>
		
	</div>
</div>

<div id="chat">
	<div id="chat_content" style="float:left;">
		<textarea id='log' name='log' readonly='readonly'></textarea><br/>
		<div id="chat_message">
			<div style="float:left">
				<input type='text' id='message' name='message' />
			</div>
		</div>
	</div>
	<div id="chat_collapse">&gt;</div>
</div>


<?php 
echo $this->Html->script('jquery-1.9.1.min.js');
echo $this->Html->script('jquery-ui-1.10.3.custom.min.js');
echo $this->Html->script('autobahn.min.js');
echo $this->Html->script('websockets/wsFunc.js');
echo $this->Html->script('gameManager.js');
echo $this->Html->script('websockets/wsDraftPlay.js');
?>