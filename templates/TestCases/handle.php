<?php
/**
 * @var \App\View\AppView $this
 * @var string $type
 * @var array $files
 */
?>

<h1><?php echo h($this->request->getQuery('namespace')); ?> tests</h1>

<h2><?php echo h($type); ?></h2>

<table class="table">
	<?php
	foreach ($files as $file) {
		?>
		<tr>
			<td>
				<?php echo h($file['name']); ?>
			</td>
			<td>
				<?php
				if (!$file['hasTestCase'] || empty($file['needsNoTestCase'])) {
					echo $this->TestHelper->yesNo($file['hasTestCase']);
				}
				?>
			</td>
			<td>
				<?php
				if (!$file['hasTestCase']) {
					echo $this->Form->postLink($this->Icon->render('plus', ['title' => 'Generate test case']), ['action' => $this->request->getParam('action'), '?' => ['namespace' => $this->request->getQuery('namespace')]], ['class' => '', 'escapeTitle' => false, 'data' => ['name' => $file['name']]]);
				} else {
					?>
					<?php echo $this->Html->link($this->Icon->render('play', ['title' => 'Run tests']), ['action' => 'run', '?' => ['test' => $file['testCase']]], ['escapeTitle' => false, 'target' => '_blank', 'class' => 'run', 'data-test-case' => $file['testCase']]); ?>

					<?php echo $this->Html->link($this->Icon->render('chart-bar', ['title' => 'Coverage']), ['action' => 'coverage', '?' => ['test' => $file['testCase'], 'name' => $file['name'], 'type' => $file['type']]], ['escapeTitle' => false, 'target' => '_blank', 'class' => 'coverage', 'data-test-case' => $file['testCase'], 'data-name' => $file['name'], 'data-type' => $file['type']]); ?>
				<?php } ?>
			</td>
			<td>
				<small><?php echo h($file['testCase']); ?></small>
			</td>
		</tr>
		<?php
	}
	?>
</table>

<?php echo $this->element('TestHelper.test_cases'); ?>
