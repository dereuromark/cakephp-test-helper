<?php
/**
 * @var \App\View\AppView $this
 * @var string $database
 * @var string $tmpDatabase
 */

use Cake\Core\Plugin;

?>

<h1>Migrations tooling</h1>

<div class="row">
	<div class="col-md-6 col-xs-12">
		<h2>Schema Drift Detection</h2>
		<p>Compare your actual database against what your migrations define. Detects manual changes, failed migrations, or schema inconsistencies.</p>

		<?= $this->Html->link('Check for Drift', ['action' => 'driftCheck'], ['class' => 'btn btn-info']) ?>

		<hr>

		<h2>Re-Do Migration</h2>
		<p>A multi-step process to ensure a fresh migration file replacing your current ones (merging all together).</p>

		<h3>1. Add tmp DB to create new snapshot in</h3>
		<ul>
			<li>
				Default DB: <?= h($database) ?>
			</li>
			<li>
			Proposed Tmp DB: <?= h($tmpDatabase) ?>
			</li>
		</ul>

		<p>You can manually create one or in the next step let the proposed tmp DB be created for you.</p>

		<?= $this->Html->link('Continue', ['action' => 'tmpDb'], ['class' => 'btn btn-primary']) ?>
	</div>
</div>
