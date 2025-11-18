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

					<div class="alert alert-info mb-3">
						<strong><?php echo $this->TestHelper->icon('next'); ?> Supported SQL Features:</strong>
						<ul class="mb-0 mt-2">
							<li><strong>SELECT:</strong> fields (with aliases), JOINs (INNER, LEFT, RIGHT), WHERE, GROUP BY, HAVING, ORDER BY, LIMIT, OFFSET</li>
							<li><strong>INSERT:</strong> basic INSERT INTO ... VALUES ...</li>
							<li><strong>UPDATE:</strong> UPDATE ... SET ... WHERE ...</li>
							<li><strong>DELETE:</strong> DELETE FROM ... WHERE ...</li>
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

				<pre id="generated-code" class="bg-light p-3 rounded"><code class="language-php"><?php echo h($result['code']); ?></code></pre>

				<div class="alert alert-warning mt-3">
					<strong><?php echo $this->TestHelper->icon('next'); ?> Important Notes:</strong>
					<ul class="mb-0 mt-2">
						<li>The generated code is a starting point and may need adjustments for your specific use case</li>
						<li>Complex WHERE/HAVING conditions are shown as TODOs - you'll need to convert them manually to CakePHP format</li>
						<li>Always test the generated code and verify it produces the expected results</li>
						<li>Make sure to add proper type hints and use statements as needed</li>
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
					<pre class="bg-light p-2 rounded mb-2"><code><?php echo h($exampleSql); ?></code></pre>
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
</style>
