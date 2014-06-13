<h1>Available Drafts</h1>

<?php if(count($drafts) > 0){ ?>
<table>
    <tr>
        <th>Draft ID</th>
        <th>Host</th>
        <th>Date Started</th>
		<th>Sets</th>
        <th>Status</th>
        <th>Actions</th>
    </tr>

    <!-- Here is where we loop through our $posts array, printing out post info -->

    <?php foreach ($drafts as $draft): ?>
    <tr>
        <td><?php echo 'Draft #'.$draft['Draft']['id']; ?></td>
		<td><?php echo $draft['Host']['username']; ?></td>
        <td><?php echo date('M j Y H:i', strtotime($draft['Draft']['created'])); ?></td>
		<td><?php echo $draft['Set1']['shortname'] .'|'. $draft['Set2']['shortname'] .'|'. $draft['Set3']['shortname']; ?></td>
        <td><?php 
			if($draft['Draft']['status'] == 0) 
				echo "Open";
			else if($draft['Draft']['status'] == 1) 
				echo "Starting";
			else if($draft['Draft']['status'] == 2) 
				echo "In progress";
			else if($draft['Draft']['status'] == 3) 
				echo "Complete";
		?>
		</td>
		<td><?php 
			if($this->Session->read('Auth.User.id') === $draft['Host']['id'] || $this->Session->read('Auth.User.role') === 'admin'){
				echo $this->Form->postLink('Delete',
					array('action' => 'delete', $draft['Draft']['id']),
					array('confirm' => 'Are you sure?'));
			}
			else
				echo '<span style="color:#666;">Delete</span>';
		?> | <?php
			if($draft['Draft']['status'] == 0)
				echo $this->Html->link('Join', array('action' => 'join', $draft['Draft']['id']));
			else if($draft['Draft']['status'] < 3)
				echo $this->Html->link('Join', array('action' => 'live', $draft['Draft']['id']));
			else if($draft['Draft']['status'] == 3)
				echo $this->Html->link('Join', array('action' => 'build', $draft['Draft']['id']));
			else
				echo '<span style="color:#666;">Join</span>';
		?></td>
    </tr>
    <?php endforeach; ?>
    <?php unset($draft); ?>
</table>
<?php } else{ ?>
	<br/>No drafts open.<br/><br/>
<?php } ?>

<input type="submit" value="Create Draft" class="button" onclick="location.href='/drafts/create'" style="width:175px"/>