<?php
/**
 * @var \Cake\View\View $this
 * @var array<\TestHelper\Utility\Association\Finding> $findings
 * @var array<string, int> $totals
 * @var bool $includeVendor
 */

use TestHelper\Utility\Association\Finding;

$this->assign('title', 'Association ↔ DB Audit: Flat scan');

$rowClass = function (string $severity): string {
	return match ($severity) {
		'error' => 'table-danger',
		'warning' => 'table-warning',
		default => '',
	};
};

$topicLabels = [
	Finding::LAYER_CONSTRAINT => 'Constraints',
	Finding::LAYER_COLUMN => 'Columns',
	Finding::LAYER_TYPE => 'Key types',
	Finding::DIRECTION_UNSUPPORTED => 'Not verifiable',
];
$topicCounts = array_count_values(array_map(fn (Finding $finding): string => $finding->topic(), $findings));
?>

<div class="page-header mb-3">
	<h1><?php echo $this->TestHelper->icon('sitemap'); ?> Flat scan</h1>
	<p>
		<?php echo $this->Html->link($this->TestHelper->icon('previous') . ' Back to matrix', ['action' => 'index', '?' => ['vendor' => $includeVendor ? 1 : null]], ['escapeTitle' => false, 'class' => 'btn btn-sm btn-outline-secondary']); ?>
	</p>
</div>

<div class="d-flex gap-2 mb-3">
	<span class="badge bg-danger fs-6"><?php echo (int)($totals['error'] ?? 0); ?> errors</span>
	<span class="badge bg-warning text-dark fs-6"><?php echo (int)($totals['warning'] ?? 0); ?> warnings</span>
	<span class="badge bg-info text-dark fs-6"><?php echo (int)($totals['info'] ?? 0); ?> info</span>
</div>

<?php if ($findings) { ?>
	<div class="d-flex gap-2 mb-3 flex-wrap align-items-center" id="topicFilter">
		<span class="text-muted small">Filter by topic:</span>
		<?php foreach ($topicLabels as $topic => $label) {
			$count = $topicCounts[$topic] ?? 0;
			if (!$count) {
				continue;
			}
			?>
			<button type="button" class="btn btn-sm btn-outline-secondary active topic-chip" data-topic="<?php echo h($topic); ?>" aria-pressed="true">
				<?php echo h($label); ?> <span class="badge bg-secondary"><?php echo $count; ?></span>
			</button>
		<?php } ?>
	</div>
<?php } ?>

<?php if (!$findings) { ?>
	<div class="alert alert-success"><?php echo $this->TestHelper->icon('check'); ?> No mismatches found across all in-scope tables.</div>
<?php } else { ?>
	<div class="table-responsive">
		<table class="table table-sm table-bordered align-middle">
			<thead class="table-light">
				<tr>
					<th>Severity</th>
					<th>Table</th>
					<th>Type</th>
					<th>Direction</th>
					<th>Column</th>
					<th>Target</th>
					<th>Message</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($findings as $finding) { ?>
					<tr class="<?php echo $rowClass($finding->severity); ?>" data-topic="<?php echo h($finding->topic()); ?>">
						<td><?php echo h($finding->severity); ?></td>
						<td><?php echo $this->Html->link(h($finding->table), ['action' => 'view', $finding->table, '?' => ['vendor' => $includeVendor ? 1 : null]]); ?></td>
						<td><code><?php echo h($finding->associationType); ?></code></td>
						<td><?php echo h($finding->direction); ?></td>
						<td><?php echo h($finding->column ?? ''); ?></td>
						<td><?php echo h($finding->target ?? ''); ?></td>
						<td><?php echo h($finding->message); ?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
<?php } ?>

<?php $this->append('script'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
	var filter = document.getElementById('topicFilter');
	if (!filter) {
		return;
	}
	var rows = document.querySelectorAll('table tbody tr[data-topic]');
	var off = {};
	function apply() {
		rows.forEach(function (row) {
			row.style.display = off[row.getAttribute('data-topic')] ? 'none' : '';
		});
	}
	filter.querySelectorAll('.topic-chip').forEach(function (chip) {
		chip.addEventListener('click', function () {
			var topic = chip.getAttribute('data-topic');
			off[topic] = !off[topic];
			chip.classList.toggle('active', !off[topic]);
			chip.setAttribute('aria-pressed', off[topic] ? 'false' : 'true');
			apply();
		});
	});
});
</script>
<?php $this->end(); ?>
