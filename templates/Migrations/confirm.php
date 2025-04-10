<?php
/**
 * @var \App\View\AppView $this
 * @var string $contentBefore
 * @var string $contentAfter
 * @var array $diffArray
 */

function displayDiff($array): void {
	$begin = null;
	$end = null;
	/**
	 * @var int $key
	 */
	foreach ($array as $key => $row) {
		if ($row[1] === 0) {
			continue;
		}

		if ($begin === null) {
			$begin = $key;
		}
		$end = $key;
	}
	if ($begin === null) {
		return;
	}
	$firstLineOfOutput = $begin > 0 ? $begin - 1 : 0;
	$lastLineOfOutput = count($array) - 1 > $end ? $end + 1 : $end;

	for ($i = $firstLineOfOutput; $i <= $lastLineOfOutput; $i++) {
		$row = $array[$i];

		$char = ' ';
		$output = trim($row[0], "\n\r\0\x0B");

		if ($row[1] === 1) {
			$char = '+';
			echo ('<span style="color: green">' . $char . $output . '</span>') . PHP_EOL;
		} elseif ($row[1] === 2) {
			$char = '-';
			echo ('<span style="color: red">' . $char . $output . '</span>') . PHP_EOL;
		} else {
			echo ($char . $output) . PHP_EOL;
		}
	}
}
?>

<h1>Migrations tooling</h1>

<div class="row">
	<div class="col-md-6 col-xs-12">
		<h2>Re-Do Migration</h2>

		<h3>5. Confirm result and replace migration file(s)</h3>

		<pre><?php echo displayDiff($diffArray); ?></pre>


		<?php echo $this->Form->postLink('Confirmed - replace files now', ['action' => 'confirm'], ['data' => ['confirm' => 1], 'class' => 'btn btn-primary']); ?>

	</div>
</div>
