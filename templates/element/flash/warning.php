<?php
/**
 * @var \Cake\View\View $this
 * @var array $params
 * @var string $message
 */
if (!isset($params['escape']) || $params['escape'] !== false) {
	$message = h($message);
}
?>
<div class="alert alert-warning alert-dismissible fade show" role="alert">
	<i class="fas fa-exclamation-triangle me-2"></i>
	<?php echo $message; ?>
	<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
