<?php
/**
 * @var \App\View\AppView $this
 * @var string $plugin
 * @var string[] $hooks
 * @var array $result
 * @var string $classContent
 * @var string $classContentAfter
 */

use SebastianBergmann\Diff\Differ;

?>

<h1>Plugin class for <?php echo h($plugin); ?></h1>

<p>
	<?php echo $this->Html->link('Back', ['action' => 'index']); ?>
	|
	<?php echo $this->Html->link('Raw (PHP to copy and paste)', ['?' => ['raw' => true] + $this->request->getQuery()]); ?>
</p>

<div>
	<pre><?php
		if (!$this->request->getQuery('raw') && class_exists(Differ::class)) {
			$differ = new Differ(null);
			$array = $differ->diffToArray($classContent, $classContentAfter);

			$count = count($array);
			for ($i = 0; $i < $count; $i++) {
				$row = $array[$i];

				$char = ' ';
				$output = $row[0];

				if ($row[1] === 1) {
					$char = '+';
				} elseif ($row[1] === 2) {
					$char = '-';
				}

				$array[$i] = $char . $output;
			}

			echo h(implode('', $array));

		} else {
			echo h($classContentAfter);
		}

	?></pre>


	<?php echo $this->Form->create(); ?>
	<?php echo $this->Form->submit('Write to file'); ?>
	<?php echo $this->Form->end(); ?>
</div>
