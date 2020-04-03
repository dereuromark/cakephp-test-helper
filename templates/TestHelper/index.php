<?php
/**
 * @var \App\View\AppView $this
 * @var string[] $plugins
 */

use Cake\Core\Plugin;

?>

<h1>Test Helper</h1>

<div class="row">
	<div class="col-md-6 col-xs-12">
		<h2>Reverse URLs</h2>
		<p>This can be useful in test cases and other places you need to refer to URL arrays and paths.</p>

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


		<hr>

		<h2>Plugins</h2>
		<ul>
			<li><?php echo $this->Html->link('Check Plugin Hooks', ['controller' => 'Plugins']); ?></li>
		</ul>

		<h2>Fixtures</h2>
		<ul>
			<li><?php echo $this->Html->link('Compare Fixtures against Tables', ['controller' => 'TestFixtures']); ?></li>
		</ul>

	</div>

	<div class="col-md-6 col-xs-12">
		<h2>Test Cases</h2>

		<ul class="inline-list">
			<li><?php echo $this->Html->link('[App]', ['?' => ['plugin' => null]]); ?></li>
		<?php
		foreach ($plugins as $plugin) {
			$path = Plugin::path($plugin);
			$path = str_replace(ROOT . DS, '', $path);
			?>
			<li><?php echo $this->Html->link($plugin, ['?' => ['plugin' => $plugin]]); ?></li>
			<?php
		}
		?>
		</ul>

		<h3><?php echo h($namespace ?: '[App]'); ?></h3>
		<?php
			$namespace = $namespace ?: 'app';
		?>
		<ul>
			<li><?php echo $this->Html->link('Controllers', ['controller' => 'TestCases', 'action' => 'controller', '?' => ['namespace' => $namespace]]); ?></li>
			<li><?php echo $this->Html->link('Shells', ['controller' => 'TestCases', 'action' => 'shell', '?' => ['namespace' => $namespace]]); ?></li>
			<li><?php echo $this->Html->link('Tables', ['controller' => 'TestCases', 'action' => 'table', '?' => ['namespace' => $namespace]]); ?></li>
			<li><?php echo $this->Html->link('Entities', ['controller' => 'TestCases', 'action' => 'entity', '?' => ['namespace' => $namespace]]); ?></li>
			<li><?php echo $this->Html->link('Behaviors', ['controller' => 'TestCases', 'action' => 'behavior', '?' => ['namespace' => $namespace]]); ?></li>
			<li><?php echo $this->Html->link('Components', ['controller' => 'TestCases', 'action' => 'component', '?' => ['namespace' => $namespace]]); ?></li>
			<li><?php echo $this->Html->link('Helpers', ['controller' => 'TestCases', 'action' => 'helper', '?' => ['namespace' => $namespace]]); ?></li>
		</ul>

	</div>
</div>
