<?php
/**
 * @var \App\View\AppView $this
 */

?>

<h1>Migrations tooling</h1>

<div class="row">
	<div class="col-md-6 col-xs-12">
		<h2>Re-Do Migration</h2>

		<h3>4. cleanup</h3>

		<?php echo $this->Form->postLink('Remove tmp DB', ['action' => 'cleanup'], ['class' => 'btn btn-primary']); ?>

	</div>
</div>
