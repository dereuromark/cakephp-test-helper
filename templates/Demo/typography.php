<?php
/**
 * @var \App\View\AppView $this
 * @var string $currentDemoLayout
 * @var string $layoutApp
 * @var string $layoutPlugin
 */
?>

<?php echo $this->element('TestHelper.demo_layout_switcher'); ?>

<h1>Typography Demo</h1>

<p>This page demonstrates typography styles, headings, text utilities, and list formats.</p>

<div class="row">
	<div class="col-lg-6">
		<h2>Headings</h2>
		<h1>h1. Heading Level 1</h1>
		<h2>h2. Heading Level 2</h2>
		<h3>h3. Heading Level 3</h3>
		<h4>h4. Heading Level 4</h4>
		<h5>h5. Heading Level 5</h5>
		<h6>h6. Heading Level 6</h6>

		<h2 class="mt-4">Display Headings</h2>
		<h1 class="display-1">Display 1</h1>
		<h1 class="display-2">Display 2</h1>
		<h1 class="display-3">Display 3</h1>
		<h1 class="display-4">Display 4</h1>
		<h1 class="display-5">Display 5</h1>
		<h1 class="display-6">Display 6</h1>

		<h2 class="mt-4">Heading with Secondary Text</h2>
		<h3>
			Main Heading
			<small class="text-muted">Secondary text</small>
		</h3>

		<h2 class="mt-4">Paragraphs</h2>
		<p>This is a regular paragraph. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>

		<p class="lead">This is a lead paragraph. It stands out from regular paragraphs and is often used for introductions.</p>

		<p><small>This is small text, useful for fine print or disclaimers.</small></p>

		<h2 class="mt-4">Inline Text Elements</h2>
		<p><strong>Bold text</strong> using &lt;strong&gt;</p>
		<p><b>Bold text</b> using &lt;b&gt;</p>
		<p><em>Italic text</em> using &lt;em&gt;</p>
		<p><i>Italic text</i> using &lt;i&gt;</p>
		<p><u>Underlined text</u> using &lt;u&gt;</p>
		<p><s>Strikethrough text</s> using &lt;s&gt;</p>
		<p><mark>Highlighted text</mark> using &lt;mark&gt;</p>
		<p><abbr title="Abbreviation">Abbr</abbr> with tooltip</p>

		<h2 class="mt-4">Text Alignment</h2>
		<p class="text-start">Left aligned text (text-start)</p>
		<p class="text-center">Center aligned text (text-center)</p>
		<p class="text-end">Right aligned text (text-end)</p>
	</div>

	<div class="col-lg-6">
		<h2>Text Colors</h2>
		<p class="text-primary">Primary text color</p>
		<p class="text-secondary">Secondary text color</p>
		<p class="text-success">Success text color</p>
		<p class="text-danger">Danger text color</p>
		<p class="text-warning bg-dark">Warning text color</p>
		<p class="text-info bg-dark">Info text color</p>
		<p class="text-light bg-dark">Light text color</p>
		<p class="text-dark">Dark text color</p>
		<p class="text-muted">Muted text color</p>

		<h2 class="mt-4">Background Colors</h2>
		<p class="bg-primary text-white p-2">Primary background</p>
		<p class="bg-secondary text-white p-2">Secondary background</p>
		<p class="bg-success text-white p-2">Success background</p>
		<p class="bg-danger text-white p-2">Danger background</p>
		<p class="bg-warning p-2">Warning background</p>
		<p class="bg-info p-2">Info background</p>
		<p class="bg-light p-2">Light background</p>
		<p class="bg-dark text-white p-2">Dark background</p>

		<h2 class="mt-4">Unordered Lists</h2>
		<ul>
			<li>First item</li>
			<li>Second item
				<ul>
					<li>Nested item 1</li>
					<li>Nested item 2</li>
				</ul>
			</li>
			<li>Third item</li>
		</ul>

		<h3>Unstyled List</h3>
		<ul class="list-unstyled">
			<li>Item without bullet</li>
			<li>Another item</li>
			<li>Third item</li>
		</ul>

		<h3>Inline List</h3>
		<ul class="list-inline">
			<li class="list-inline-item">First</li>
			<li class="list-inline-item">Second</li>
			<li class="list-inline-item">Third</li>
		</ul>

		<h2 class="mt-4">Ordered Lists</h2>
		<ol>
			<li>First step</li>
			<li>Second step</li>
			<li>Third step
				<ol>
					<li>Sub-step A</li>
					<li>Sub-step B</li>
				</ol>
			</li>
			<li>Fourth step</li>
		</ol>

		<h2 class="mt-4">Description Lists</h2>
		<dl>
			<dt>Term 1</dt>
			<dd>Definition for term 1</dd>
			<dt>Term 2</dt>
			<dd>Definition for term 2</dd>
		</dl>

		<h3>Horizontal Description List</h3>
		<dl class="row">
			<dt class="col-sm-3">Term</dt>
			<dd class="col-sm-9">Definition</dd>
			<dt class="col-sm-3">Another term</dt>
			<dd class="col-sm-9">Another definition with longer text that may wrap to multiple lines</dd>
			<dt class="col-sm-3">Status</dt>
			<dd class="col-sm-9"><span class="badge bg-success">Active</span></dd>
		</dl>
	</div>
