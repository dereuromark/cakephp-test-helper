<?php
/**
 * @var \App\View\AppView $this
 * @var string[] $plugins
 * @var mixed $result
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
		<h2>Model comparison</h2>
		<p>Comparing current table and entity classes.</p>


	<?php foreach ($result as $plugin => $pluginResult) { ?>
		<?php if ($plugin === 'app' && $this->request->getQuery('plugin')) {
			continue;
		} ?>

		<h3><?php echo ($plugin); ?></h3>
		<table class="list">
			<tr>
				<th>Table</th><th>DB Table</th><th>Entity</th>
			</tr>
			<?php foreach ($pluginResult as $tableClassName => $details) { ?>
				<?php
				?>
			<tr>
				<td>
					<?php echo h($details['table']) ?: $this->Icon->render('warning', [], ['title'=> 'Missing']); ?>
				</td>
				<td>
					<?php echo h($details['dbTable']) ?: $this->Icon->render('warning', [], ['title'=> 'Missing']); ?>
				</td>
				<td>
					<?php echo h($details['entity']) ?: $this->Icon->render('warning', [], ['title'=> 'Missing']); ?>
				</td>
			</tr>
			<?php } ?>
		</table>
	<?php } ?>

	</div>
</div>
