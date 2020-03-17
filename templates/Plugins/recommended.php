<?php
/**
 * @var \App\View\AppView $this
 * @var string $plugin
 * @var string[] $hooks
 * @var array $result
 * @var string $classContent
 * @var string $classContentAfter
 */

?>

<h1>Plugin class for <?php echo h($plugin); ?></h1>

<div>
	<pre><?php echo h($classContentAfter); ?></pre>


	<?php echo $this->Form->create(); ?>
	<?php echo $this->Form->submit('Write to file'); ?>
	<?php echo $this->Form->end(); ?>
</div>
