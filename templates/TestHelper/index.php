<?php
/**
 * @var \Cake\View\View $this
 * @var string[] $plugins
 * @var string|null $namespace
 * @var array<array<string, mixed>> $testTypes
 */

use Cake\Core\Plugin;

$this->assign('title', 'Test Helper Dashboard');
?>

<div class="page-header">
	<h1><?php echo $this->TestHelper->icon('dashboard'); ?> Test Helper Dashboard</h1>
	<p class="lead">Browser-based tools for test-driven development</p>
</div>

<div class="row g-4">
	<!-- URL Reverse Lookup Card -->
	<div class="col-lg-6">
		<div class="card h-100 shadow-sm">
			<div class="card-header bg-primary text-white">
				<h5 class="mb-0"><?php echo $this->TestHelper->icon('url'); ?> Reverse URL Lookup</h5>
			</div>
			<div class="card-body">
				<p class="card-text">Convert any URL from your site into its corresponding routing array - useful for test cases and URL generation.</p>

				<?php echo $this->Form->create(null, ['class' => 'mb-3']); ?>
					<?php
					echo $this->Form->control('url', [
						'label' => 'Enter any URL from your site',
						'placeholder' => 'e.g., /test-helper/plugins',
						'class' => 'form-control',
					]);
					echo $this->Form->control('verbose', [
						'type' => 'checkbox',
						'default' => true,
						'label' => 'Verbose output',
						'class' => 'form-check-input',
					]);
					?>
					<div class="mt-3">
						<?php echo $this->Form->submit(__('Submit'), ['class' => 'btn btn-primary']); ?>
					</div>
				<?php echo $this->Form->end(); ?>

				<?php echo $this->element('url'); ?>
			</div>
		</div>
	</div>

	<!-- Test Cases Card -->
	<div class="col-lg-6">
		<div class="card h-100 shadow-sm">
			<div class="card-header bg-success text-white">
				<h5 class="mb-0"><?php echo $this->TestHelper->icon('test-cases'); ?> Test Cases</h5>
			</div>
			<div class="card-body">
				<p class="card-text">Generate and run test cases for your application classes.</p>

				<!-- Plugin Selector -->
				<div class="mb-3">
					<label class="form-label fw-bold">Select Plugin/App:</label>
					<ul class="inline-list mb-0">
						<li><?php echo $this->Html->link('[App]', ['?' => ['plugin' => null]], ['class' => 'btn btn-sm btn-outline-secondary']); ?></li>
					<?php
					foreach ($plugins as $plugin) {
						$path = Plugin::path($plugin);
						$path = str_replace(ROOT . DS, '', $path);
						?>
						<li><?php echo $this->Html->link($plugin, ['?' => ['plugin' => $plugin]], ['class' => 'btn btn-sm btn-outline-secondary']); ?></li>
						<?php
					}
					?>
					</ul>
				</div>

				<h6 class="fw-bold mt-3"><?php echo h($namespace ?: '[App]'); ?></h6>
				<?php $namespace = $namespace ?: 'app'; ?>
				<div class="row g-2">
					<?php foreach ($testTypes as $type) { ?>
						<div class="col-6">
							<?php
								$isEmpty = $type['count'] === 0;
								$btnClass = $isEmpty ? 'btn btn-sm btn-outline-secondary d-block text-start text-muted' : 'btn btn-sm btn-outline-success d-block text-start';
								$label = $type['label'] . ($type['count'] > 0 ? ' <span class="badge bg-success">' . $type['count'] . '</span>' : ' <span class="badge bg-secondary">0</span>');
							?>
							<?php echo $this->Html->link(
								$this->TestHelper->icon($type['icon']) . ' ' . $label,
								['controller' => 'TestCases', 'action' => $type['action'], '?' => ['namespace' => $namespace]],
								['escape' => false, 'class' => $btnClass],
							); ?>
						</div>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Query Builder Row -->
<div class="row g-4 mt-2">
	<div class="col-12">
		<div class="card shadow-sm border-primary">
			<div class="card-header bg-primary text-white">
				<h5 class="mb-0"><?php echo $this->TestHelper->icon('query-builder'); ?> SQL to CakePHP Query Builder</h5>
			</div>
			<div class="card-body">
				<p class="card-text">Convert raw SQL queries (MySQL) into CakePHP Query Builder code. Supports SELECT, INSERT, UPDATE, DELETE with JOINs, WHERE conditions, GROUP BY, ORDER BY, and more.</p>
				<ul class="list-unstyled">
					<li><?php echo $this->Html->link($this->TestHelper->icon('next') . ' Open Query Builder Converter', ['controller' => 'QueryBuilder', 'action' => 'index'], ['escape' => false, 'class' => 'btn btn-primary']); ?></li>
				</ul>
			</div>
		</div>
	</div>
</div>

<!-- Additional Tools Row -->
<div class="row g-4 mt-2">
	<!-- Plugins Card -->
	<div class="col-md-4">
		<div class="card shadow-sm">
			<div class="card-header bg-info text-white">
				<h5 class="mb-0"><?php echo $this->TestHelper->icon('plugins'); ?> Plugins</h5>
			</div>
			<div class="card-body">
				<p class="card-text">Check plugin hooks and get improvement suggestions.</p>
				<ul class="list-unstyled">
					<li><?php echo $this->Html->link($this->TestHelper->icon('next') . ' Check Plugin Hooks', ['controller' => 'Plugins'], ['escape' => false, 'class' => 'btn btn-sm btn-info text-white']); ?></li>
				</ul>
			</div>
		</div>
	</div>

	<!-- Migrations Card -->
	<div class="col-md-4">
		<div class="card shadow-sm">
			<div class="card-header bg-warning text-dark">
				<h5 class="mb-0"><?php echo $this->TestHelper->icon('migrations'); ?> Migrations</h5>
			</div>
			<div class="card-body">
				<p class="card-text">Tools for managing database migrations and snapshots.</p>
				<ul class="list-unstyled mb-0">
					<li class="mb-2"><?= $this->Html->link($this->TestHelper->icon('next') . ' Schema Drift Detection', ['controller' => 'Migrations', 'action' => 'driftCheck'], ['escape' => false, 'class' => 'btn btn-sm btn-warning']) ?></li>
					<li><?= $this->Html->link($this->TestHelper->icon('next') . ' Migration Re-Do', ['controller' => 'Migrations'], ['escape' => false, 'class' => 'btn btn-sm btn-warning']) ?></li>
				</ul>
			</div>
		</div>
	</div>

	<!-- Comparison Card -->
	<div class="col-md-4">
		<div class="card shadow-sm">
			<div class="card-header bg-danger text-white">
				<h5 class="mb-0"><?php echo $this->TestHelper->icon('compare'); ?> Comparison</h5>
			</div>
			<div class="card-body">
				<p class="card-text">Compare and validate your application structure.</p>
				<ul class="list-unstyled mb-0">
					<li class="mb-2"><?php echo $this->Html->link($this->TestHelper->icon('next') . ' Compare Models & DB Tables', ['controller' => 'TestComparison'], ['escape' => false, 'class' => 'btn btn-sm btn-danger text-white']); ?></li>
					<li><?php echo $this->Html->link($this->TestHelper->icon('next') . ' Compare Fixtures vs Tables', ['controller' => 'TestFixtures'], ['escape' => false, 'class' => 'btn btn-sm btn-danger text-white']); ?></li>
				</ul>
			</div>
		</div>
	</div>
</div>

<!-- Demo Section -->
<div class="row g-4 mt-2">
	<div class="col-12">
		<div class="card shadow-sm">
			<div class="card-header bg-secondary text-white">
				<h5 class="mb-0"><?php echo $this->TestHelper->icon('demo'); ?> Demo & Examples</h5>
			</div>
			<div class="card-body">
				<p class="card-text">View layout examples and HTML5 element demos.</p>
				<ul class="list-unstyled">
					<li><?php echo $this->Html->link($this->TestHelper->icon('next') . ' Layouting and More', ['controller' => 'Demo'], ['escape' => false, 'class' => 'btn btn-sm btn-secondary text-white']); ?></li>
				</ul>
			</div>
		</div>
	</div>
</div>
