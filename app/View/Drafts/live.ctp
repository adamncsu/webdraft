<script>
	var draftID = "<?php echo $draft['Draft']['id']; ?>";
	var userID = "<?php echo $this->Session->read('Auth.User.id'); ?>";
	var username = "<?php echo $this->Session->read('Auth.User.username'); ?>";
	var sets = [<?php echo '"'.$draft['Set1']['name'].'", "'.$draft['Set2']['name'].'", "'.$draft['Set3']['name'].'"'; ?>];
</script>

<div id="draft_live">
	<div id="row_1">
		<div id="live_title"><h1>Draft #<?php echo $draft['Draft']['id']; ?></h1></div>
		<div id="live_indicators">
			<?php
			foreach($draft as $key => $value){
				if(substr($key, 0,4) == 'Seat')
					echo $this->Html->image('draft-ind-red.png', array('class'=>is_null($value['id'])?'noplayer':'player'.$value['id'], 'title'=>$value['username']));
			}
			?>
		</div>
		<div style="float:right;padding-top:11px"><img id="btn_sound" style="cursor:pointer" src="/img/audio-low.png" onclick="toggleAudio()"/></div>
	</div>
	
	<div id="row_2">
		<div id="pack">
			<div id="packstack"></div>
		</div>
		<div id="cardpreview_container">
			<div id="cardpreview"></div>
			<div id="cardpreview_button">
				<input id='btn_draft' type="submit" value="Draft Card" class="button" />
			</div>
		</div>
		<div id="draft_live_chat">
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
		<h3>Selected Cards</h3>
		<div id="drafted_cards">
			<div id="drafted_cards_1"></div>
			<div id="drafted_cards_2"></div>
			<div id="drafted_cards_3"></div>
			<div id="drafted_cards_4"></div>
			<div id="drafted_cards_5"></div>
		</div>
	</div>
</div>

<audio id="bell" src="/snd/bell.mp3" preload="auto"></audio>

<?php	
	echo $this->Html->script('jquery-1.9.1.min.js');
	echo $this->Html->script('autobahn.min.js');
	echo $this->Html->script('websockets/wsFunc.js');
	echo $this->Html->script('draftManager.js');
	echo $this->Html->script('websockets/wsDraftLive.js');
?>