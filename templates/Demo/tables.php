<?php
/**
 * @var \App\View\AppView $this
 * @var string $currentDemoLayout
 * @var string $layoutApp
 * @var string $layoutPlugin
 */
?>

<?php echo $this->element('TestHelper.demo_layout_switcher'); ?>

<h1>Tables Demo</h1>

<p>This page demonstrates various table styles and configurations.</p>

<div class="row">
	<div class="col-lg-6">
		<h2>Basic Table</h2>
		<table class="table">
			<thead>
				<tr>
					<th>ID</th>
					<th>Name</th>
					<th>Email</th>
					<th>Role</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>1</td>
					<td>John Doe</td>
					<td>john@example.com</td>
					<td>Admin</td>
				</tr>
				<tr>
					<td>2</td>
					<td>Jane Smith</td>
					<td>jane@example.com</td>
					<td>Editor</td>
				</tr>
				<tr>
					<td>3</td>
					<td>Bob Wilson</td>
					<td>bob@example.com</td>
					<td>User</td>
				</tr>
			</tbody>
		</table>

		<h2>Striped Table</h2>
		<table class="table table-striped">
			<thead>
				<tr>
					<th>#</th>
					<th>Product</th>
					<th>Price</th>
					<th>Stock</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>1</td>
					<td>Widget A</td>
					<td>$19.99</td>
					<td>150</td>
				</tr>
				<tr>
					<td>2</td>
					<td>Widget B</td>
					<td>$29.99</td>
					<td>85</td>
				</tr>
				<tr>
					<td>3</td>
					<td>Widget C</td>
					<td>$39.99</td>
					<td>42</td>
				</tr>
				<tr>
					<td>4</td>
					<td>Widget D</td>
					<td>$49.99</td>
					<td>0</td>
				</tr>
			</tbody>
		</table>

		<h2>Bordered Table</h2>
		<table class="table table-bordered">
			<thead>
				<tr>
					<th>Date</th>
					<th>Description</th>
					<th>Amount</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>2024-01-15</td>
					<td>Payment received</td>
					<td class="text-success">+$500.00</td>
				</tr>
				<tr>
					<td>2024-01-14</td>
					<td>Service fee</td>
					<td class="text-danger">-$25.00</td>
				</tr>
				<tr>
					<td>2024-01-10</td>
					<td>Refund issued</td>
					<td class="text-danger">-$100.00</td>
				</tr>
			</tbody>
			<tfoot>
				<tr>
					<th colspan="2">Total</th>
					<th>$375.00</th>
				</tr>
			</tfoot>
		</table>
	</div>

	<div class="col-lg-6">
		<h2>Hover Table</h2>
		<table class="table table-hover">
			<thead>
				<tr>
					<th>Task</th>
					<th>Status</th>
					<th>Priority</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>Update documentation</td>
					<td><span class="badge bg-success">Complete</span></td>
					<td>Low</td>
				</tr>
				<tr>
					<td>Fix login bug</td>
					<td><span class="badge bg-warning text-dark">In Progress</span></td>
					<td>High</td>
				</tr>
				<tr>
					<td>Add new feature</td>
					<td><span class="badge bg-secondary">Pending</span></td>
					<td>Medium</td>
				</tr>
			</tbody>
		</table>

		<h2>Small / Condensed Table</h2>
		<table class="table table-sm table-striped">
			<thead>
				<tr>
					<th>Code</th>
					<th>Country</th>
					<th>Population</th>
				</tr>
			</thead>
			<tbody>
				<tr><td>US</td><td>United States</td><td>331M</td></tr>
				<tr><td>CN</td><td>China</td><td>1.4B</td></tr>
				<tr><td>IN</td><td>India</td><td>1.3B</td></tr>
				<tr><td>BR</td><td>Brazil</td><td>213M</td></tr>
				<tr><td>DE</td><td>Germany</td><td>83M</td></tr>
			</tbody>
		</table>

		<h2>Dark Table</h2>
		<table class="table table-dark table-striped">
			<thead>
				<tr>
					<th>Server</th>
					<th>Status</th>
					<th>Uptime</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>web-01</td>
					<td><span class="badge bg-success">Online</span></td>
					<td>99.9%</td>
				</tr>
				<tr>
					<td>web-02</td>
					<td><span class="badge bg-success">Online</span></td>
					<td>99.8%</td>
				</tr>
				<tr>
					<td>db-01</td>
					<td><span class="badge bg-danger">Offline</span></td>
					<td>95.2%</td>
				</tr>
			</tbody>
		</table>

		<h2>Contextual Row Colors</h2>
		<table class="table">
			<thead>
				<tr>
					<th>Class</th>
					<th>Description</th>
					<th>Example</th>
				</tr>
			</thead>
			<tbody>
				<tr class="table-primary"><td>.table-primary</td><td>Primary/info row</td><td>Info</td></tr>
				<tr class="table-success"><td>.table-success</td><td>Success row</td><td>Saved</td></tr>
				<tr class="table-warning"><td>.table-warning</td><td>Warning row</td><td>Pending</td></tr>
				<tr class="table-danger"><td>.table-danger</td><td>Danger row</td><td>Error</td></tr>
				<tr class="table-info"><td>.table-info</td><td>Info row</td><td>Note</td></tr>
				<tr class="table-light"><td>.table-light</td><td>Light row</td><td>Muted</td></tr>
				<tr class="table-dark"><td>.table-dark</td><td>Dark row</td><td>Strong</td></tr>
			</tbody>
		</table>
	</div>