</div>

<hr>

<h2>Blockquotes</h2>
<div class="row">
	<div class="col-lg-6">
		<h3>Default Blockquote</h3>
		<blockquote class="blockquote">
			<p>A well-known quote, contained in a blockquote element.</p>
		</blockquote>

		<h3>With Citation</h3>
		<figure>
			<blockquote class="blockquote">
				<p>The only way to do great work is to love what you do.</p>
			</blockquote>
			<figcaption class="blockquote-footer">
				Steve Jobs in <cite title="Stanford Commencement Speech">his 2005 Stanford Commencement Address</cite>
			</figcaption>
		</figure>
	</div>
	<div class="col-lg-6">
		<h3>Centered Blockquote</h3>
		<figure class="text-center">
			<blockquote class="blockquote">
				<p>A centered blockquote for emphasis.</p>
			</blockquote>
			<figcaption class="blockquote-footer">
				Someone famous
			</figcaption>
		</figure>

		<h3>Right-aligned Blockquote</h3>
		<figure class="text-end">
			<blockquote class="blockquote">
				<p>A right-aligned blockquote.</p>
			</blockquote>
			<figcaption class="blockquote-footer">
				Author name
			</figcaption>
		</figure>
	</div>
</div>

<hr>

<h2>Code & Preformatted Text</h2>
<div class="row">
	<div class="col-lg-6">
		<h3>Inline Code</h3>
		<p>Use <code>&lt;code&gt;</code> for inline code snippets.</p>
		<p>For example: <code>$this->Html->link()</code></p>

		<h3>User Input</h3>
		<p>Press <kbd>Ctrl</kbd> + <kbd>S</kbd> to save.</p>
		<p>Type <kbd>cd /var/www</kbd> to change directory.</p>

		<h3>Sample Output</h3>
		<p><samp>Error: File not found</samp></p>
	</div>
	<div class="col-lg-6">
		<h3>Code Block</h3>
		<pre><code>&lt;?php
namespace App\Controller;

class UsersController extends AppController
{
    public function index()
    {
        $users = $this->Users->find('all');
        $this->set(compact('users'));
    }
}</code></pre>

		<h3>Scrollable Code Block</h3>
		<pre class="pre-scrollable" style="max-height: 150px;"><code>Line 1
Line 2
Line 3
Line 4
Line 5
Line 6
Line 7
Line 8
Line 9
Line 10
Line 11
Line 12</code></pre>
	</div>
</div>
