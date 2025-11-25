<?php
/**
 * @var \App\View\AppView $this
 * @var string $currentDemoLayout
 * @var string $layoutApp
 * @var string $layoutPlugin
 */
?>

<?php echo $this->element('TestHelper.demo_layout_switcher'); ?>

<h1>HTML5 Elements Demo</h1>

<p>This page demonstrates various HTML5 semantic and interactive elements.</p>

<div class="row">
	<div class="col-lg-6">
		<h2>Interactive Elements</h2>

		<h3>Details / Summary</h3>
		<details>
			<summary>Click to expand</summary>
			<p>This content is hidden by default and revealed when the user clicks the summary.</p>
			<p>Useful for FAQs, collapsible sections, and progressive disclosure.</p>
		</details>

		<details open>
			<summary>Initially open details</summary>
			<p>This details element starts in the open state using the <code>open</code> attribute.</p>
		</details>

		<h3>Dialog</h3>
		<dialog id="demo-dialog">
			<h4>Dialog Title</h4>
			<p>This is a native HTML5 dialog element.</p>
			<button onclick="document.getElementById('demo-dialog').close()">Close</button>
		</dialog>
		<button onclick="document.getElementById('demo-dialog').showModal()">Open Dialog</button>

		<hr>

		<h2>Progress Indicators</h2>

		<h3>Progress</h3>
		<p>Determinate progress (70%):</p>
		<progress value="70" max="100">70%</progress>

		<p>Indeterminate progress (no value):</p>
		<progress>Loading...</progress>

		<h3>Meter</h3>
		<p>Disk usage (low - green):</p>
		<meter min="0" max="100" value="25" low="33" high="66" optimum="20">25%</meter>

		<p>Disk usage (medium - yellow):</p>
		<meter min="0" max="100" value="50" low="33" high="66" optimum="20">50%</meter>

		<p>Disk usage (high - red):</p>
		<meter min="0" max="100" value="85" low="33" high="66" optimum="20">85%</meter>

		<hr>

		<h2>Media Elements</h2>

		<h3>Audio</h3>
		<audio controls>
			<source src="https://www.w3schools.com/html/horse.ogg" type="audio/ogg">
			<source src="https://www.w3schools.com/html/horse.mp3" type="audio/mpeg">
			Your browser does not support the audio element.
		</audio>

		<h3>Video</h3>
		<video width="320" height="240" controls poster="https://via.placeholder.com/320x240?text=Video+Poster">
			<source src="https://www.w3schools.com/html/mov_bbb.mp4" type="video/mp4">
			<source src="https://www.w3schools.com/html/mov_bbb.ogg" type="video/ogg">
			Your browser does not support the video element.
		</video>

		<h3>Figure / Figcaption</h3>
		<figure>
			<img src="https://via.placeholder.com/300x200?text=Sample+Image" alt="Sample image" style="max-width: 100%;">
			<figcaption>Fig. 1 - A sample image with a caption using figure and figcaption elements.</figcaption>
		</figure>

		<hr>

		<h2>Output Element</h2>
		<?php echo $this->Form->create(null, ['onsubmit' => 'return false;']); ?>
		<label for="calc-a">Value A:</label>
		<input type="number" id="calc-a" value="50" oninput="document.getElementById('calc-result').value = parseInt(this.value) + parseInt(document.getElementById('calc-b').value)">
		+
		<label for="calc-b">Value B:</label>
		<input type="number" id="calc-b" value="25" oninput="document.getElementById('calc-result').value = parseInt(document.getElementById('calc-a').value) + parseInt(this.value)">
		=
		<output id="calc-result" for="calc-a calc-b">75</output>
		<?php echo $this->Form->end(); ?>
	</div>

	<div class="col-lg-6">
		<h2>Text Semantics</h2>

		<h3>Mark (Highlight)</h3>
		<p>You can use the mark element to <mark>highlight important text</mark> in a paragraph.</p>

		<h3>Time</h3>
		<p>The meeting is scheduled for <time datetime="2024-12-25T14:00">December 25th at 2:00 PM</time>.</p>
		<p>Published on <time datetime="2024-01-15" pubdate>January 15, 2024</time>.</p>

		<h3>Abbreviation</h3>
		<p>The <abbr title="World Wide Web Consortium">W3C</abbr> sets web standards.</p>
		<p><abbr title="HyperText Markup Language">HTML</abbr> is used to structure web pages.</p>

		<h3>Address</h3>
		<address>
			Written by <a href="mailto:author@example.com">John Doe</a>.<br>
			Visit us at: Example.com<br>
			Box 123, City<br>
			Country
		</address>

		<h3>Blockquote</h3>
		<blockquote cite="https://www.example.com/quote-source">
			<p>The only way to do great work is to love what you do.</p>
			<footer>— <cite>Steve Jobs</cite></footer>
		</blockquote>

		<h3>Inline Quote</h3>
		<p>As they say, <q>actions speak louder than words</q>.</p>

		<h3>Definition</h3>
		<p><dfn>HTML</dfn> is the standard markup language for creating web pages.</p>

		<h3>Code Elements</h3>
		<p>Inline code: Use the <code>echo</code> function to output text.</p>
		<p>Keyboard input: Press <kbd>Ctrl</kbd> + <kbd>C</kbd> to copy.</p>
		<p>Sample output: <samp>Error: File not found</samp></p>
		<p>Variable: The <var>x</var> variable represents the user's input.</p>

		<pre><code>&lt;?php
