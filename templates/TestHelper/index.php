<?php
/**
 * @var \Cake\View\View $this
 * @var string[] $plugins
 * @var string|null $namespace
 */

use Cake\Core\Plugin;

$this->assign('title', 'Test Helper Dashboard');
?>

<div class="page-header">
	<h1><i class="fas fa-flask"></i> Test Helper Dashboard</h1>
	<p class="lead">Browser-based tools for test-driven development</p>
</div>

<div class="row g-4">
	<!-- URL Reverse Lookup Card -->
	<div class="col-lg-6">
		<div class="card h-100 shadow-sm">
			<div class="card-header bg-primary text-white">
				<h5 class="mb-0"><i class="fas fa-link"></i> Reverse URL Lookup</h5>
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
				<h5 class="mb-0"><i class="fas fa-code"></i> Test Cases</h5>
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
				<?php
					$namespace = $namespace ?: 'app';
				?>
				<div class="list-group">
					<?php echo $this->Html->link(
						'<i class="fas fa-cog"></i> Controllers',
						['controller' => 'TestCases', 'action' => 'controller', '?' => ['namespace' => $namespace]],
						['escape' => false, 'class' => 'list-group-item list-group-item-action'],
					); ?>
					<?php echo $this->Html->link(
						'<i class="fas fa-terminal"></i> Commands',
						['controller' => 'TestCases', 'action' => 'command', '?' => ['namespace' => $namespace]],
						['escape' => false, 'class' => 'list-group-item list-group-item-action'],
					); ?>
					<?php echo $this->Html->link(
						'<i class="fas fa-database"></i> Tables',
						['controller' => 'TestCases', 'action' => 'table', '?' => ['namespace' => $namespace]],
						['escape' => false, 'class' => 'list-group-item list-group-item-action'],
					); ?>
					<?php echo $this->Html->link(
						'<i class="fas fa-cube"></i> Entities',
						['controller' => 'TestCases', 'action' => 'entity', '?' => ['namespace' => $namespace]],
						['escape' => false, 'class' => 'list-group-item list-group-item-action'],
					); ?>
					<?php echo $this->Html->link(
						'<i class="fas fa-puzzle-piece"></i> Behaviors',
						['controller' => 'TestCases', 'action' => 'behavior', '?' => ['namespace' => $namespace]],
						['escape' => false, 'class' => 'list-group-item list-group-item-action'],
					); ?>
					<?php echo $this->Html->link(
						'<i class="fas fa-plug"></i> Components',
						['controller' => 'TestCases', 'action' => 'component', '?' => ['namespace' => $namespace]],
						['escape' => false, 'class' => 'list-group-item list-group-item-action'],
					); ?>
					<?php echo $this->Html->link(
						'<i class="fas fa-hands-helping"></i> Helpers',
						['controller' => 'TestCases', 'action' => 'helper', '?' => ['namespace' => $namespace]],
						['escape' => false, 'class' => 'list-group-item list-group-item-action'],
					); ?>
				</div>
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
				<h5 class="mb-0"><i class="fas fa-plug"></i> Plugins</h5>
			</div>
			<div class="card-body">
				<p class="card-text">Check plugin hooks and get improvement suggestions.</p>
				<ul class="list-unstyled">
					<li><?php echo $this->Html->link('<i class="fas fa-arrow-right"></i> Check Plugin Hooks', ['controller' => 'Plugins'], ['escape' => false, 'class' => 'btn btn-sm btn-info text-white']); ?></li>
				</ul>
			</div>
		</div>
	</div>

	<!-- Migrations Card -->
	<div class="col-md-4">
		<div class="card shadow-sm">
			<div class="card-header bg-warning text-dark">
				<h5 class="mb-0"><i class="fas fa-database"></i> Migrations</h5>
			</div>
			<div class="card-body">
				<p class="card-text">Tools for managing database migrations and snapshots.</p>
				<ul class="list-unstyled">
					<li><?php echo $this->Html->link('<i class="fas fa-arrow-right"></i> Migration Re-Do', ['controller' => 'Migrations'], ['escape' => false, 'class' => 'btn btn-sm btn-warning']); ?></li>
				</ul>
			</div>
		</div>
	</div>

	<!-- Comparison Card -->
	<div class="col-md-4">
		<div class="card shadow-sm">
			<div class="card-header bg-danger text-white">
				<h5 class="mb-0"><i class="fas fa-exchange-alt"></i> Comparison</h5>
			</div>
			<div class="card-body">
				<p class="card-text">Compare and validate your application structure.</p>
				<ul class="list-unstyled mb-0">
					<li class="mb-2"><?php echo $this->Html->link('<i class="fas fa-arrow-right"></i> Compare Models & DB Tables', ['controller' => 'TestComparison'], ['escape' => false, 'class' => 'btn btn-sm btn-danger text-white']); ?></li>
					<li><?php echo $this->Html->link('<i class="fas fa-arrow-right"></i> Compare Fixtures vs Tables', ['controller' => 'TestFixtures'], ['escape' => false, 'class' => 'btn btn-sm btn-danger text-white']); ?></li>
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
				<h5 class="mb-0"><i class="fas fa-palette"></i> Demo & Examples</h5>
			</div>
			<div class="card-body">
				<p class="card-text">View layout examples and HTML5 element demos.</p>
				<ul class="list-unstyled">
					<li><?php echo $this->Html->link('<i class="fas fa-arrow-right"></i> Layouting and More', ['controller' => 'Demo'], ['escape' => false, 'class' => 'btn btn-sm btn-secondary text-white']); ?></li>
				</ul>
			</div>
		</div>
	</div>
</div>
