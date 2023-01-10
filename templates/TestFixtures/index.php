<?php
/**
 * @var \App\View\AppView $this
 * @var string[] $plugins
 */

?>

<style>
	.disabled {
		color: #999;
	}
</style>

<h1>Test Helper</h1>

<div class="row">
	<div class="col-xs-12">
		<h2>Fixtures</h2>
		<p>Comparing current fixtures and table classes.</p>


	<?php foreach ($result as $plugin => $pluginResult) { ?>
		<?php if ($plugin === 'app' && $this->request->getQuery('plugin')) {
			continue;
		} ?>

		<h3><?php echo ($plugin); ?></h3>
		<table class="list">
			<tr>
				<th>Fixture</th><th>DB Table</th><th>Model</th>
			</tr>
			<?php foreach ($pluginResult as $fixture => $fixtureDetails) { ?>
			<tr>
				<td class="<?php echo $fixtureDetails['missing'] ? 'disabled' : '' ?>">
					<?php echo h($fixture); ?>
					<?php
					if ($fixtureDetails['missing']) {
						echo $this->Form->postLink($this->Icon->render('plus', ['title' => 'Generate Fixture']), ['action' => 'generate'], ['class' => '', 'escapeTitle' => false, 'data' => ['plugin' => $plugin, 'name' => $fixture]]);
					}
					?>
				</td>
				<td>
					<?php echo h($fixtureDetails['table']); ?>
				</td>
				<td>
					<?php echo h(implode(', ', $fixtureDetails['models'])); ?>
				</td>
			</tr>
			<?php } ?>
		</table>
	<?php } ?>

	</div>
</div>
