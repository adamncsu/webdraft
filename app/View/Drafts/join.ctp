<script>
	var draftID = "<?php echo $draft['Draft']['id']; ?>";
	var userID = "<?php echo $this->Session->read('Auth.User.id'); ?>";
	var username = "<?php echo $this->Session->read('Auth.User.username'); ?>";
</script>

<div id="draft_lobby">
	<div id="draft_lobby_left">
		<h1>Draft #<?php echo $draft['Draft']['id']; ?></h1>

		<p><small>Hosted by: <?php echo $draft['Host']['username']; ?><br/>
		Created: <?php echo date('M j Y H:i', strtotime($draft['Draft']['created'])); ?><br/>
		<?php echo $this->Html->link('Back to draft lobby', '/drafts'); ?>
		</small></p>

		<div id="draft_lobby_controls">
			<ul id="seat_list">
				<?php
					$sitting = false;
					
					for($i=1; $i<=8; $i++){
						$li = '<li>Seat '.$i.': ';
						if(!$draft['Seat'.$i]['id'])
							$li .= '<a href="#" onclick="takeSeat('.$i.')">Open</a>';
						else
							$li .= $draft['Seat'.$i]['username'];
						$li .= '</li>';
						echo $li;
						
						if($draft['Seat'.$i]['id'] == $this->Session->read('Auth.User.id'))
							$sitting = true;
					}
				?>
			</ul>
			<ul id="seat_leave" style="list-style-type:none;<?php if(!$sitting) echo "display:none"; else echo "display:inherit"; ?>"><li><a href="#" onclick="standUp()">Leave Seat</a></li></ul>
			
			<br/>
			
			<?php if($this->Session->read('Auth.User.id') === $draft['Host']['id']){ ?>
				<input id="start_btn" type='submit' value='Start Draft' style="width:160px; height:40px;" onclick="startDraft()"/>
			<?php } else echo '<em>Waiting for host to start...</em>'; ?>
		</div>
	</div>

	<div id="draft_lobby_right">
		<textarea id='log' name='log' readonly='readonly'></textarea><br/>
		<div id="chat_message">
			<input type='text' id='message' name='message' />
			<input id='btn_send' type="submit" value="Send" class="button" />
		</div>
	</div>
</div>

<?php	
	echo $this->Html->script('jquery-1.9.1.min.js');
	echo $this->Html->script('autobahn.min.js');
	echo $this->Html->script('websockets/wsFunc.js');
	echo $this->Html->script('websockets/wsDraftJoin.js');
?>