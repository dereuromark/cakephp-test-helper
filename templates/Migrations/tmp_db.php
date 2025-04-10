<?php
/**
 * @var \App\View\AppView $this
 * @var string[] $plugins
 */

use Cake\Core\Plugin;

?>

<h1>Migrations tooling</h1>

<div class="row">
	<div class="col-md-6 col-xs-12">
		<h2>Re-Do Migration</h2>

		<h3>1. Add tmp DB to create new snapshot in</h3>

		<?php echo $this->Form->postLink('Create tmp DB', ['action' => 'tmpDb'], ['class' => 'btn btn-primary']); ?>

	</div>
</div>
