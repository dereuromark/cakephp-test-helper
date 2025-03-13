<?php
/**
 * @var \App\View\AppView $this
 */
?>

<h1>Form Helper Demo</h1>

<?php echo $this->Form->create(); ?>

<fieldset>
	<legend>Form Elements</legend>

<?php echo $this->Form->control('char', []); ?>
<?php echo $this->Form->control('required', ['required' => true]); ?>
<?php echo $this->Form->control('text', ['type' => 'textarea']); ?>

<?php echo $this->Form->control('boolean', ['type' => 'checkbox']); ?>

<?php echo $this->Form->control('select', ['type' => 'select', 'options' => ['One', 'Two']]); ?>
<?php echo $this->Form->control('radio', ['type' => 'radio', 'options' => ['One', 'Two']]); ?>
<?php echo $this->Form->control('checkbox', ['type' => 'select', 'multiple'=> 'checkbox', 'options' => ['One', 'Two']]); ?>

<?php echo $this->Form->control('date', ['type' => 'date']); ?>
<?php echo $this->Form->control('datetime', ['type' => 'datetime']); ?>
<?php echo $this->Form->control('time', ['type' => 'time']); ?>

<?php echo $this->Form->control('file', ['type' => 'file']); ?>

<?php echo $this->Form->submit('Click me'); ?>

</fieldset>

<?php echo $this->Form->end(); ?>
