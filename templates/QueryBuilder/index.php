<?php
/**
 * @var \Cake\View\View $this
 * @var string $sqlQuery
 * @var array|null $result
 * @var string|null $error
 */

$this->assign('title', 'SQL to CakePHP Query Builder Converter');
?>

<div class="page-header">
	<h1><?php echo $this->TestHelper->icon('query-builder'); ?> SQL to CakePHP Query Builder</h1>
	<p class="lead">Convert raw SQL queries into CakePHP Query Builder code</p>
	<?php echo $this->Html->link(
		$this->TestHelper->icon('dashboard') . ' Back to Dashboard',
		['controller' => 'TestHelper', 'action' => 'index'],
		['escape' => false, 'class' => 'btn btn-sm btn-secondary'],
	); ?>
</div>

<div class="row">
	<div class="col-12">
		<div class="card shadow-sm">
			<div class="card-header bg-primary text-white">
				<h5 class="mb-0"><?php echo $this->TestHelper->icon('database'); ?> SQL Query Input</h5>
			</div>
			<div class="card-body">
				<?php echo $this->Form->create(null, ['type' => 'post']); ?>
					<?php
					echo $this->Form->control('sql_query', [
						'type' => 'textarea',
						'label' => 'Enter your SQL query (MySQL syntax)',
						'placeholder' => "SELECT users.id, users.name, COUNT(posts.id) AS post_count\nFROM users\nLEFT JOIN posts ON posts.user_id = users.id\nWHERE users.active = 1\nGROUP BY users.id\nORDER BY post_count DESC\nLIMIT 10",
						'class' => 'form-control font-monospace',
						'rows' => 10,
						'value' => $sqlQuery,
						'required' => true,
					]);
					?>

					<div class="alert alert-success mb-3">
						<strong><?php echo $this->TestHelper->icon('next'); ?> Supported SQL Features:</strong>
						<ul class="mb-0 mt-2">
							<li><strong>SELECT:</strong> fields, aliases, DISTINCT, JOINs, WHERE, GROUP BY, HAVING, ORDER BY, LIMIT, OFFSET, UNION/UNION ALL</li>
							<li><strong>Functions:</strong> String (CONCAT, SUBSTRING, TRIM, UPPER, LOWER, COALESCE), Date (NOW, YEAR, MONTH, DATEDIFF), Aggregate (COUNT, SUM, AVG, MIN, MAX)</li>
							<li><strong>Advanced:</strong> CASE expressions, subqueries (recursive), window functions (with guidance), CTEs (WITH clause)</li>
							<li><strong>INSERT:</strong> Single and bulk INSERT (multiple rows)</li>
							<li><strong>UPDATE:</strong> Single and multi-table UPDATE (with JOINs)</li>
							<li><strong>DELETE:</strong> With full WHERE condition support</li>
						</ul>
					</div>

					<div class="mt-3">
						<?php echo $this->Form->submit('Convert to CakePHP', ['class' => 'btn btn-primary btn-lg']); ?>
					</div>
				<?php echo $this->Form->end(); ?>
			</div>
		</div>
	</div>
</div>

