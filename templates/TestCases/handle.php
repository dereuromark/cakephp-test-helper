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
		// Extract relative path within TestCase/ for view action
		$testCaseFile = preg_replace('#^(plugins/[^/]+/)?tests/TestCase/#', '', $file['testCase']);
		?>
		<tr>
			<td>
				<?php
				if ($file['hasTestCase']) {
					echo $this->Html->link(
						h($file['name']),
						['action' => 'view', '?' => ['namespace' => $this->request->getQuery('namespace'), 'file' => $testCaseFile]],
						['title' => 'View test methods'],
					);
				} else {
					echo h($file['name']);
				}
				?>
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
					echo $this->Form->postLink($this->TestHelper->icon('add', ['title' => 'Generate test case']), ['action' => $this->request->getParam('action'), '?' => ['namespace' => $this->request->getQuery('namespace')]], ['class' => '', 'escapeTitle' => false, 'data' => ['name' => $file['name']]]);
				} else {
					?>
					<?php echo $this->Html->link($this->TestHelper->icon('run', ['title' => 'Run all tests']), ['action' => 'run', '?' => ['test' => $file['testCase']]], ['escapeTitle' => false, 'target' => '_blank', 'class' => 'run', 'data-test-case' => $file['testCase']]); ?>

					<?php echo $this->Html->link($this->TestHelper->icon('coverage', ['title' => 'Coverage']), ['action' => 'coverage', '?' => ['test' => $file['testCase'], 'name' => $file['name'], 'type' => $file['type']]], ['escapeTitle' => false, 'target' => '_blank', 'class' => 'coverage', 'data-test-case' => $file['testCase'], 'data-name' => $file['name'], 'data-type' => $file['type']]); ?>
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
