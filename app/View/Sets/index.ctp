<h1>Sets</h1>
<table>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Code</th>
		<th>Actions</th>
        <th>Created</th>
        <th>Modified</th>
    </tr>

    <?php foreach ($sets as $set): ?>
    <tr>
        <td><?php echo $set['Set']['id']; ?></td>
        <td><?php echo $set['Set']['name']; ?></td>
        <td><?php echo $set['Set']['shortname']; ?></td>
		<td><?php echo $this->Html->link('Edit', array('action' => 'edit', $set['Set']['id'])); ?>
			<?php echo $this->Form->postLink(
                'Delete',
                array('action' => 'delete', $set['Set']['id']),
                array('confirm' => 'Are you sure?'));
			?>
		</td>
        <td><?php echo $set['Set']['created']; ?></td>
        <td><?php echo $set['Set']['modified']; ?></td>
    </tr>
    <?php endforeach; ?>
    <?php unset($sets); ?>
</table>

<?php echo $this->Html->link('Add Set', array('controller'=>'sets', 'action'=>'add')); ?>