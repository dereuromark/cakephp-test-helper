<?php
/**
 * @var \App\View\AppView $this
 * @var string $currentDemoLayout
 * @var string $layoutApp
 * @var string $layoutPlugin
 */
?>

<?php echo $this->element('TestHelper.demo_layout_switcher'); ?>

<h1>Form Helper Demo</h1>

<?php echo $this->Form->create(); ?>

<fieldset>
	<legend>Basic Text Inputs</legend>

	<?php echo $this->Form->control('text_basic', ['label' => 'Basic Text']); ?>
	<?php echo $this->Form->control('text_required', ['label' => 'Required Field', 'required' => true]); ?>
	<?php echo $this->Form->control('text_placeholder', ['label' => 'With Placeholder', 'placeholder' => 'Enter something here...']); ?>
	<?php echo $this->Form->control('text_default', ['label' => 'With Default Value', 'default' => 'Default text']); ?>
	<?php echo $this->Form->control('text_disabled', ['label' => 'Disabled Field', 'disabled' => true, 'default' => 'Cannot edit this']); ?>
	<?php echo $this->Form->control('text_readonly', ['label' => 'Readonly Field', 'readonly' => true, 'default' => 'Read only value']); ?>
	<?php echo $this->Form->control('textarea', ['type' => 'textarea', 'label' => 'Textarea', 'rows' => 4]); ?>
</fieldset>

<fieldset>
	<legend>Specialized Text Inputs</legend>

	<?php echo $this->Form->control('password', ['type' => 'password', 'autocomplete' => 'new-password']); ?>
	<?php echo $this->Form->control('email', ['type' => 'email', 'placeholder' => 'user@example.com']); ?>
	<?php echo $this->Form->control('url', ['type' => 'url', 'placeholder' => 'https://example.com']); ?>
	<?php echo $this->Form->control('tel', ['type' => 'tel', 'label' => 'Telephone', 'placeholder' => '+1 (555) 123-4567']); ?>
	<?php echo $this->Form->control('search', ['type' => 'search', 'placeholder' => 'Search...']); ?>
</fieldset>

<fieldset>
	<legend>Number Inputs</legend>

	<?php echo $this->Form->control('number_basic', ['type' => 'number', 'label' => 'Basic Number']); ?>
	<?php echo $this->Form->control('number_minmax', ['type' => 'number', 'label' => 'With Min/Max (0-100)', 'min' => 0, 'max' => 100]); ?>
	<?php echo $this->Form->control('number_step', ['type' => 'number', 'label' => 'With Step (0.01)', 'step' => '0.01', 'placeholder' => '0.00']); ?>
	<?php echo $this->Form->control('number_step10', ['type' => 'number', 'label' => 'With Step (10)', 'step' => 10]); ?>
	<?php echo $this->Form->control('range', ['type' => 'range', 'label' => 'Range Slider', 'min' => 0, 'max' => 100, 'default' => 50]); ?>
</fieldset>

<fieldset>
	<legend>Date and Time Inputs</legend>

	<?php echo $this->Form->control('date', ['type' => 'date']); ?>
	<?php echo $this->Form->control('time', ['type' => 'time']); ?>
	<?php echo $this->Form->control('datetime', ['type' => 'datetime-local', 'label' => 'DateTime Local']); ?>
	<?php echo $this->Form->control('month', ['type' => 'month']); ?>
	<?php echo $this->Form->control('week', ['type' => 'week']); ?>
</fieldset>

