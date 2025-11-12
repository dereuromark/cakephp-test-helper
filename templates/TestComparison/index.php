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
	.warning {
		color: #ff6a00;
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
				$details['dbTable'] = h($details['dbTable'] ?? '');
				$details['entity'] = h($details['entity'] ?? '');

				if ($details['table']) {
					$underscored = \Cake\Utility\Inflector::underscore($details['table'] ?? '');
					$singularized = \Cake\Utility\Inflector::singularize($details['table'] ?? '');

					if ($details['dbTable'] && $underscored !== $details['dbTable']) {
						$details['dbTable'] = ' <span class="warning">' . $details['dbTable'] . '</span>';
					}
					if ($details['entity'] && $singularized !== $details['entity']) {
						$details['entity'] = ' <span class="warning">' . $details['entity'] . '</span>';
					}
				}

				?>
			<tr>
				<td>
					<?php echo h($details['table']) ?: $this->TestHelper->icon('missing', ['title'=> 'Missing']); ?>
				</td>
				<td>
					<?php echo $details['dbTable'] ?: $this->TestHelper->icon('missing', ['title'=> 'Missing']); ?>
				</td>
				<td>
					<?php echo $details['entity'] ?: $this->TestHelper->icon('missing', ['title'=> 'Missing']); ?>
				</td>
			</tr>
			<?php } ?>
		</table>
	<?php } ?>

	</div>
</div>
