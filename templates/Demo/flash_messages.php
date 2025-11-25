<?php
/**
 * @var \App\View\AppView $this
 * @var string $currentDemoLayout
 * @var string $layoutApp
 * @var string $layoutPlugin
 */
?>

<?php echo $this->element('TestHelper.demo_layout_switcher'); ?>

<h1>Flash Messages Demo</h1>

<p>This page demonstrates all available flash message types in the TestHelper plugin.</p>

<div class="row">
	<div class="col-md-6">
		<h2>Trigger Flash Messages</h2>
		<p>Click a button to trigger a flash message (will redirect and show at top):</p>

		<?php echo $this->Form->create(null, ['url' => ['action' => 'flashMessages']]); ?>
		<?php echo $this->Form->hidden('type', ['id' => 'flash-type']); ?>
		<?php echo $this->Form->control('message', [
			'type' => 'text',
			'label' => 'Custom Message (optional)',
			'placeholder' => 'Leave empty for default message',
		]); ?>

		<div class="btn-group mt-3" role="group">
			<button type="submit" class="btn btn-secondary" onclick="document.getElementById('flash-type').value='default'">
				Default
			</button>
			<button type="submit" class="btn btn-success" onclick="document.getElementById('flash-type').value='success'">
				Success
			</button>
			<button type="submit" class="btn btn-danger" onclick="document.getElementById('flash-type').value='error'">
				Error
			</button>
			<button type="submit" class="btn btn-warning" onclick="document.getElementById('flash-type').value='warning'">
				Warning
			</button>
			<button type="submit" class="btn btn-info" onclick="document.getElementById('flash-type').value='info'">
				Info
			</button>
		</div>
		<?php echo $this->Form->end(); ?>
	</div>

	<div class="col-md-6">
		<h2>Usage in Controller</h2>
		<pre><code>// Default flash message
$this->Flash->set('Your message here.');

// Success message
$this->Flash->success('Operation completed!');

// Error message
$this->Flash->error('Something went wrong.');

// Warning message
$this->Flash->warning('Please review this.');

// Info message
$this->Flash->info('Did you know...');</code></pre>
	</div>
</div>

<hr class="my-4">

<h2>Static Examples</h2>
<p>All flash message types rendered directly (without redirect):</p>

<h3>Default</h3>
<?php echo $this->element('TestHelper.flash/default', ['message' => 'This is a default flash message.', 'params' => []]); ?>

<h3>Success</h3>
<?php echo $this->element('TestHelper.flash/success', ['message' => 'This is a success flash message.', 'params' => []]); ?>

<h3>Error</h3>
<?php echo $this->element('TestHelper.flash/error', ['message' => 'This is an error flash message.', 'params' => []]); ?>

<h3>Warning</h3>
<?php echo $this->element('TestHelper.flash/warning', ['message' => 'This is a warning flash message.', 'params' => []]); ?>

<h3>Info</h3>
<?php echo $this->element('TestHelper.flash/info', ['message' => 'This is an info flash message.', 'params' => []]); ?>

<hr class="my-4">

<h2>With HTML Content</h2>
<p>Flash messages can contain HTML when <code>escape => false</code> is set:</p>

<?php echo $this->element('TestHelper.flash/info', [
	'message' => 'This message contains <strong>bold text</strong> and a <a href="#">link</a>.',
	'params' => ['escape' => false],
]); ?>

<pre><code>$this->Flash->info(
    'Message with &lt;strong&gt;HTML&lt;/strong&gt;',
    ['escape' => false]
);</code></pre>