<fieldset>
	<legend>Select Elements</legend>

	<?php echo $this->Form->control('select_basic', ['type' => 'select', 'label' => 'Basic Select', 'options' => ['apple' => 'Apple', 'banana' => 'Banana', 'cherry' => 'Cherry']]); ?>
	<?php echo $this->Form->control('select_empty', ['type' => 'select', 'label' => 'With Empty Option', 'options' => ['apple' => 'Apple', 'banana' => 'Banana', 'cherry' => 'Cherry'], 'empty' => '-- Select --']); ?>
	<?php echo $this->Form->control('select_default', ['type' => 'select', 'label' => 'With Default Value', 'options' => ['apple' => 'Apple', 'banana' => 'Banana', 'cherry' => 'Cherry'], 'default' => 'banana']); ?>
	<?php echo $this->Form->control('select_multiple', ['type' => 'select', 'label' => 'Multiple Select', 'options' => ['apple' => 'Apple', 'banana' => 'Banana', 'cherry' => 'Cherry', 'date' => 'Date'], 'multiple' => true]); ?>
	<?php echo $this->Form->control('select_optgroup', ['type' => 'select', 'label' => 'With Optgroups', 'options' => [
		'Fruits' => ['apple' => 'Apple', 'banana' => 'Banana'],
		'Vegetables' => ['carrot' => 'Carrot', 'broccoli' => 'Broccoli'],
	], 'empty' => '-- Choose --']); ?>
</fieldset>

<fieldset>
	<legend>Checkbox and Radio</legend>

	<?php echo $this->Form->control('checkbox_single', ['type' => 'checkbox', 'label' => 'Single Checkbox']); ?>
	<?php echo $this->Form->control('checkbox_checked', ['type' => 'checkbox', 'label' => 'Pre-checked Checkbox', 'checked' => true]); ?>
	<?php echo $this->Form->control('radio_basic', ['type' => 'radio', 'label' => 'Radio Buttons', 'options' => ['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large']]); ?>
	<?php echo $this->Form->control('radio_default', ['type' => 'radio', 'label' => 'Radio with Default', 'options' => ['yes' => 'Yes', 'no' => 'No', 'maybe' => 'Maybe'], 'default' => 'yes']); ?>
	<?php echo $this->Form->control('checkbox_multiple', ['type' => 'select', 'multiple' => 'checkbox', 'label' => 'Multiple Checkboxes', 'options' => ['red' => 'Red', 'green' => 'Green', 'blue' => 'Blue']]); ?>
</fieldset>

<fieldset>
	<legend>Other Inputs</legend>

	<?php echo $this->Form->control('color', ['type' => 'color', 'label' => 'Color Picker', 'default' => '#3498db']); ?>
	<?php echo $this->Form->control('file', ['type' => 'file']); ?>
	<?php echo $this->Form->control('file_multiple', ['type' => 'file', 'label' => 'Multiple Files', 'multiple' => true]); ?>
	<?php echo $this->Form->control('hidden_field', ['type' => 'hidden', 'value' => 'hidden_value']); ?>
	<p class="text-muted"><small>Hidden field included above (not visible)</small></p>
</fieldset>

<fieldset>
	<legend>With Help Text</legend>

	<?php echo $this->Form->control('with_help', ['label' => 'Username', 'help' => 'Choose a unique username between 3-20 characters.']); ?>
	<?php echo $this->Form->control('with_help_password', ['type' => 'password', 'label' => 'Password', 'help' => 'Must be at least 8 characters with one number.', 'autocomplete' => 'new-password']); ?>
</fieldset>

<fieldset>
	<legend>Datalist (Autocomplete)</legend>

	<?php echo $this->Form->control('browser', ['label' => 'Choose a Browser', 'list' => 'browsers', 'placeholder' => 'Start typing...']); ?>
	<datalist id="browsers">
		<option value="Chrome">
		<option value="Firefox">
		<option value="Safari">
		<option value="Edge">
		<option value="Opera">
	</datalist>
</fieldset>

<fieldset>
	<legend>Buttons</legend>

	<?php echo $this->Form->button('Regular Button'); ?>
	<?php echo $this->Form->button('Primary Button', ['class' => 'btn btn-primary']); ?>
	<?php echo $this->Form->button('Secondary Button', ['class' => 'btn btn-secondary']); ?>
	<?php echo $this->Form->submit('Submit Button'); ?>
	<?php echo $this->Form->submit('Disabled Submit', ['disabled' => true]); ?>
</fieldset>

<?php echo $this->Form->end(); ?>
