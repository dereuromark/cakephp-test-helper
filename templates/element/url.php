<?php
/**
 * @var \App\View\AppView $this
 * @var array|null $url
 * @var mixed $params
 */
?>
<?php if (isset($params)) {
	echo '<h3>URL array</h3>';
	echo '<pre>';
	echo $this->TestHelper->url($params, (bool)$this->request->getData('verbose'));
	echo '</pre>';

	echo '<h3>URL path</h3>';
	echo '<p>Note: Path elements only support <code>[Plugin].[Prefix]/[Controller]::[action]</code>. The rest is dropped.';

	echo '<pre>';
	echo $this->TestHelper->urlPath($params);
	echo '</pre>';

} ?>
