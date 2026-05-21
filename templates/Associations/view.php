<?php
/**
 * @var \Cake\View\View $this
 * @var string|null $model
 * @var array<\TestHelper\Utility\Association\Finding> $findings
 * @var array<string, array<\TestHelper\Utility\Association\Finding>> $grouped
 * @var array<string> $groupOrder
 * @var bool $includeVendor
 */

use TestHelper\Utility\Association\Finding;

$this->assign('title', 'Associations: ' . ($model ?: ''));

$labels = [
	Finding::DIRECTION_MISMATCH => ['Mismatches', 'danger'],
	Finding::DIRECTION_COLUMN_MISSING => ['Declared, but column missing', 'danger'],
	Finding::DIRECTION_TYPE => ['Key column types', 'dark'],
	Finding::DIRECTION_RULE => ['Cascade rules', 'dark'],
	Finding::DIRECTION_INDEX => ['Missing indexes', 'dark'],
	Finding::DIRECTION_DB_MISSING => ['Declared, but missing DB constraint', 'warning'],
	Finding::DIRECTION_CODE_MISSING => ['In DB, but no association', 'info'],
	Finding::DIRECTION_UNSUPPORTED => ['Not auto-verifiable', 'secondary'],
];

$severityBadge = function (string $severity): string {
	return match ($severity) {
		'error' => 'bg-danger',
		'warning' => 'bg-warning text-dark',
		default => 'bg-info text-dark',
	};
};
?>

<div class="page-header mb-3">
	<h1><?php echo $this->TestHelper->icon('sitemap'); ?> Associations: <?php echo h($model); ?></h1>
	<p>
		<?php echo $this->Html->link($this->TestHelper->icon('previous') . ' Back to matrix', ['action' => 'index', '?' => ['vendor' => $includeVendor ? 1 : null]], ['escapeTitle' => false, 'class' => 'btn btn-sm btn-outline-secondary']); ?>
	</p>
</div>

<?php if (!$findings) { ?>
	<div class="alert alert-success"><?php echo $this->TestHelper->icon('check'); ?> All associations agree with the database.</div>
<?php } ?>

<?php foreach ($groupOrder as $direction) { ?>
	<?php [$title, $color] = $labels[$direction]; ?>
	<?php $items = $grouped[$direction] ?? []; ?>
	<?php if (!$items) {
		continue;
	} ?>
	<div class="card shadow-sm mb-4">
		<div class="card-header bg-<?php echo $color; ?> <?php echo in_array($color, ['warning', 'info'], true) ? 'text-dark' : 'text-white'; ?>">
			<h5 class="mb-0"><?php echo h($title); ?> <span class="badge bg-light text-dark"><?php echo count($items); ?></span></h5>
		</div>
		<div class="card-body">
			<?php foreach ($items as $finding) { ?>
				<div class="mb-3 pb-3 border-bottom">
					<div class="d-flex gap-2 align-items-start">
						<span class="badge <?php echo $severityBadge($finding->severity); ?>"><?php echo h($finding->severity); ?></span>
						<div class="flex-grow-1">
							<div><code><?php echo h($finding->associationType); ?></code> <?php echo h($finding->message); ?></div>
							<?php if ($finding->fixSnippet) { ?>
								<pre class="bg-light border rounded p-2 mt-2 mb-0"><code><?php echo h($finding->fixSnippet); ?></code></pre>
							<?php } ?>
						</div>
					</div>
				</div>
			<?php } ?>
		</div>
	</div>
<?php } ?>
