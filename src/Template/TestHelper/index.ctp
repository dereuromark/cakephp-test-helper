<?php
/**
 * @var \App\View\AppView $this
 */

use Cake\Core\Plugin;

?>

<h1>Test Helper</h1>

<div class="row">
	<div class="col-md-6 col-xs-12">
		<h2>Generate URL arrays</h2>
		<?php echo $this->Form->create();?>
		<fieldset>
			<legend><?php echo __('Enter any URL from your site');?></legend>
			<?php
			echo $this->Form->control('url', []);
			echo $this->Form->control('verbose', ['type' => 'checkbox', 'default' => true]);
			?>
		</fieldset>
		<?php echo $this->Form->submit(__('Submit')); echo $this->Form->end();?>

		<?php echo $this->element('url'); ?>
	</div>

	<div class="col-md-6 col-xs-12">
		<h2>Test Cases</h2>

		<ul>
			<li><?php echo $this->Html->link('[App]', ['controller' => 'TestCases', 'action' => 'controllers', 'app']); ?></li>
			<?php
			foreach ($plugins as $plugin) {
				$path = Plugin::path($plugin);
				$path = str_replace(ROOT . DS, '', $path);
				?>
				<li><?php echo $this->Html->link($plugin, ['controller' => 'TestCases', 'action' => 'controllers', $plugin]); ?> (<?php echo h($path); ?>)</li>
				<?php
			}
			?>
		</ul>

	</div>
</div>