echo "Hello, World!";
?&gt;</code></pre>

		<h3>Insertions and Deletions</h3>
		<p>Price: <del>$99.99</del> <ins>$79.99</ins></p>
		<p>This feature is <del>deprecated</del> <ins>no longer supported</ins>.</p>

		<h3>Subscript and Superscript</h3>
		<p>Water formula: H<sub>2</sub>O</p>
		<p>Einstein's equation: E = mc<sup>2</sup></p>

		<h3>Small Print</h3>
		<p>Regular text with <small>small print disclaimer text</small>.</p>

		<h3>Word Break Opportunity</h3>
		<p>This is a very long URL: https://www.example.com/<wbr>really/<wbr>long/<wbr>path/<wbr>to/<wbr>some/<wbr>resource</p>
	</div>
</div>

<hr>

<h2>Semantic Structure Elements</h2>

<p>These elements define the structure of a page (shown as styled boxes for demonstration):</p>

<div style="border: 2px solid #333; padding: 10px; margin: 10px 0;">
	<header style="background: #e0e0e0; padding: 10px; margin-bottom: 10px;">
		<strong>&lt;header&gt;</strong> - Page or section header
	</header>

	<nav style="background: #d0d0d0; padding: 10px; margin-bottom: 10px;">
		<strong>&lt;nav&gt;</strong> - Navigation links
	</nav>

	<main style="background: #f0f0f0; padding: 10px; margin-bottom: 10px;">
		<strong>&lt;main&gt;</strong> - Main content area

		<div style="display: flex; gap: 10px; margin-top: 10px;">
			<article style="background: #fff; border: 1px solid #ccc; padding: 10px; flex: 2;">
				<strong>&lt;article&gt;</strong> - Self-contained content
				<section style="background: #fafafa; border: 1px solid #ddd; padding: 10px; margin-top: 10px;">
					<strong>&lt;section&gt;</strong> - Thematic grouping
				</section>
			</article>

			<aside style="background: #f5f5f5; border: 1px solid #ccc; padding: 10px; flex: 1;">
				<strong>&lt;aside&gt;</strong> - Sidebar content
			</aside>
		</div>
	</main>

	<footer style="background: #e0e0e0; padding: 10px;">
		<strong>&lt;footer&gt;</strong> - Page or section footer
	</footer>
</div>
