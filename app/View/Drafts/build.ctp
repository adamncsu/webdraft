<script>
	var draftID = "<?php echo $draft['Draft']['id']; ?>";
	var userID = "<?php echo $this->Session->read('Auth.User.id'); ?>";
	var username = "<?php echo $this->Session->read('Auth.User.username'); ?>";
	var sets = [<?php echo '"'.$draft['Set1']['name'].'", "'.$draft['Set2']['name'].'", "'.$draft['Set3']['name'].'"'; ?>];
</script>

<div id="draft_live">
	<div id="row_1">
		<div id="live_title"><h1>Draft #<?php echo $draft['Draft']['id']; ?></h1></div>
	</div>
	
	<div id="row_2">
		<div id="build_info">
			<!--<input id='btn_save' type="submit" value="Save Deck" class="button"/>-->
			<ul id="build_tools">
				<li><a href="#">Save as .COD</a></li>
				<li><a href="#">View as Text</a></li>
				<li><a href="#">Analyze</a></li>
				<li>Organize Cards</li>
			</ul>
		</div>
		<div id="cardpreview_container">
			<div id="cardpreview"></div>
		</div>
		<div id="draft_build_chat">
			<textarea id='log' name='log' readonly='readonly'></textarea><br/>
			<div id="chat_message">
				<div style="float:left">
					<input type='text' id='message' name='message' />
				</div>
				<div style="float:right">
					<input id='btn_send' type="submit" value="Send" class="button" />
				</div>
			</div>
		</div>
	</div>
	
	<div id="row_3">
		<div id="draftboard_title">
			<div style="float:left"><h3>Selected Cards</h3></div>
			<div style="float:right"><span id="draftboard_count">0</span>/40</div>
		<div id="drafted_cards">
			<div id="drafted_cards_1"></div>
			<div id="drafted_cards_2"></div>
			<div id="drafted_cards_3"></div>
			<div id="drafted_cards_4"></div>
			<div id="drafted_cards_5"></div>
		</div>
		<div id="draftboard_subtitle" style="display:none">
			<div style="float:left; padding-top:10px">Basic Lands</div>
		</div>
		<div id="drafted_lands">
			<div id="drafted_lands_1"></div>
			<div id="drafted_lands_2"></div>
			<div id="drafted_lands_3"></div>
			<div id="drafted_lands_4"></div>
			<div id="drafted_lands_5"></div>
		</div>
	</div>
</div>

<?php	
	echo $this->Html->script('jquery-1.9.1.min.js');
	echo $this->Html->script('jquery-ui-1.10.3.min.js');
	echo $this->Html->script('autobahn.min.js');
	echo $this->Html->script('websockets/wsFunc.js');
	echo $this->Html->script('deckBuilder.js');
	echo $this->Html->script('websockets/wsDraftBuild.js');
?>