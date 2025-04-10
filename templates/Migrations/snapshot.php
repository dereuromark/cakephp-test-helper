<?php
/**
 * @var \App\View\AppView $this
 * @var string[] $plugins
 * @var mixed $files
 */

use Cake\Core\Plugin;

?>

<h1>Migrations tooling</h1>

<div class="row">
	<div class="col-md-6 col-xs-12">
		<h2>Re-Do Migration</h2>

		<h3>2. Create snapshot</h3>
		<p>Tmp snapshot in CONFIG/MigrationsTmp/</p>

		<?php echo $this->Form->postLink('Create snapshot', ['action' => 'snapshot'], ['data' => ['generate' => 1], 'class' => 'btn btn-primary']); ?>

		<?php
		if ($files) {
			echo $this->Form->postLink('Clear', ['action' => 'snapshot'], ['data' => ['clear' => 1], 'class' => 'btn btn-secondary', 'confirm' => 'Sure?']);
		} ?>

	</div>
</div>
