<?php
/**
 * @var \App\View\AppView $this
 * @var array<string> $plugins
 * @var array<string> $allTables
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

		<?php echo $this->Form->create(null, ['url' => ['action' => 'snapshot']]); ?>

		<?php if ($allTables) { ?>
			<div class="card mb-3">
				<div class="card-header">
					<strong>Table Exclusion</strong>
					<?php if ($plugins) { ?>
						<small class="text-muted">(Detected plugins: <?php echo implode(', ', $plugins); ?>)</small>
					<?php } ?>
				</div>
				<div class="card-body">
					<p class="text-muted small">
						Select tables to exclude from the migration snapshot. This is useful for excluding plugin-managed tables
						that have their own migrations (e.g., Queue, FileStorage).
					</p>

					<div class="row">
						<?php
						$columns = array_chunk($allTables, (int)ceil(count($allTables) / 3));
						foreach ($columns as $columnTables) {
						?>
							<div class="col-md-4">
								<?php foreach ($columnTables as $table) { ?>
									<div class="form-check">
										<?php
										echo $this->Form->checkbox('excluded_tables[]', [
											'value' => $table,
											'id' => 'table-' . $table,
											'class' => 'form-check-input',
											'hiddenField' => false,
										]);
										?>
										<label class="form-check-label" for="table-<?php echo $table; ?>">
											<?php echo h($table); ?>
										</label>
									</div>
								<?php } ?>
							</div>
						<?php } ?>
					</div>
				</div>
			</div>
		<?php } ?>

		<?php
		echo $this->Form->hidden('generate', ['value' => 1]);
		echo $this->Form->button('Create snapshot', ['class' => 'btn btn-primary']);
		echo $this->Form->end();
		?>

		<?php
		if ($files) {
			echo $this->Form->postLink('Clear', ['action' => 'snapshot'], ['data' => ['clear' => 1], 'class' => 'btn btn-secondary', 'confirm' => 'Sure?']);
		} ?>

	</div>
</div>
