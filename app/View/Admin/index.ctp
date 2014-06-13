<h1>Settings</h1>
<table>
    <tr>
        <th>Property</th>
        <th>Value</th>
        <th>Actions</th>
        <th>Last Modified</th>
    </tr>

    <?php foreach ($settings as $setting): ?>
    <tr>
        <td><?php echo $setting['Setting']['prop']; ?></td>
        <td><?php echo $setting['Setting']['value']; ?></td>
		<td><?php echo $this->Html->link('Edit', array('action' => 'edit', $setting['Setting']['id'])); ?>
			<?php echo $this->Form->postLink(
                'Delete',
                array('action' => 'delete', $setting['Setting']['id']),
                array('confirm' => 'Are you sure?'));
			?>
		</td>
        <td><?php echo $setting['Setting']['modified']; ?></td>
    </tr>
    <?php endforeach; ?>
    <?php unset($settings); ?>
</table>

<?php echo $this->Html->link('Add Property', array('controller'=>'admin', 'action'=>'add')); ?>