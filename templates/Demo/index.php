<?php
/**
 * @var \App\View\AppView $this
 * @var string $currentDemoLayout
 * @var string $layoutApp
 * @var string $layoutPlugin
 */

?>

<?php echo $this->element('TestHelper.demo_layout_switcher'); ?>

<h1>Demo</h1>

<p>Browse demo pages to see how various UI elements render with your current layout/theme.</p>

<div class="row">
	<div class="col-md-6">
		<h2>Forms & Inputs</h2>
		<ul>
			<li><?php echo $this->Html->link('Form Elements', ['action' => 'formElements']); ?> - All form input types and states</li>
			<li><?php echo $this->Html->link('Buttons & Links', ['action' => 'buttons']); ?> - Button styles, groups, and CakePHP link helpers</li>
		</ul>

		<h2>Layout & Structure</h2>
		<ul>
			<li><?php echo $this->Html->link('Tables', ['action' => 'tables']); ?> - Table styles, responsive tables, CRUD patterns</li>
			<li><?php echo $this->Html->link('Typography', ['action' => 'typography']); ?> - Headings, text, lists, colors</li>
			<li><?php echo $this->Html->link('HTML5 Elements', ['action' => 'html5Elements']); ?> - Semantic HTML5 elements</li>
		</ul>
	</div>

	<div class="col-md-6">
		<h2>Navigation</h2>
		<ul>
			<li><?php echo $this->Html->link('Navigation', ['action' => 'navigation']); ?> - Breadcrumbs, tabs, pills, navbars</li>
			<li><?php echo $this->Html->link('Pagination', ['action' => 'pagination']); ?> - Pagination styles and sizes</li>
		</ul>

		<h2>Feedback</h2>
		<ul>
			<li><?php echo $this->Html->link('Flash Messages', ['action' => 'flashMessages']); ?> - All flash message types</li>
		</ul>
	</div>
</div>
