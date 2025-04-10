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

		<h3>3. Test snapshot</h3>
		<p>Tmp snapshot in CONFIG/MigrationsTmp/</p>

		<?php
		if ($files) {
			echo $this->Form->postLink('Remove tables and test snapshot', ['action' => 'snapshotTest'], ['data' => ['test' => 1], 'class' => 'btn btn-primary']);
		} ?>

	</div>
</div>
