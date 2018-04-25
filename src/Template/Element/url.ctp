<?php
/**
 * @var \App\View\AppView $this
 */
?>
<?php if (isset($url)) {
	$url  += [
		'prefix' => null,
		'plugin' => null,
	];
	$output = $this->TestHelper->prepareUrl($url, $this->request->getData('verbose'));
	echo '<pre>';
	echo '[' . implode(', ', $output) . ']';
	echo '</pre>';
} ?>
