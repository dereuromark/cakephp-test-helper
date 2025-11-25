<?php
/**
 * @var \App\View\AppView $this
 * @var string $currentDemoLayout
 * @var string $layoutApp
 * @var string $layoutPlugin
 */
?>

<?php echo $this->element('TestHelper.demo_layout_switcher'); ?>

<h1>Pagination Demo</h1>

<p>This page demonstrates various pagination styles. Note: These are static HTML examples showing how pagination should render.</p>

<div class="row">
	<div class="col-lg-6">
		<h2>Basic Pagination</h2>
		<nav aria-label="Page navigation">
			<ul class="pagination">
				<li class="page-item"><a class="page-link" href="#">Previous</a></li>
				<li class="page-item"><a class="page-link" href="#">1</a></li>
				<li class="page-item"><a class="page-link" href="#">2</a></li>
				<li class="page-item active"><a class="page-link" href="#">3</a></li>
				<li class="page-item"><a class="page-link" href="#">4</a></li>
				<li class="page-item"><a class="page-link" href="#">5</a></li>
				<li class="page-item"><a class="page-link" href="#">Next</a></li>
			</ul>
		</nav>

		<h2>With First/Last</h2>
		<nav aria-label="Page navigation">
			<ul class="pagination">
				<li class="page-item"><a class="page-link" href="#">&laquo; First</a></li>
				<li class="page-item"><a class="page-link" href="#">&lsaquo; Prev</a></li>
				<li class="page-item"><a class="page-link" href="#">1</a></li>
				<li class="page-item"><a class="page-link" href="#">2</a></li>
				<li class="page-item active"><a class="page-link" href="#">3</a></li>
				<li class="page-item"><a class="page-link" href="#">4</a></li>
				<li class="page-item"><a class="page-link" href="#">5</a></li>
				<li class="page-item"><a class="page-link" href="#">Next &rsaquo;</a></li>
				<li class="page-item"><a class="page-link" href="#">Last &raquo;</a></li>
			</ul>
		</nav>

		<h2>With Disabled States</h2>
		<nav aria-label="Page navigation">
			<ul class="pagination">
				<li class="page-item disabled"><span class="page-link">&laquo; First</span></li>
				<li class="page-item disabled"><span class="page-link">&lsaquo; Prev</span></li>
				<li class="page-item active"><a class="page-link" href="#">1</a></li>
				<li class="page-item"><a class="page-link" href="#">2</a></li>
				<li class="page-item"><a class="page-link" href="#">3</a></li>
				<li class="page-item"><a class="page-link" href="#">Next &rsaquo;</a></li>
				<li class="page-item"><a class="page-link" href="#">Last &raquo;</a></li>
			</ul>
		</nav>

		<h2>With Icons</h2>
		<nav aria-label="Page navigation">
			<ul class="pagination">
				<li class="page-item"><a class="page-link" href="#"><i class="fas fa-angle-double-left"></i></a></li>
				<li class="page-item"><a class="page-link" href="#"><i class="fas fa-angle-left"></i></a></li>
				<li class="page-item"><a class="page-link" href="#">1</a></li>
				<li class="page-item"><a class="page-link" href="#">2</a></li>
				<li class="page-item active"><a class="page-link" href="#">3</a></li>
				<li class="page-item"><a class="page-link" href="#">4</a></li>
				<li class="page-item"><a class="page-link" href="#">5</a></li>
				<li class="page-item"><a class="page-link" href="#"><i class="fas fa-angle-right"></i></a></li>
				<li class="page-item"><a class="page-link" href="#"><i class="fas fa-angle-double-right"></i></a></li>
			</ul>
		</nav>

		<h2>With Ellipsis</h2>
		<nav aria-label="Page navigation">
			<ul class="pagination">
				<li class="page-item"><a class="page-link" href="#">1</a></li>
				<li class="page-item"><a class="page-link" href="#">2</a></li>
				<li class="page-item disabled"><span class="page-link">...</span></li>
				<li class="page-item"><a class="page-link" href="#">10</a></li>
				<li class="page-item active"><a class="page-link" href="#">11</a></li>
				<li class="page-item"><a class="page-link" href="#">12</a></li>
				<li class="page-item disabled"><span class="page-link">...</span></li>
				<li class="page-item"><a class="page-link" href="#">99</a></li>
				<li class="page-item"><a class="page-link" href="#">100</a></li>
			</ul>
		</nav>
	</div>

	<div class="col-lg-6">
		<h2>Pagination Sizes</h2>

		<h3>Large</h3>
		<nav aria-label="Page navigation">
			<ul class="pagination pagination-lg">
				<li class="page-item"><a class="page-link" href="#">Prev</a></li>
				<li class="page-item active"><a class="page-link" href="#">1</a></li>
				<li class="page-item"><a class="page-link" href="#">2</a></li>
				<li class="page-item"><a class="page-link" href="#">3</a></li>
				<li class="page-item"><a class="page-link" href="#">Next</a></li>
			</ul>
		</nav>

		<h3>Default</h3>
		<nav aria-label="Page navigation">
			<ul class="pagination">
				<li class="page-item"><a class="page-link" href="#">Prev</a></li>
				<li class="page-item active"><a class="page-link" href="#">1</a></li>
				<li class="page-item"><a class="page-link" href="#">2</a></li>
				<li class="page-item"><a class="page-link" href="#">3</a></li>
				<li class="page-item"><a class="page-link" href="#">Next</a></li>
			</ul>
		</nav>

		<h3>Small</h3>
		<nav aria-label="Page navigation">
			<ul class="pagination pagination-sm">
				<li class="page-item"><a class="page-link" href="#">Prev</a></li>
				<li class="page-item active"><a class="page-link" href="#">1</a></li>
				<li class="page-item"><a class="page-link" href="#">2</a></li>
				<li class="page-item"><a class="page-link" href="#">3</a></li>
				<li class="page-item"><a class="page-link" href="#">Next</a></li>
			</ul>
		</nav>

		<h2>Alignment</h2>

		<h3>Start (default)</h3>
		<nav aria-label="Page navigation">
			<ul class="pagination justify-content-start">
				<li class="page-item"><a class="page-link" href="#">Prev</a></li>
				<li class="page-item active"><a class="page-link" href="#">1</a></li>
				<li class="page-item"><a class="page-link" href="#">2</a></li>
				<li class="page-item"><a class="page-link" href="#">Next</a></li>
			</ul>
		</nav>

		<h3>Center</h3>
		<nav aria-label="Page navigation">
			<ul class="pagination justify-content-center">
				<li class="page-item"><a class="page-link" href="#">Prev</a></li>
				<li class="page-item active"><a class="page-link" href="#">1</a></li>
				<li class="page-item"><a class="page-link" href="#">2</a></li>
				<li class="page-item"><a class="page-link" href="#">Next</a></li>
			</ul>
		</nav>

		<h3>End</h3>
		<nav aria-label="Page navigation">
			<ul class="pagination justify-content-end">
				<li class="page-item"><a class="page-link" href="#">Prev</a></li>
				<li class="page-item active"><a class="page-link" href="#">1</a></li>
				<li class="page-item"><a class="page-link" href="#">2</a></li>
				<li class="page-item"><a class="page-link" href="#">Next</a></li>
			</ul>
		</nav>
	</div>
