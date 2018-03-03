
<?php if (isset($url)) {
	$url  += [
		'prefix' => null,
		'plugin' => null,
	];
	$output = $this->TestHelper->prepareUrl($url, $this->request->data['verbose']);
	echo '<pre>';
	echo '[' . implode(', ', $output) . ']';
	echo '</pre>';
} ?>
