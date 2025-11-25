<?php
/**
 * @var \App\View\AppView $this
 * @var array<string> $availableConnections
 * @var string $connectionName
 * @var string $database
 * @var string $shadowDatabase
 * @var array<string> $pluginsToMigrate
 * @var array|null $drift
 * @var bool $hasDrift
 * @var string|null $error
 * @var bool $isMysql
 * @var bool $isPostgres
 */

/**
 * @param mixed $value
 * @return string
 */
function formatValue($value): string {
	if ($value === null) {
		return '<em>null</em>';
	}
	if ($value === true) {
		return 'true';
	}
	if ($value === false) {
		return 'false';
	}
	if (is_array($value)) {
		return h(json_encode($value));
	}

	return h((string)$value);
}

/**
 * @param array<string, mixed> $columnData
 * @return string
 */
function formatColumnType(array $columnData): string {
	$type = $columnData['type'] ?? 'unknown';
	if (isset($columnData['length'])) {
		$type .= '(' . $columnData['length'] . ')';
	} elseif (isset($columnData['precision'])) {
		$type .= '(' . $columnData['precision'];
		if (isset($columnData['scale'])) {
			$type .= ',' . $columnData['scale'];
		}
		$type .= ')';
	}
	if (!empty($columnData['unsigned'])) {
		$type .= ' UNSIGNED';
	}

	return $type;
}
?>

<h1>Schema Drift Detection</h1>

<p>
	<?= $this->Html->link('Back to Migrations', ['action' => 'index']) ?>
</p>

<?php if ($error) { ?>
	<div class="alert alert-danger">
		<?= h($error) ?>
	</div>
<?php } ?>