</div>

<hr>

<h2>Simple Prev/Next</h2>
<nav aria-label="Page navigation">
	<ul class="pagination">
		<li class="page-item"><a class="page-link" href="#">&larr; Previous</a></li>
		<li class="page-item"><a class="page-link" href="#">Next &rarr;</a></li>
	</ul>
</nav>

<hr>

<h2>With Page Info</h2>
<div class="d-flex justify-content-between align-items-center">
	<div class="text-muted">
		Showing <strong>21</strong> to <strong>30</strong> of <strong>97</strong> results
	</div>
	<nav aria-label="Page navigation">
		<ul class="pagination mb-0">
			<li class="page-item"><a class="page-link" href="#">Previous</a></li>
			<li class="page-item"><a class="page-link" href="#">1</a></li>
			<li class="page-item"><a class="page-link" href="#">2</a></li>
			<li class="page-item active"><a class="page-link" href="#">3</a></li>
			<li class="page-item"><a class="page-link" href="#">4</a></li>
			<li class="page-item"><a class="page-link" href="#">5</a></li>
			<li class="page-item"><a class="page-link" href="#">Next</a></li>
		</ul>
	</nav>
</div>

<hr>

<h2>With Per-Page Selector</h2>
<div class="d-flex justify-content-between align-items-center">
	<div class="d-flex align-items-center gap-2">
		<label for="per-page" class="text-muted mb-0">Show:</label>
		<select id="per-page" class="form-select form-select-sm" style="width: auto;">
			<option>10</option>
			<option selected>25</option>
			<option>50</option>
			<option>100</option>
		</select>
		<span class="text-muted">per page</span>
	</div>
	<nav aria-label="Page navigation">
		<ul class="pagination pagination-sm mb-0">
			<li class="page-item disabled"><span class="page-link">Prev</span></li>
			<li class="page-item active"><a class="page-link" href="#">1</a></li>
			<li class="page-item"><a class="page-link" href="#">2</a></li>
			<li class="page-item"><a class="page-link" href="#">3</a></li>
			<li class="page-item"><a class="page-link" href="#">4</a></li>
			<li class="page-item"><a class="page-link" href="#">Next</a></li>
		</ul>
	</nav>
</div>

<hr>

<h2>CakePHP Paginator Helper</h2>
<p>In CakePHP templates, you would use the Paginator helper:</p>
<pre><code>&lt;?php
// Basic numbers
echo $this->Paginator->numbers();

// First/Prev/Next/Last links
echo $this->Paginator->first('&laquo; First');
echo $this->Paginator->prev('&lsaquo; Prev');
echo $this->Paginator->numbers();
echo $this->Paginator->next('Next &rsaquo;');
echo $this->Paginator->last('Last &raquo;');

// Counter info
echo $this->Paginator->counter(
    'Page {{page}} of {{pages}}, showing {{current}} records out of {{count}} total'
);
?&gt;</code></pre>