</div>

<hr>

<h2>Responsive Table</h2>
<p>Scroll horizontally on small screens:</p>
<div class="table-responsive">
	<table class="table table-bordered">
		<thead>
			<tr>
				<th>ID</th>
				<th>First Name</th>
				<th>Last Name</th>
				<th>Email</th>
				<th>Phone</th>
				<th>Address</th>
				<th>City</th>
				<th>Country</th>
				<th>Postal Code</th>
				<th>Created</th>
				<th>Actions</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>1</td>
				<td>John</td>
				<td>Doe</td>
				<td>john.doe@example.com</td>
				<td>+1-555-0100</td>
				<td>123 Main Street</td>
				<td>New York</td>
				<td>USA</td>
				<td>10001</td>
				<td>2024-01-15</td>
				<td>
					<button class="btn btn-sm btn-primary">Edit</button>
					<button class="btn btn-sm btn-danger">Delete</button>
				</td>
			</tr>
			<tr>
				<td>2</td>
				<td>Jane</td>
				<td>Smith</td>
				<td>jane.smith@example.com</td>
				<td>+1-555-0101</td>
				<td>456 Oak Avenue</td>
				<td>Los Angeles</td>
				<td>USA</td>
				<td>90001</td>
				<td>2024-01-14</td>
				<td>
					<button class="btn btn-sm btn-primary">Edit</button>
					<button class="btn btn-sm btn-danger">Delete</button>
				</td>
			</tr>
		</tbody>
	</table>
</div>

<hr>

<h2>Table with Actions</h2>
<p>Common pattern for CRUD tables:</p>
<table class="table table-striped table-hover">
	<thead class="table-dark">
		<tr>
			<th style="width: 50px;">
				<input type="checkbox" class="form-check-input" title="Select all">
			</th>
			<th>Name</th>
			<th>Status</th>
			<th>Created</th>
			<th class="text-end" style="width: 150px;">Actions</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><input type="checkbox" class="form-check-input"></td>
			<td><strong>Project Alpha</strong><br><small class="text-muted">Web Application</small></td>
			<td><span class="badge bg-success">Active</span></td>
			<td>Jan 15, 2024</td>
			<td class="text-end">
				<div class="btn-group btn-group-sm">
					<a href="#" class="btn btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
					<a href="#" class="btn btn-outline-secondary" title="Edit"><i class="fas fa-edit"></i></a>
					<a href="#" class="btn btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></a>
				</div>
			</td>
		</tr>
		<tr>
			<td><input type="checkbox" class="form-check-input"></td>
			<td><strong>Project Beta</strong><br><small class="text-muted">Mobile App</small></td>
			<td><span class="badge bg-warning text-dark">Draft</span></td>
			<td>Jan 10, 2024</td>
			<td class="text-end">
				<div class="btn-group btn-group-sm">
					<a href="#" class="btn btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
					<a href="#" class="btn btn-outline-secondary" title="Edit"><i class="fas fa-edit"></i></a>
					<a href="#" class="btn btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></a>
				</div>
			</td>
		</tr>
		<tr>
			<td><input type="checkbox" class="form-check-input"></td>
			<td><strong>Project Gamma</strong><br><small class="text-muted">API Service</small></td>
			<td><span class="badge bg-secondary">Archived</span></td>
			<td>Dec 20, 2023</td>
			<td class="text-end">
				<div class="btn-group btn-group-sm">
					<a href="#" class="btn btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
					<a href="#" class="btn btn-outline-secondary" title="Edit"><i class="fas fa-edit"></i></a>
					<a href="#" class="btn btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></a>
				</div>
			</td>
		</tr>
	</tbody>
</table>