<div class="row">
	<div class="col-md-8 col-xs-12">
		<?php if (count($availableConnections) > 1) { ?>
			<h2>Select Connection to Check</h2>
			<p>
				<?php foreach ($availableConnections as $conn) { ?>
					<?php if ($conn === $connectionName) { ?>
						<span class="btn btn-primary btn-sm"><?= h($conn) ?></span>
					<?php } else { ?>
						<?= $this->Html->link($conn, ['?' => ['connection' => $conn]], ['class' => 'btn btn-default btn-sm']) ?>
					<?php } ?>
				<?php } ?>
			</p>
		<?php } ?>

		<h2>Database Information</h2>
		<table class="table table-bordered">
			<tr>
				<th>Connection to Check</th>
				<td><code><?= h($connectionName) ?></code> (<?= h($database) ?>)</td>
			</tr>
			<tr>
				<th>Shadow Database</th>
				<td><code>test</code> (<?= h($shadowDatabase) ?>)</td>
			</tr>
			<tr>
				<th>Driver</th>
				<td>
					<?php if ($isMysql) { ?>
						MySQL
					<?php } elseif ($isPostgres) { ?>
						PostgreSQL
					<?php } else { ?>
						<span class="text-warning">Unsupported</span>
					<?php } ?>
				</td>
			</tr>
		</table>

		<?php if (!$error) { ?>
			<h2>Drift Check Process</h2>
			<p>This tool compares your actual database against what your migrations define (like Prisma's shadow database).</p>
			<p>The <code>test</code> database is used as shadow: migrations are run fresh, then compared against your selected connection.</p>

			<h3>Migrations to Run</h3>
			<ul>
				<li><strong>App</strong> (main application)</li>
				<?php foreach ($pluginsToMigrate as $plugin) { ?>
					<li><?= h($plugin) ?></li>
				<?php } ?>
			</ul>
			<?php if (!$pluginsToMigrate) { ?>
				<p class="text-muted"><small>No plugin migrations detected (based on *_phinxlog tables).</small></p>
			<?php } ?>

			<?php if ($drift === null) { ?>
				<h3>Run Migrations & Compare</h3>
				<p class="text-warning"><strong>Warning:</strong> This will clear all tables in the test database and run migrations fresh.</p>
				<?= $this->Form->create(null) ?>
				<?= $this->Form->hidden('action', ['value' => 'run_migrations']) ?>
				<?= $this->Form->button('Run Migrations & Compare', ['class' => 'btn btn-primary']) ?>
				<?= $this->Form->end() ?>
			<?php } ?>
		<?php } ?>

		<?php if ($drift !== null) { ?>
			<hr>
			<h2>Drift Report</h2>

			<?php if (!$hasDrift) { ?>
				<div class="alert alert-success">
					<strong>No drift detected!</strong> Your database schema matches your migration history.
				</div>
			<?php } else { ?>
				<div class="alert alert-warning">
					<strong>Schema drift detected!</strong> Your database has diverged from what migrations define.
				</div>

				<?php if ($drift['extra_tables']) { ?>
					<h3>Extra Tables <small>(in database but not in migrations)</small></h3>
					<table class="table table-bordered table-striped">
						<thead>
							<tr>
								<th>Table</th>
								<th>Columns</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($drift['extra_tables'] as $item) { ?>
								<tr class="danger">
									<td><code>+<?= h($item['table']) ?></code></td>
									<td><?= h(implode(', ', $item['columns'])) ?></td>
								</tr>
							<?php } ?>
						</tbody>
					</table>
				<?php } ?>

				<?php if ($drift['missing_tables']) { ?>
					<h3>Missing Tables <small>(in migrations but not in database)</small></h3>
					<table class="table table-bordered table-striped">
						<thead>
							<tr>
								<th>Table</th>
								<th>Expected Columns</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($drift['missing_tables'] as $item) { ?>
								<tr class="warning">
									<td><code>-<?= h($item['table']) ?></code></td>
									<td><?= h(implode(', ', $item['columns'])) ?></td>
								</tr>
							<?php } ?>
						</tbody>
					</table>
				<?php } ?>

				<?php if ($drift['column_diffs']) { ?>
					<h3>Column Differences</h3>
					<table class="table table-bordered table-striped">
						<thead>
							<tr>
								<th>Type</th>
								<th>Table.Column</th>
								<th>Details</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($drift['column_diffs'] as $item) { ?>
								<tr class="<?= $item['type'] === 'extra' ? 'danger' : ($item['type'] === 'missing' ? 'warning' : 'info') ?>">
									<td>
										<?php if ($item['type'] === 'extra') { ?>
											<span class="label label-danger">EXTRA</span>
										<?php } elseif ($item['type'] === 'missing') { ?>
											<span class="label label-warning">MISSING</span>
										<?php } else { ?>
											<span class="label label-info">MISMATCH</span>
										<?php } ?>
									</td>
									<td><code><?= h($item['table']) ?>.<?= h($item['column']) ?></code></td>
									<td>
										<?php if ($item['type'] === 'extra') { ?>
											Type: <code><?= formatColumnType($item['actual']) ?></code>
										<?php } elseif ($item['type'] === 'missing') { ?>
											Expected: <code><?= formatColumnType($item['expected']) ?></code>
										<?php } else { ?>
											<?php foreach ($item['differences'] as $attr => $diff) { ?>
												<div>
													<strong><?= h($attr) ?>:</strong>
													Expected <code><?= formatValue($diff['expected']) ?></code>,
													Actual <code><?= formatValue($diff['actual']) ?></code>
												</div>
											<?php } ?>
										<?php } ?>
									</td>
								</tr>
							<?php } ?>
						</tbody>
					</table>
				<?php } ?>

				<?php if ($drift['index_diffs']) { ?>
					<h3>Index Differences</h3>
					<table class="table table-bordered table-striped">
						<thead>
							<tr>
								<th>Type</th>
								<th>Table</th>
								<th>Index</th>
								<th>Details</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($drift['index_diffs'] as $item) { ?>
								<tr class="<?= $item['type'] === 'extra' ? 'danger' : ($item['type'] === 'missing' ? 'warning' : 'info') ?>">
									<td>
										<?php if ($item['type'] === 'extra') { ?>
											<span class="label label-danger">EXTRA</span>
										<?php } elseif ($item['type'] === 'missing') { ?>
											<span class="label label-warning">MISSING</span>
										<?php } else { ?>
											<span class="label label-info">MISMATCH</span>
										<?php } ?>
									</td>
									<td><code><?= h($item['table']) ?></code></td>
									<td><code><?= h($item['index']) ?></code></td>
									<td>
										<?php if ($item['type'] === 'mismatch') { ?>
											Expected: <code><?= formatValue($item['expected']) ?></code><br>
											Actual: <code><?= formatValue($item['actual']) ?></code>
										<?php } else { ?>
											<?= formatValue($item['expected'] ?? $item['actual']) ?>
										<?php } ?>
									</td>
								</tr>
							<?php } ?>
						</tbody>
					</table>
				<?php } ?>

				<?php if ($drift['constraint_diffs']) { ?>
					<h3>Constraint Differences</h3>
					<table class="table table-bordered table-striped">
						<thead>
							<tr>
								<th>Type</th>
								<th>Table</th>
								<th>Constraint</th>
								<th>Details</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($drift['constraint_diffs'] as $item) { ?>
								<tr class="<?= $item['type'] === 'extra' ? 'danger' : ($item['type'] === 'missing' ? 'warning' : 'info') ?>">
									<td>
										<?php if ($item['type'] === 'extra') { ?>
											<span class="label label-danger">EXTRA</span>
										<?php } elseif ($item['type'] === 'missing') { ?>
											<span class="label label-warning">MISSING</span>
										<?php } else { ?>
											<span class="label label-info">MISMATCH</span>
										<?php } ?>
									</td>
									<td><code><?= h($item['table']) ?></code></td>
									<td><code><?= h($item['constraint']) ?></code></td>
									<td>
										<?php if ($item['type'] === 'mismatch') { ?>
											Expected: <code><?= formatValue($item['expected']) ?></code><br>
											Actual: <code><?= formatValue($item['actual']) ?></code>
										<?php } else { ?>
											<?= formatValue($item['expected'] ?? $item['actual']) ?>
										<?php } ?>
									</td>
								</tr>
							<?php } ?>
						</tbody>
					</table>
				<?php } ?>

				<hr>
				<h3>Resolve Drift</h3>
				<p>To resolve the drift, you can:</p>
				<ul>
					<li>Run <code>bin/cake bake migration_diff FixDrift</code> to generate a migration that aligns the database with migrations</li>
					<li>Or manually adjust your database/migrations to match</li>
				</ul>
			<?php } ?>

			<hr>
			<p>
				<?= $this->Html->link('Run Again', ['?' => ['connection' => $connectionName]], ['class' => 'btn btn-default']) ?>
			</p>
		<?php } ?>
	</div>
</div>
