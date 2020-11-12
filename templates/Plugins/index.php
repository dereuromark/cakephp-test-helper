<?php
/**
 * @var \App\View\AppView $this
 * @var string[] $plugins
 * @var string[] $hooks
 * @var array $result
 */

?>

<style>
.plugins-overview .warning {
	color: red;
}
</style>

<h1>Plugin tooling</h1>

<p>Note: This does not check how your app is loading a plugin, but what the plugin's default config is.</p>

<div class="plugins-overview row">
	<div class="col-xs-12">
		<h2>Info/Check existence vs enabled/disabled</h2>
		<p>Status: <?php echo implode(', ', $hooks); ?> ?</p>

		<div class="list-inline">
			<?php foreach ($plugins as $plugin) { ?>
			<div class="box col-md-6 col-xs-12">
				<h3><?php echo h($plugin); ?></h3>
				<?php
				$recommendedChanges = false;
				?>
				<table class="table list">
					<tr>
						<th>
							Hook
						</th>
						<th>
							Exists
						</th>
						<th>
							Enabled
						</th>
					</tr>
					<?php foreach ($hooks as $hook) { ?>
					<tr>
						<td>
							<?php echo h($hook); ?>
						</td>
						<td>
							<?php echo $this->Format->yesNo((bool)$result[$plugin][$hook]); ?>
							<?php if ($result[$plugin][$hook]) {
								echo implode(', ', $result[$plugin][$hook]);
							} ?>
						</td>
						<td>
							<?php
							$enabled = $result[$plugin][$hook . 'Enabled'] ?? null;

							if ($enabled !== null) {
								$text = $enabled ? 'yes' : 'no';
								$exists = !empty($result[$plugin][$hook]);

								if (!$enabled && $exists) {
									$text = '<span class="warning">' . $text . '</span>';
									$recommendedChanges = true;
								}
							} else {
								$text = '<i>auto-detect</i>';
								$couldBeManuallyDisabled = empty($result[$plugin][$hook]);

								if ($couldBeManuallyDisabled) {
									$text = '<span class="warning">' . $text . '</span>';
									$recommendedChanges = true;
								}
							}
							echo $text;
							?>
						</td>
					</tr>
					<?php } ?>
				</table>
				<?php
					if (!$result[$plugin]['pluginClassExists']) {
						$recommendedChanges = true;
						echo '<b>Plugin class missing</b> | ';
					}

					if ($recommendedChanges) {
						echo $this->Html->link('Recommended changes', ['action' => 'recommended', '?' => ['plugin' => $plugin]]);
					} else {
						echo '<i>nothing to change</i>';
					}
				?>
			</div>
			<?php } ?>
		</div>

	</div>
</div>
