<?php
/**
 * @var \App\View\AppView $this
 * @var string $currentDemoLayout
 * @var string $layoutApp
 * @var string $layoutPlugin
 */
?>

<?php echo $this->element('TestHelper.demo_layout_switcher'); ?>

<h1>Navigation Demo</h1>

<p>This page demonstrates various navigation elements including breadcrumbs, tabs, pills, and navbars.</p>

<div class="row">
	<div class="col-lg-6">
		<h2>Breadcrumbs</h2>

		<h3>Basic Breadcrumb</h3>
		<nav aria-label="breadcrumb">
			<ol class="breadcrumb">
				<li class="breadcrumb-item"><a href="#">Home</a></li>
				<li class="breadcrumb-item"><a href="#">Library</a></li>
				<li class="breadcrumb-item active" aria-current="page">Data</li>
			</ol>
		</nav>

		<h3>With Icons</h3>
		<nav aria-label="breadcrumb">
			<ol class="breadcrumb">
				<li class="breadcrumb-item"><a href="#"><i class="fas fa-home"></i> Home</a></li>
				<li class="breadcrumb-item"><a href="#"><i class="fas fa-folder"></i> Projects</a></li>
				<li class="breadcrumb-item active" aria-current="page"><i class="fas fa-file"></i> Document</li>
			</ol>
		</nav>

		<h3>CakePHP Breadcrumbs Helper</h3>
		<pre><code>// In controller or view
$this->Breadcrumbs->add('Home', ['action' => 'index']);
$this->Breadcrumbs->add('Users', ['controller' => 'Users']);
$this->Breadcrumbs->add('Edit');

// In template
echo $this->Breadcrumbs->render();</code></pre>

		<hr>

		<h2>Tabs</h2>

		<h3>Basic Tabs</h3>
		<ul class="nav nav-tabs" role="tablist">
			<li class="nav-item" role="presentation">
				<button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab1" type="button">Home</button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab2" type="button">Profile</button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab3" type="button">Messages</button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link disabled" type="button" disabled>Disabled</button>
			</li>
		</ul>
		<div class="tab-content border border-top-0 p-3">
			<div class="tab-pane fade show active" id="tab1" role="tabpanel">
				<p>Home tab content. This is the active tab by default.</p>
			</div>
			<div class="tab-pane fade" id="tab2" role="tabpanel">
				<p>Profile tab content. User profile information would go here.</p>
			</div>
			<div class="tab-pane fade" id="tab3" role="tabpanel">
				<p>Messages tab content. List of messages would appear here.</p>
			</div>
		</div>

		<h3 class="mt-4">Tabs with Icons</h3>
		<ul class="nav nav-tabs">
			<li class="nav-item">
				<a class="nav-link active" href="#"><i class="fas fa-home me-1"></i> Home</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="#"><i class="fas fa-user me-1"></i> Profile</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="#">
					<i class="fas fa-envelope me-1"></i> Messages
					<span class="badge bg-danger">5</span>
				</a>
			</li>
		</ul>
	</div>

	<div class="col-lg-6">
		<h2>Pills</h2>

		<h3>Basic Pills</h3>
		<ul class="nav nav-pills">
			<li class="nav-item">
				<a class="nav-link active" href="#">Active</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="#">Link</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="#">Another Link</a>
			</li>
			<li class="nav-item">
				<a class="nav-link disabled" href="#">Disabled</a>
			</li>
		</ul>

		<h3 class="mt-4">Vertical Pills</h3>
		<div class="d-flex">
			<div class="nav flex-column nav-pills me-3" role="tablist">
				<button class="nav-link active" data-bs-toggle="pill" data-bs-target="#vpill1" type="button">Home</button>
				<button class="nav-link" data-bs-toggle="pill" data-bs-target="#vpill2" type="button">Profile</button>
				<button class="nav-link" data-bs-toggle="pill" data-bs-target="#vpill3" type="button">Messages</button>
				<button class="nav-link" data-bs-toggle="pill" data-bs-target="#vpill4" type="button">Settings</button>
			</div>
			<div class="tab-content flex-grow-1 border p-3">
				<div class="tab-pane fade show active" id="vpill1" role="tabpanel">Home content</div>
				<div class="tab-pane fade" id="vpill2" role="tabpanel">Profile content</div>
				<div class="tab-pane fade" id="vpill3" role="tabpanel">Messages content</div>
				<div class="tab-pane fade" id="vpill4" role="tabpanel">Settings content</div>
			</div>
		</div>

		<h3 class="mt-4">Fill / Justify</h3>
		<ul class="nav nav-pills nav-fill">
			<li class="nav-item">
				<a class="nav-link active" href="#">Active</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="#">Longer Link</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="#">Link</a>
			</li>
		</ul>

		<hr>

		<h2>List Group Navigation</h2>
		<div class="list-group">
			<a href="#" class="list-group-item list-group-item-action active">
				<i class="fas fa-home me-2"></i> Dashboard
			</a>
			<a href="#" class="list-group-item list-group-item-action">
				<i class="fas fa-users me-2"></i> Users
			</a>
			<a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
				<span><i class="fas fa-envelope me-2"></i> Messages</span>
				<span class="badge bg-primary rounded-pill">14</span>
			</a>
			<a href="#" class="list-group-item list-group-item-action">
				<i class="fas fa-cog me-2"></i> Settings
			</a>
			<a href="#" class="list-group-item list-group-item-action disabled">
				<i class="fas fa-lock me-2"></i> Locked
			</a>
		</div>
	</div>
