<?php
/**
 * @var \Cake\View\View $this
 * @var string $title
 */
?>
<!DOCTYPE html>
<html>
<head>
	<?php echo $this->Html->charset(); ?>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>
		<?php echo $this->fetch('title'); ?> - TestHelper
	</title>

	<!-- Bootstrap 5 CSS -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

	<!-- Font Awesome 6 -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

	<?php echo $this->Html->meta('icon'); ?>
	<?php echo $this->fetch('meta'); ?>
	<?php echo $this->fetch('css'); ?>

	<style>
		body {
			padding-top: 20px;
			padding-bottom: 40px;
		}
		.navbar {
			margin-bottom: 30px;
		}
		.page-header {
			margin-bottom: 30px;
			padding-bottom: 10px;
			border-bottom: 2px solid #e0e0e0;
		}
		pre {
			background-color: #f8f9fa;
			padding: 15px;
			border-radius: 4px;
			border: 1px solid #dee2e6;
		}
		.flash-message {
			margin-bottom: 20px;
		}
		.inline-list {
			list-style: none;
			padding: 0;
		}
		.inline-list li {
			display: inline-block;
			margin-right: 10px;
		}
	</style>
</head>
<body>
	<!-- Navigation -->
	<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
		<div class="container">
			<a class="navbar-brand" href="<?php echo $this->Url->build(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index']); ?>">
				<i class="fas fa-flask"></i> TestHelper
			</a>
			<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
				<span class="navbar-toggler-icon"></span>
			</button>
			<div class="collapse navbar-collapse" id="navbarNav">
				<ul class="navbar-nav">
					<li class="nav-item">
						<?php echo $this->Html->link('Home', ['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index'], ['class' => 'nav-link']); ?>
					</li>
					<li class="nav-item">
						<?php echo $this->Html->link('Test Cases', ['plugin' => 'TestHelper', 'controller' => 'TestCases', 'action' => 'controller'], ['class' => 'nav-link']); ?>
					</li>
					<li class="nav-item">
						<?php echo $this->Html->link('Plugins', ['plugin' => 'TestHelper', 'controller' => 'Plugins', 'action' => 'index'], ['class' => 'nav-link']); ?>
					</li>
					<li class="nav-item">
						<?php echo $this->Html->link('Migrations', ['plugin' => 'TestHelper', 'controller' => 'Migrations', 'action' => 'index'], ['class' => 'nav-link']); ?>
					</li>
				</ul>
			</div>
		</div>
	</nav>

	<!-- Main Content -->
	<div class="container">
		<!-- Flash Messages -->
		<div class="flash-messages">
			<?php echo $this->Flash->render(); ?>
		</div>

		<!-- Page Content -->
		<?php echo $this->fetch('content'); ?>
	</div>

	<!-- Footer -->
	<footer class="container mt-5 pt-3 border-top text-center text-muted">
		<p>TestHelper Plugin for CakePHP | Development Tool | @dereuromark</p>
	</footer>

	<!-- Bootstrap 5 JS Bundle (includes Popper) -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

	<?php echo $this->fetch('script'); ?>
</body>
</html>
