<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="col-md-12">

<style>
div.input.date div.ui-select {
	width: 200px;
}
form.ui-listview-filter {
	margin-bottom: 0px;
}
</style>

<h1>HTML5 Demo</h1>

<h2>Details</h2>
<details>
<summary>Copyright 1999-2011.</summary>
<p> - blabla. All Rights Reserved.</p>
<p>All content and graphics on this web site are the property of the company blabla.</p>
</details>

<h2>Meter</h2>
<meter min="0" max="100" value="25"></meter>

<h2>Progress</h2>
<progress value="250" max="1000">
<span id="downloadProgress">25</span>%
</progress>

<h2>Video</h2>
<video src="http://www.quackit.com/video/pass-countdown.ogg" width="300" height="150" controls>
<p>If you are reading this, it is because your browser does not support the HTML5 video element.</p>
</video>

<h2>Form</h2>
<?php
echo $this->Form->create();
/*
array(
	'inputDefaults' => array(
	'div' => array('data-role' => 'fieldcontain')
	)
*/
echo $this->Form->control('some_number', array('type' => 'number', 'step' => 'any'));
echo $this->Form->control('some_number_10', array('type' => 'number', 'step' => 10));

echo $this->Form->control('some_date', array('type' => 'date', 'dateFormat' => 'DMY'));
echo $this->Form->time('some_time', array('timeFormat' => 24));

echo $this->Form->control('some_checkboxes', array('type' => 'select', 'multiple' => 'checkbox', 'options' => array(1, 2, 3)));
echo $this->Form->control('some_radio', array('type' => 'radio', 'multiple' => 'radio', 'options' => array(1, 2, 3)));
echo $this->Form->control('some_select', array('type' => 'select', 'multiple' => true, 'options' => array(1, 2, 3)));

$value = null;
if (!$this->request->getData()) {
	$value = 25;
}
echo $this->Form->control('some_slider', array('type' => 'range', 'min' => 0, 'value' => $value, 'max' => 100));

echo $this->Form->submit(__('Submit')); echo $this->Form->end();
?>

</div>
