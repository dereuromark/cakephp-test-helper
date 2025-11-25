<?php
/**
 * @var \App\View\AppView $this
 * @var string $currentDemoLayout
 * @var string $layoutApp
 * @var string $layoutPlugin
 */
?>

<?php echo $this->element('TestHelper.demo_layout_switcher'); ?>

<h1>Buttons & Links Demo</h1>

<p>This page demonstrates various button styles, link types, and CakePHP helpers.</p>

<div class="row">
	<div class="col-lg-6">
		<h2>Button Variants</h2>
		<p>
			<button type="button" class="btn btn-primary">Primary</button>
			<button type="button" class="btn btn-secondary">Secondary</button>
			<button type="button" class="btn btn-success">Success</button>
			<button type="button" class="btn btn-danger">Danger</button>
			<button type="button" class="btn btn-warning">Warning</button>
			<button type="button" class="btn btn-info">Info</button>
			<button type="button" class="btn btn-light">Light</button>
			<button type="button" class="btn btn-dark">Dark</button>
			<button type="button" class="btn btn-link">Link</button>
		</p>

		<h2>Outline Buttons</h2>
		<p>
			<button type="button" class="btn btn-outline-primary">Primary</button>
			<button type="button" class="btn btn-outline-secondary">Secondary</button>
			<button type="button" class="btn btn-outline-success">Success</button>
			<button type="button" class="btn btn-outline-danger">Danger</button>
			<button type="button" class="btn btn-outline-warning">Warning</button>
			<button type="button" class="btn btn-outline-info">Info</button>
			<button type="button" class="btn btn-outline-dark">Dark</button>
		</p>

		<h2>Button Sizes</h2>
		<p>
			<button type="button" class="btn btn-primary btn-lg">Large</button>
			<button type="button" class="btn btn-primary">Default</button>
			<button type="button" class="btn btn-primary btn-sm">Small</button>
		</p>

		<h2>Button States</h2>
		<p>
			<button type="button" class="btn btn-primary">Normal</button>
			<button type="button" class="btn btn-primary active">Active</button>
			<button type="button" class="btn btn-primary" disabled>Disabled</button>
		</p>

		<h2>Block Buttons</h2>
		<div class="d-grid gap-2">
			<button type="button" class="btn btn-primary">Block Button</button>
			<button type="button" class="btn btn-secondary">Block Button</button>
		</div>

		<h2 class="mt-4">Button Groups</h2>
		<div class="btn-group" role="group">
			<button type="button" class="btn btn-primary">Left</button>
			<button type="button" class="btn btn-primary">Middle</button>
			<button type="button" class="btn btn-primary">Right</button>
		</div>

		<h3 class="mt-3">Button Toolbar</h3>
		<div class="btn-toolbar" role="toolbar">
			<div class="btn-group me-2" role="group">
				<button type="button" class="btn btn-secondary">1</button>
				<button type="button" class="btn btn-secondary">2</button>
				<button type="button" class="btn btn-secondary">3</button>
			</div>
			<div class="btn-group me-2" role="group">
				<button type="button" class="btn btn-secondary">4</button>
				<button type="button" class="btn btn-secondary">5</button>
			</div>
			<div class="btn-group" role="group">
				<button type="button" class="btn btn-secondary">6</button>
			</div>
		</div>
	</div>

	<div class="col-lg-6">
		<h2>Buttons with Icons</h2>
		<p>
			<button type="button" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
			<button type="button" class="btn btn-success"><i class="fas fa-check me-1"></i> Submit</button>
			<button type="button" class="btn btn-danger"><i class="fas fa-trash me-1"></i> Delete</button>
			<button type="button" class="btn btn-secondary"><i class="fas fa-times me-1"></i> Cancel</button>
		</p>
		<p>
			<button type="button" class="btn btn-outline-primary"><i class="fas fa-download me-1"></i> Download</button>
			<button type="button" class="btn btn-outline-secondary"><i class="fas fa-upload me-1"></i> Upload</button>
			<button type="button" class="btn btn-outline-info"><i class="fas fa-print me-1"></i> Print</button>
		</p>

		<h3>Icon-Only Buttons</h3>
		<p>
			<button type="button" class="btn btn-primary" title="Edit"><i class="fas fa-edit"></i></button>
			<button type="button" class="btn btn-success" title="Add"><i class="fas fa-plus"></i></button>
			<button type="button" class="btn btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
			<button type="button" class="btn btn-secondary" title="Settings"><i class="fas fa-cog"></i></button>
		</p>

		<h2>Dropdown Buttons</h2>
		<div class="btn-group">
			<button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
				Dropdown
			</button>
			<ul class="dropdown-menu">
				<li><a class="dropdown-item" href="#">Action</a></li>
				<li><a class="dropdown-item" href="#">Another action</a></li>
				<li><hr class="dropdown-divider"></li>
				<li><a class="dropdown-item" href="#">Separated link</a></li>
			</ul>
		</div>

		<div class="btn-group">
			<button type="button" class="btn btn-success">Split Button</button>
			<button type="button" class="btn btn-success dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
				<span class="visually-hidden">Toggle Dropdown</span>
			</button>
			<ul class="dropdown-menu">
				<li><a class="dropdown-item" href="#">Action</a></li>
				<li><a class="dropdown-item" href="#">Another action</a></li>
			</ul>
		</div>

		<h2 class="mt-4">Loading Buttons</h2>
		<p>
			<button type="button" class="btn btn-primary" disabled>
				<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
				Loading...
			</button>
			<button type="button" class="btn btn-secondary" disabled>
				<span class="spinner-grow spinner-grow-sm me-1" role="status" aria-hidden="true"></span>
				Processing...
			</button>
		</p>

		<h2>Close Button</h2>
		<p>
			<button type="button" class="btn-close" aria-label="Close"></button>
			<button type="button" class="btn-close" disabled aria-label="Close"></button>
		</p>
		<div class="p-3 bg-dark d-inline-block">
			<button type="button" class="btn-close btn-close-white" aria-label="Close"></button>
		</div>
	</div>
