<?php
/**
 * @var \App\View\AppView $this
 * @var array<array<string, mixed>> $methods
 * @var string $file
 * @var string $testPath
 * @var array<array<string, string>> $breadcrumb
 * @var string $namespace
 * @var string|null $plugin
 * @var string $className
 */
?>

<h1><?php echo $this->TestHelper->icon('file'); ?> <?php echo h($className); ?></h1>
<p class="text-muted">Namespace: <?php echo h($namespace); ?></p>
<p><code><?php echo h($testPath); ?></code></p>

<!-- Breadcrumb Navigation -->
<nav aria-label="breadcrumb" class="mb-3">
	<ol class="breadcrumb">
		<li class="breadcrumb-item">
			<?php echo $this->Html->link(
				$this->TestHelper->icon('home') . ' TestCase',
				['action' => 'browse', '?' => ['namespace' => $namespace]],
				['escape' => false],
			); ?>
		</li>
		<?php foreach ($breadcrumb as $crumb) { ?>
			<li class="breadcrumb-item">
				<?php echo $this->Html->link(
					h($crumb['name']),
					['action' => 'browse', '?' => ['namespace' => $namespace, 'path' => $crumb['path']]],
				); ?>
			</li>
		<?php } ?>
		<li class="breadcrumb-item active" aria-current="page">
			<?php echo h($className); ?>
		</li>
	</ol>
</nav>

<!-- Actions -->
<div class="mb-4">
	<?php
	$parentPath = dirname($file);
	$parentPath = $parentPath === '.' ? '' : $parentPath;
	echo $this->Html->link(
		$this->TestHelper->icon('back') . ' Back to directory',
		['action' => 'browse', '?' => array_filter(['namespace' => $namespace, 'path' => $parentPath ?: null])],
		['escape' => false, 'class' => 'btn btn-outline-secondary btn-sm me-2'],
	);
	?>
	<?php echo $this->Html->link(
		$this->TestHelper->icon('run') . ' Run all tests',
		['action' => 'run', '?' => ['test' => $testPath]],
		['escapeTitle' => false, 'target' => '_blank', 'class' => 'btn btn-success btn-sm run', 'data-test-case' => $testPath],
	); ?>
	<?php echo $this->Html->link(
		$this->TestHelper->icon('coverage') . ' Coverage',
		['action' => 'coverage', '?' => ['test' => $testPath, 'name' => basename($file, 'Test.php'), 'type' => dirname($file)]],
		['escapeTitle' => false, 'target' => '_blank', 'class' => 'btn btn-info btn-sm ms-1 coverage', 'data-test-case' => $testPath],
	); ?>
</div>

<h2>Test Methods <span class="badge bg-secondary"><?php echo count($methods); ?></span></h2>

<?php if (empty($methods)) { ?>
	<div class="alert alert-warning">
		No test methods found in this file.
	</div>
<?php } else { ?>
	<table class="table table-hover">
		<thead>
			<tr>
				<th style="width: 30px;"></th>
				<th>Method</th>
				<th style="width: 80px;">Line</th>
				<th style="width: 150px;">Actions</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($methods as $method) { ?>
				<tr>
					<td>
						<?php echo $this->TestHelper->icon('run', ['class' => 'text-success']); ?>
					</td>
					<td>
						<code><?php echo h($method['name']); ?>()</code>
					</td>
					<td>
						<small class="text-muted"><?php echo h($method['line']); ?></small>
					</td>
					<td>
						<?php echo $this->Html->link(
							$this->TestHelper->icon('run') . ' Run',
							['action' => 'run', '?' => ['test' => $testPath, 'filter' => $method['name']]],
							['escapeTitle' => false, 'target' => '_blank', 'class' => 'btn btn-sm btn-outline-success run', 'data-test-case' => $testPath, 'data-filter' => $method['name']],
						); ?>
					</td>
				</tr>
			<?php } ?>
		</tbody>
	</table>
<?php } ?>

<?php echo $this->element('TestHelper.test_cases'); ?>
