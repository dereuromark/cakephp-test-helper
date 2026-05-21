<?php
/**
 * @var \Cake\View\View $this
 * @var array<string> $tables
 * @var array<\TestHelper\Utility\Association\Finding> $findings
 * @var array<string, array<string, array{severity: string|null, count: int}>> $matrix
 * @var array<string, int> $totals
 * @var array<string> $columns
 * @var bool $includeVendor
 */

$this->assign('title', 'Association ↔ DB Audit');

$badge = function (?string $severity): string {
	return match ($severity) {
		'error' => 'bg-danger',
		'warning' => 'bg-warning text-dark',
		'info' => 'bg-info text-dark',
		default => 'bg-success',
	};
};

$columnLabel = function (string $column): string {
	return match ($column) {
		'looseColumn' => 'Loose column',
		'keyType' => 'Key type',
		'cascadeRule' => 'Cascade',
		'index' => 'Index',
		default => $column,
	};
};
?>

<div class="page-header mb-3">
	<h1><?php echo $this->TestHelper->icon('sitemap'); ?> Association &harr; DB Audit</h1>
	<p class="lead">Symmetric diff between declared table associations and the actual database foreign keys.</p>
</div>

<div class="d-flex gap-2 align-items-center mb-3 flex-wrap">
	<span class="badge bg-danger fs-6"><?php echo (int)($totals['error'] ?? 0); ?> errors</span>
	<span class="badge bg-warning text-dark fs-6"><?php echo (int)($totals['warning'] ?? 0); ?> warnings</span>
	<span class="badge bg-info text-dark fs-6"><?php echo (int)($totals['info'] ?? 0); ?> info</span>

	<div class="ms-auto d-flex gap-2">
		<?php echo $this->Html->link(
			$this->TestHelper->icon('next') . ' Flat scan',
			['action' => 'scan', '?' => ['vendor' => $includeVendor ? 1 : null]],
			['escapeTitle' => false, 'class' => 'btn btn-sm btn-outline-primary'],
		); ?>
		<?php if ($includeVendor) { ?>
			<?php echo $this->Html->link('Hide vendor tables', ['action' => 'index'], ['class' => 'btn btn-sm btn-outline-secondary']); ?>
		<?php } else { ?>
			<?php echo $this->Html->link('Include vendor tables', ['action' => 'index', '?' => ['vendor' => 1]], ['class' => 'btn btn-sm btn-outline-secondary']); ?>
		<?php } ?>
	</div>
</div>

<?php if (!$tables) { ?>
	<div class="alert alert-warning">No tables found in scope.</div>
<?php } else { ?>
	<div class="table-responsive">
		<table class="table table-bordered table-sm align-middle">
			<thead class="table-light">
				<tr>
					<th>Table</th>
					<?php foreach ($columns as $column) { ?>
						<th class="text-center"><?php echo h($columnLabel($column)); ?></th>
					<?php } ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($tables as $alias) { ?>
					<tr>
						<th scope="row">
							<?php echo $this->Html->link(h($alias), ['action' => 'view', $alias, '?' => ['vendor' => $includeVendor ? 1 : null]]); ?>
						</th>
						<?php foreach ($columns as $column) { ?>
							<?php $cell = $matrix[$alias][$column] ?? ['severity' => null, 'count' => 0]; ?>
							<td class="text-center">
								<?php if ($cell['count'] === 0) { ?>
									<span class="badge bg-success">&check;</span>
								<?php } else { ?>
									<?php echo $this->Html->link(
										'<span class="badge ' . $badge($cell['severity']) . '">' . (int)$cell['count'] . '</span>',
										['action' => 'view', $alias, '?' => ['vendor' => $includeVendor ? 1 : null]],
										['escape' => false],
									); ?>
								<?php } ?>
							</td>
						<?php } ?>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
	<p class="text-muted small">
		<span class="badge bg-success">&check;</span> in agreement &nbsp;
		<span class="badge bg-danger">n</span> error &nbsp;
		<span class="badge bg-warning text-dark">n</span> warning &nbsp;
		<span class="badge bg-info text-dark">n</span> info. Click a count or table name for details.
	</p>
<?php } ?>
