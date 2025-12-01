<?php
/**
 * @var \App\View\AppView $this
 * @var array<array<string, mixed>> $items
 * @var string $path
 * @var array<array<string, string>> $breadcrumb
 * @var string $namespace
 * @var string|null $plugin
 * @var string[] $plugins
 */

use Cake\Core\Plugin;

$plugins = Plugin::loaded();
?>

<h1><?php echo $this->TestHelper->icon('browse'); ?> Browse TestCase/</h1>

<div class="row mb-3">
	<div class="col-md-4">
		<select id="namespace-select" class="form-select form-select-sm" onchange="window.location.href=this.value">
			<option value="<?php echo $this->Url->build(['action' => 'browse', '?' => ['namespace' => 'app']]); ?>"<?php echo $namespace === 'app' ? ' selected' : ''; ?>>[App]</option>
			<?php foreach ($plugins as $p) { ?>
				<option value="<?php echo $this->Url->build(['action' => 'browse', '?' => ['namespace' => $p]]); ?>"<?php echo $namespace === $p ? ' selected' : ''; ?>><?php echo h($p); ?></option>
			<?php } ?>
		</select>
	</div>
</div>

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
		<?php foreach ($breadcrumb as $index => $crumb) { ?>
			<?php if ($index === count($breadcrumb) - 1) { ?>
				<li class="breadcrumb-item active" aria-current="page">
					<?php echo h($crumb['name']); ?>
				</li>
			<?php } else { ?>
				<li class="breadcrumb-item">
					<?php echo $this->Html->link(
						h($crumb['name']),
						['action' => 'browse', '?' => ['namespace' => $namespace, 'path' => $crumb['path']]],
					); ?>
				</li>
			<?php } ?>
		<?php } ?>
	</ol>
</nav>

<!-- Back Link -->
<?php if ($path) { ?>
	<div class="mb-3">
		<?php
		$parentPath = dirname($path);
		$parentPath = $parentPath === '.' ? '' : $parentPath;
		echo $this->Html->link(
			$this->TestHelper->icon('back') . ' Back',
			['action' => 'browse', '?' => array_filter(['namespace' => $namespace, 'path' => $parentPath ?: null])],
			['escape' => false, 'class' => 'btn btn-outline-secondary btn-sm'],
		);
		?>
	</div>
<?php } ?>

<?php if (empty($items)) { ?>
	<div class="alert alert-info">
		No test cases found in this directory.
	</div>
<?php } else { ?>
	<table class="table table-hover">
		<thead>
			<tr>
				<th style="width: 30px;"></th>
				<th>Name</th>
				<th style="width: 200px;">Actions</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($items as $item) { ?>
				<tr>
					<td>
						<?php
						if ($item['type'] === 'directory') {
							echo $this->TestHelper->icon('folder', ['class' => 'text-warning']);
						} else {
							echo $this->TestHelper->icon('file', ['class' => 'text-primary']);
						}
						?>
					</td>
					<td>
						<?php
						$itemPath = $path ? $path . DIRECTORY_SEPARATOR . $item['path'] : $item['path'];
						if ($item['type'] === 'directory') {
							echo $this->Html->link(
								h($item['name']),
								['action' => 'browse', '?' => ['namespace' => $namespace, 'path' => $itemPath]],
								['class' => 'fw-bold'],
							);
						} else {
							echo $this->Html->link(
								h($item['name']),
								['action' => 'view', '?' => ['namespace' => $namespace, 'file' => $itemPath]],
							);
						}
						?>
					</td>
					<td>
						<?php if ($item['type'] === 'file') { ?>
							<?php
							$testPath = ($plugin ? 'plugins' . DIRECTORY_SEPARATOR . $plugin . DIRECTORY_SEPARATOR . 'tests' : 'tests') . DIRECTORY_SEPARATOR . 'TestCase' . DIRECTORY_SEPARATOR . $itemPath;
							?>
							<?php echo $this->Html->link(
								$this->TestHelper->icon('run', ['title' => 'Run all tests']),
								['action' => 'run', '?' => ['test' => $testPath]],
								['escapeTitle' => false, 'target' => '_blank', 'class' => 'btn btn-sm btn-outline-success run', 'data-test-case' => $testPath],
							); ?>
							<?php echo $this->Html->link(
								$this->TestHelper->icon('next', ['title' => 'View methods']),
								['action' => 'view', '?' => ['namespace' => $namespace, 'file' => $itemPath]],
								['escapeTitle' => false, 'class' => 'btn btn-sm btn-outline-primary'],
							); ?>
						<?php } ?>
					</td>
				</tr>
			<?php } ?>
		</tbody>
	</table>
<?php } ?>

<?php echo $this->element('TestHelper.test_cases'); ?>
