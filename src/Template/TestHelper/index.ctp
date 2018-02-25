<?php
/**
 * @var \App\View\AppView $this
 */

use Cake\Core\Plugin;

?>

<h1>Test Helper</h1>

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
