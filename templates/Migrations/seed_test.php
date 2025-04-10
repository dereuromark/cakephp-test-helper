<?php
/**
 * @var \App\View\AppView $this
 * @var string[] $seeds
 */

use Cake\Core\Plugin;

?>

<h1>Migrations tooling</h1>

<div class="row">
	<div class="col-md-6 col-xs-12">
		<h2>Re-Do Migration</h2>

		<h3>4. Test seeding</h3>
		<p>Found <?php count($seeds); ?>Seeds in CONFIG/Seeds/</p>

		<?php
		if ($seeds) {
			echo $this->Form->postLink('Test seeds', ['action' => 'seedTest'], ['data' => ['test' => 1], 'class' => 'btn btn-primary']);
		} ?>

	</div>
</div>
