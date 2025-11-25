<?php
/**
 * Layout switcher element for Demo pages.
 *
 * @var \App\View\AppView $this
 * @var string $currentDemoLayout Current layout ('app' or 'plugin')
 * @var string $layoutApp Value for app layout
 * @var string $layoutPlugin Value for plugin layout
 */

$isAppLayout = $currentDemoLayout === $layoutApp;
$switchToLayout = $isAppLayout ? $layoutPlugin : $layoutApp;
$switchToLabel = $isAppLayout ? 'Plugin Layout' : 'App Layout';
$currentLabel = $isAppLayout ? 'App Layout' : 'Plugin Layout';
?>
<div class="demo-layout-switcher mb-3">
	<small class="text-muted">
		<?php echo $this->Html->link('Demo', ['action' => 'index']); ?> |
		Current: <strong><?php echo h($currentLabel); ?></strong> |
		<?php echo $this->Html->link(
			'Switch to ' . $switchToLabel,
			['?' => ['layout' => $switchToLayout]],
		); ?>
	</small>
</div>