</div>

<hr>

<h2>Navbars</h2>

<h3>Light Navbar</h3>
<nav class="navbar navbar-expand-lg navbar-light bg-light mb-3">
	<div class="container-fluid">
		<a class="navbar-brand" href="#">Brand</a>
		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarLight">
			<span class="navbar-toggler-icon"></span>
		</button>
		<div class="collapse navbar-collapse" id="navbarLight">
			<ul class="navbar-nav me-auto">
				<li class="nav-item"><a class="nav-link active" href="#">Home</a></li>
				<li class="nav-item"><a class="nav-link" href="#">Features</a></li>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Dropdown</a>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item" href="#">Action</a></li>
						<li><a class="dropdown-item" href="#">Another action</a></li>
						<li><hr class="dropdown-divider"></li>
						<li><a class="dropdown-item" href="#">Something else</a></li>
					</ul>
				</li>
			</ul>
			<form class="d-flex">
				<input class="form-control me-2" type="search" placeholder="Search">
				<button class="btn btn-outline-success" type="submit">Search</button>
			</form>
		</div>
	</div>
</nav>

<h3>Dark Navbar</h3>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-3">
	<div class="container-fluid">
		<a class="navbar-brand" href="#"><i class="fas fa-rocket me-2"></i>AppName</a>
		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarDark">
			<span class="navbar-toggler-icon"></span>
		</button>
		<div class="collapse navbar-collapse" id="navbarDark">
			<ul class="navbar-nav me-auto">
				<li class="nav-item"><a class="nav-link active" href="#">Dashboard</a></li>
				<li class="nav-item"><a class="nav-link" href="#">Reports</a></li>
				<li class="nav-item"><a class="nav-link" href="#">Settings</a></li>
			</ul>
			<ul class="navbar-nav">
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
						<i class="fas fa-user-circle me-1"></i> John Doe
					</a>
					<ul class="dropdown-menu dropdown-menu-end">
						<li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> Profile</a></li>
						<li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> Settings</a></li>
						<li><hr class="dropdown-divider"></li>
						<li><a class="dropdown-item" href="#"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
					</ul>
				</li>
			</ul>
		</div>
	</div>
</nav>

<h3>Primary Color Navbar</h3>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
	<div class="container-fluid">
		<a class="navbar-brand" href="#">Primary Theme</a>
		<div class="collapse navbar-collapse">
			<ul class="navbar-nav me-auto">
				<li class="nav-item"><a class="nav-link active" href="#">Home</a></li>
				<li class="nav-item"><a class="nav-link" href="#">About</a></li>
				<li class="nav-item"><a class="nav-link" href="#">Contact</a></li>
			</ul>
		</div>
	</div>
</nav>
