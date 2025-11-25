<?php
/**
 * @var \Cake\View\View $this
 * @var array $params
 * @var string $message
 */
$class = $params['class'] ?? 'info';
if (!isset($params['escape']) || $params['escape'] !== false) {
	$message = h($message);
}
?>
<div class="alert alert-<?php echo $class; ?> alert-dismissible fade show" role="alert">
	<i class="fas fa-info-circle me-2"></i>
	<?php echo $message; ?>
	<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