</div>

<hr>

<h2>CakePHP Html Helper Links</h2>

<div class="row">
	<div class="col-lg-6">
		<h3>Basic Links</h3>
		<p><?php echo $this->Html->link('Simple Link', '#'); ?></p>
		<p><?php echo $this->Html->link('Link with Class', '#', ['class' => 'btn btn-primary']); ?></p>
		<p><?php echo $this->Html->link('Link with Icon', '#', ['class' => 'btn btn-secondary', 'escape' => false]); ?></p>

		<h3>Links with Confirm</h3>
		<p><?php echo $this->Html->link('Delete Item', '#', [
			'class' => 'btn btn-danger',
			'confirm' => 'Are you sure you want to delete this item?',
		]); ?></p>

		<h3>External Links</h3>
		<p><?php echo $this->Html->link('External Link', 'https://example.com', ['target' => '_blank']); ?></p>
		<p><?php echo $this->Html->link('External with Icon', 'https://example.com', [
			'target' => '_blank',
			'class' => 'btn btn-outline-primary',
			'escape' => false,
		]); ?> <i class="fas fa-external-link-alt"></i></p>
	</div>

	<div class="col-lg-6">
		<h3>Form PostLink</h3>
		<p>For actions that modify data (requires POST):</p>
		<?php echo $this->Form->postLink('Delete (PostLink)', '#', [
			'class' => 'btn btn-danger',
			'confirm' => 'Are you sure?',
			'block' => true,
		]); ?>

		<?php echo $this->Form->postLink('Archive (PostLink)', '#', [
			'class' => 'btn btn-warning',
			'data' => ['status' => 'archived'],
			'block' => true,
		]); ?>

		<h3 class="mt-3">Form PostButton</h3>
		<?php echo $this->Form->postButton('Submit as POST', '#', [
			'class' => 'btn btn-success',
		]); ?>

		<h3 class="mt-3">Code Examples</h3>
		<pre><code>// Simple link
echo $this->Html->link('Text', ['action' => 'view', $id]);

// Link with confirm
echo $this->Html->link('Delete', ['action' => 'delete', $id], [
    'confirm' => 'Are you sure?',
]);

// PostLink (for delete/modify actions)
echo $this->Form->postLink('Delete', ['action' => 'delete', $id], [
    'confirm' => 'Are you sure?',
    'block' => true, // Important inside forms!
]);</code></pre>
	</div>
</div>

<?php // Render any postLinks that were blocked ?>
<?php echo $this->fetch('postLink'); ?>
