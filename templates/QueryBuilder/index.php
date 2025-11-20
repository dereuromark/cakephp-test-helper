<?php
/**
 * @var \Cake\View\View $this
 * @var string $sqlQuery
 * @var array|null $result
 * @var string|null $error
 * @var string $dialect
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
					<div class="mb-3">
						<?php
						echo $this->Form->control('dialect', [
							'type' => 'select',
							'label' => 'Database Dialect',
							'options' => [
								'mysql' => 'MySQL / MariaDB',
								'postgres' => 'PostgreSQL',
							],
							'default' => $dialect,
							'class' => 'form-select',
						]);
						?>
					</div>

					<?php
					echo $this->Form->control('sql_query', [
						'type' => 'textarea',
						'label' => 'Enter your SQL query',
						'placeholder' => "SELECT users.id, users.name, COUNT(posts.id) AS post_count\nFROM users\nLEFT JOIN posts ON posts.user_id = users.id\nWHERE users.active = 1\nGROUP BY users.id\nORDER BY post_count DESC\nLIMIT 10",
						'class' => 'form-control font-monospace',
						'rows' => 10,
						'value' => $sqlQuery,
						'required' => true,
					]);
					?>

					<div class="mb-3">
						<button class="btn btn-sm btn-outline-info" type="button" data-bs-toggle="collapse" data-bs-target="#supportedFeatures" aria-expanded="false" aria-controls="supportedFeatures">
							<?php echo $this->TestHelper->icon('next'); ?> Show Supported SQL Features
						</button>
					</div>

					<div class="collapse" id="supportedFeatures">
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
				<h5 class="mb-0"><?php echo $this->TestHelper->icon('yes'); ?> Conversion Results</h5>
			</div>
			<div class="card-body">
				<!-- Nav tabs -->
				<ul class="nav nav-tabs" id="resultTabs" role="tablist">
					<li class="nav-item" role="presentation">
						<button class="nav-link active" id="code-tab" data-bs-toggle="tab" data-bs-target="#code-panel" type="button" role="tab" aria-controls="code-panel" aria-selected="true">
							<?php echo $this->TestHelper->icon('next'); ?> Generated Code
						</button>
					</li>
					<?php if (!empty($result['parsed'])) { ?>
					<li class="nav-item" role="presentation">
						<button class="nav-link" id="debug-tab" data-bs-toggle="tab" data-bs-target="#debug-panel" type="button" role="tab" aria-controls="debug-panel" aria-selected="false">
							<?php echo $this->TestHelper->icon('next'); ?> Debug (Parsed Structure)
						</button>
					</li>
					<?php } ?>
				</ul>

				<!-- Tab panes -->
				<div class="tab-content" id="resultTabsContent">
					<!-- Generated Code Tab -->
					<div class="tab-pane fade show active" id="code-panel" role="tabpanel" aria-labelledby="code-tab">
						<div class="mt-3 mb-3">
							<button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard()">
								<?php echo $this->TestHelper->icon('next'); ?> Copy to Clipboard
							</button>
						</div>

						<pre id="generated-code" class="line-numbers"><code class="language-php"><?php echo h($result['code']); ?></code></pre>

						<?php if (!empty($result['optimizations'])) { ?>
						<div class="alert alert-warning mt-3">
							<strong>⚡ Optimization Suggestions:</strong>
							<ul class="mb-0 mt-2">
								<?php foreach ($result['optimizations'] as $optimization) { ?>
									<li><?php echo h($optimization); ?></li>
								<?php } ?>
							</ul>
						</div>
						<?php } ?>

						<div class="alert alert-info mt-3">
							<strong><?php echo $this->TestHelper->icon('next'); ?> Important Notes:</strong>
							<ul class="mb-0 mt-2">
								<li>The generated code is production-ready but review for your specific use case</li>
								<li>Window functions and CTEs include helpful guidance comments (limited CakePHP 5.x support)</li>
								<li>Always test the generated code and verify it produces the expected results</li>
								<li>Add proper type hints and use statements as needed</li>
							</ul>
						</div>
					</div>

					<!-- Debug Tab -->
					<?php if (!empty($result['parsed'])) { ?>
					<div class="tab-pane fade" id="debug-panel" role="tabpanel" aria-labelledby="debug-tab">
						<div class="mt-3">
							<pre class="bg-light p-3 rounded"><code><?php echo h(print_r($result['parsed'], true)); ?></code></pre>
						</div>
					</div>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
</div>

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
	'SELECT' => [
		'Simple SELECT' => 'SELECT * FROM users WHERE active = 1 ORDER BY created DESC LIMIT 10',
		'SELECT with JOIN and GROUP BY' => "SELECT users.id, users.name, COUNT(posts.id) AS post_count\nFROM users\nLEFT JOIN posts ON posts.user_id = users.id\nWHERE users.active = 1\nGROUP BY users.id\nORDER BY post_count DESC\nLIMIT 10",
		'SELECT with String Functions' => "SELECT CONCAT(first_name, ' ', last_name) AS full_name,\n       UPPER(email) AS email_upper,\n       SUBSTRING(phone, 1, 3) AS area_code\nFROM users\nWHERE TRIM(city) != ''",
		'SELECT with Date Functions' => "SELECT id, name,\n       YEAR(created) AS signup_year,\n       DATEDIFF(NOW(), last_login) AS days_since_login\nFROM users\nWHERE created > DATE_SUB(NOW(), INTERVAL 1 YEAR)",
		'SELECT with CASE Expression' => "SELECT id, name,\n       CASE\n           WHEN status = 1 THEN 'Active'\n           WHEN status = 2 THEN 'Pending'\n           ELSE 'Inactive'\n       END AS status_label\nFROM users",
		'SELECT with Subquery' => "SELECT id, username, email\nFROM users\nWHERE id IN (SELECT user_id FROM active_sessions WHERE last_activity > NOW() - INTERVAL 1 HOUR)",
		'SELECT with UNION' => "SELECT id, name, 'user' AS type FROM users WHERE active = 1\nUNION\nSELECT id, name, 'admin' AS type FROM admins WHERE active = 1",
		'Complex SELECT with Multiple JOINs' => "SELECT DISTINCT u.id, u.name,\n       COUNT(p.id) AS post_count,\n       COALESCE(SUM(p.views), 0) AS total_views\nFROM users u\nLEFT JOIN posts p ON p.user_id = u.id\nINNER JOIN user_roles ur ON ur.user_id = u.id\nWHERE u.active = 1 AND ur.role_id IN (1, 2, 3)\nGROUP BY u.id\nHAVING COUNT(p.id) > 5\nORDER BY total_views DESC\nLIMIT 20",
	],
	'INSERT' => [
		'Simple INSERT' => "INSERT INTO users (username, email, active) VALUES ('john', 'john@example.com', 1)",
		'Bulk INSERT (Multiple Rows)' => "INSERT INTO users (username, email, active)\nVALUES\n    ('alice', 'alice@example.com', 1),\n    ('bob', 'bob@example.com', 1),\n    ('charlie', 'charlie@example.com', 0)",
		'INSERT with NOW() Function' => "INSERT INTO posts (title, content, user_id, created)\nVALUES ('My Post', 'Content here', 5, NOW())",
	],
	'UPDATE' => [
		'Simple UPDATE' => "UPDATE users SET active = 0, modified = NOW() WHERE last_login < '2023-01-01'",
		'UPDATE with CASE' => "UPDATE users\nSET status = CASE\n    WHEN last_login > NOW() - INTERVAL 7 DAY THEN 'active'\n    WHEN last_login > NOW() - INTERVAL 30 DAY THEN 'inactive'\n    ELSE 'dormant'\nEND\nWHERE id > 0",
		'Multi-table UPDATE with JOIN' => "UPDATE users u\nJOIN profiles p ON p.user_id = u.id\nSET u.last_login = NOW(), p.updated = NOW()\nWHERE u.id = 5",
		'UPDATE with Subquery' => "UPDATE users\nSET premium = 1\nWHERE id IN (SELECT user_id FROM subscriptions WHERE status = 'active')",
	],
	'DELETE' => [
		'Simple DELETE' => "DELETE FROM users WHERE active = 0 AND created < '2020-01-01'",
		'DELETE with Complex WHERE' => "DELETE FROM sessions\nWHERE (last_activity < NOW() - INTERVAL 24 HOUR)\n   OR (created < NOW() - INTERVAL 7 DAY AND user_id IS NULL)",
		'DELETE with Subquery' => "DELETE FROM posts\nWHERE user_id IN (SELECT id FROM users WHERE deleted = 1)",
	],
	'Advanced' => [
		'Window Function (ROW_NUMBER)' => "SELECT id, name, salary,\n       ROW_NUMBER() OVER (PARTITION BY department_id ORDER BY salary DESC) AS rank\nFROM employees",
		'CTE (Common Table Expression)' => "WITH active_users AS (\n    SELECT * FROM users WHERE active = 1\n)\nSELECT * FROM active_users WHERE created > '2023-01-01'",
		'Nested Subqueries' => "SELECT u.id, u.name,\n       (SELECT COUNT(*) FROM posts p WHERE p.user_id = u.id) AS post_count\nFROM users u\nWHERE u.id IN (\n    SELECT DISTINCT user_id\n    FROM posts\n    WHERE created > NOW() - INTERVAL 30 DAY\n)",
		'Complex UNION with ORDER BY' => "SELECT id, title, 'post' AS type, created FROM posts WHERE status = 'published'\nUNION ALL\nSELECT id, title, 'page' AS type, created FROM pages WHERE published = 1\nORDER BY created DESC\nLIMIT 50",
	],
];
?>

<div class="row mt-4">
	<div class="col-12">
		<div class="card shadow-sm">
			<div class="card-header bg-secondary text-white">
				<h5 class="mb-0"><?php echo $this->TestHelper->icon('next'); ?> Examples - Features Showcase</h5>
			</div>
			<div class="card-body">
				<!-- Nav tabs for example types -->
				<ul class="nav nav-pills mb-3" id="exampleTabs" role="tablist">
					<?php $isFirst = true; ?>
					<?php foreach (array_keys($examples) as $type) { ?>
					<li class="nav-item" role="presentation">
						<button class="nav-link <?php echo $isFirst ? 'active' : ''; ?>" id="<?php echo strtolower($type); ?>-examples-tab" data-bs-toggle="tab" data-bs-target="#<?php echo strtolower($type); ?>-examples" type="button" role="tab" aria-controls="<?php echo strtolower($type); ?>-examples" aria-selected="<?php echo $isFirst ? 'true' : 'false'; ?>">
							<?php echo h($type); ?>
						</button>
					</li>
					<?php $isFirst = false; ?>
					<?php } ?>
				</ul>

				<!-- Tab panes for examples -->
				<div class="tab-content" id="exampleTabsContent">
					<?php $isFirst = true; ?>
					<?php foreach ($examples as $type => $typeExamples) { ?>
					<div class="tab-pane fade <?php echo $isFirst ? 'show active' : ''; ?>" id="<?php echo strtolower($type); ?>-examples" role="tabpanel" aria-labelledby="<?php echo strtolower($type); ?>-examples-tab">
						<?php foreach ($typeExamples as $title => $exampleSql) { ?>
							<div class="example-item <?php if ($title !== array_key_first($typeExamples)) { ?>mt-4<?php } ?>">
								<h6 class="text-primary"><?php echo h($title); ?></h6>
								<pre><code class="language-sql"><?php echo h($exampleSql); ?></code></pre>
								<?php echo $this->Html->link(
									$this->TestHelper->icon('next') . ' Try It',
									['?' => ['sql' => $exampleSql]],
									['escape' => false, 'class' => 'btn btn-sm btn-outline-primary mb-2'],
								); ?>
							</div>
						<?php } ?>
					</div>
					<?php $isFirst = false; ?>
					<?php } ?>
				</div>
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