<?php if ($result) { ?>
<div class="row mt-4">
	<div class="col-12">
		<div class="card shadow-sm border-success">
			<div class="card-header bg-success text-white">
				<h5 class="mb-0"><?php echo $this->TestHelper->icon('yes'); ?> Generated CakePHP Query Builder Code</h5>
			</div>
			<div class="card-body">
				<div class="mb-3">
					<button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard()">
						<?php echo $this->TestHelper->icon('next'); ?> Copy to Clipboard
					</button>
				</div>

				<pre id="generated-code" class="line-numbers"><code class="language-php"><?php echo h($result['code']); ?></code></pre>

				<div class="alert alert-info mt-3">
					<strong><?php echo $this->TestHelper->icon('next'); ?> Important Notes:</strong>
					<ul class="mb-0 mt-2">
						<li><strong>v2.0 Features:</strong> Full support for WHERE/HAVING conditions, string/date functions, CASE expressions, subqueries, and multi-table updates!</li>
						<li>The generated code is production-ready but review for your specific use case</li>
						<li>Window functions and CTEs include helpful guidance comments (limited CakePHP 5.x support)</li>
						<li>Always test the generated code and verify it produces the expected results</li>
						<li>Add proper type hints and use statements as needed</li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</div>

<?php if (!empty($result['parsed'])) { ?>
<div class="row mt-4">
	<div class="col-12">
		<div class="card shadow-sm">
			<div class="card-header bg-info text-white">
				<h5 class="mb-0"><?php echo $this->TestHelper->icon('next'); ?> Parsed SQL Structure (Debug)</h5>
			</div>
			<div class="card-body">
				<pre class="bg-light p-3 rounded"><code><?php echo h(print_r($result['parsed'], true)); ?></code></pre>
			</div>
		</div>
	</div>
</div>
<?php } ?>

<?php } ?>

<?php if ($error) { ?>
<div class="row mt-4">
	<div class="col-12">
		<div class="alert alert-danger">
			<strong><?php echo $this->TestHelper->icon('no'); ?> Error:</strong> <?php echo h($error); ?>
		</div>
	</div>
</div>
<?php } ?>

<?php
$examples = [
	'Simple SELECT' => 'SELECT * FROM users WHERE active = 1 ORDER BY created DESC LIMIT 10',
	'SELECT with JOIN and GROUP BY' => "SELECT users.id, users.name, COUNT(posts.id) AS post_count\nFROM users\nLEFT JOIN posts ON posts.user_id = users.id\nWHERE users.active = 1\nGROUP BY users.id\nORDER BY post_count DESC\nLIMIT 10",
	'UPDATE' => "UPDATE users SET active = 0, modified = NOW() WHERE last_login < '2023-01-01'",
	'INSERT' => "INSERT INTO users (username, email, active) VALUES ('john', 'john@example.com', 1)",
	'DELETE' => "DELETE FROM users WHERE active = 0 AND created < '2020-01-01'",
];
?>

<div class="row mt-4">
	<div class="col-12">
		<div class="card shadow-sm">
			<div class="card-header bg-secondary text-white">
				<h5 class="mb-0"><?php echo $this->TestHelper->icon('next'); ?> Examples</h5>
			</div>
			<div class="card-body">
				<?php foreach ($examples as $title => $exampleSql) { ?>
					<h6<?php if ($title !== array_key_first($examples)) { ?> class="mt-3"<?php } ?>><?php echo h($title); ?></h6>
					<pre><code class="language-sql"><?php echo h($exampleSql); ?></code></pre>
					<?php echo $this->Html->link(
						$this->TestHelper->icon('next') . ' Try It',
						['?' => ['sql' => $exampleSql]],
						['escape' => false, 'class' => 'btn btn-sm btn-outline-primary'],
					); ?>
				<?php } ?>
			</div>
		</div>
	</div>
</div>

<script>
function copyToClipboard() {
	const code = document.getElementById('generated-code').innerText;
	navigator.clipboard.writeText(code).then(function() {
		alert('Code copied to clipboard!');
	}, function(err) {
		console.error('Could not copy text: ', err);
		alert('Failed to copy code. Please copy manually.');
	});
}
</script>

<!-- Prism.js for Syntax Highlighting -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-markup-templating.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-sql.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.min.js"></script>

<style>
.font-monospace {
	font-family: 'Courier New', Courier, monospace;
	font-size: 0.9em;
}

pre code {
	display: block;
	white-space: pre;
	overflow-x: auto;
}

/* Enhance Prism.js styling */
pre[class*="language-"] {
	margin: 0;
	padding: 1em;
	border-radius: 0.375rem;
}

.line-numbers .line-numbers-rows {
	border-right: 1px solid #999;
}

/* SQL textarea highlighting */
#sql_query {
	font-family: 'Consolas', 'Monaco', 'Courier New', monospace !important;
	tab-size: 4;
}
</style>
