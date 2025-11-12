<?php
/**
 * @var \App\View\AppView $this
 */
?>

<style>
	.icon-no {
		color: red;
	}
	.icon-yes {
		color: green;
	}
</style>

<?php $this->append('script');?>
<script>
	document.addEventListener('DOMContentLoaded', function () {
		// Handle run test clicks
		document.querySelectorAll('.run').forEach(function(link) {
			link.addEventListener('click', function (e) {
				e.preventDefault();
				var url = this.getAttribute('href');
				remoteModal(url);
			});
		});

		// Handle coverage clicks
		document.querySelectorAll('.coverage').forEach(function(link) {
			link.addEventListener('click', function (e) {
				e.preventDefault();
				var url = this.getAttribute('href');
				remoteModal(url);
			});
		});
	});

	var remoteModal = function(url) {
		// reset modal body with a spinner or empty content
		var spinner = '<div class="text-center"><?php echo $this->TestHelper->icon('loading', ['class' => 'fa-spin fa-5x fa-fw']); ?></div>';
		var modalBody = document.querySelector("#modal-default .modal-body");
		var modalFooterButtons = document.querySelector("#modal-default .modal-footer .buttons");

		modalBody.innerHTML = spinner;

		fetch(url, {
			method: 'GET',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
				'X-CSRF-Token': '<?php echo $this->request->getParam('_csrfToken'); ?>',
				'X-Requested-With': 'XMLHttpRequest',
				'Accept': 'application/json'
			}
		})
		.then(function(response) {
			if (!response.ok) {
				throw new Error('Error ' + response.status + ': ' + response.statusText);
			}
			return response.json();
		})
		.then(function(data) {
			var output = data.output;
			var button = '<div><a href="' + url + '" target="_blank" class="btn btn-primary">Details</a></div>';

			modalBody.innerHTML = output;
			modalFooterButtons.innerHTML = button;

			var primaryButton = document.querySelector("#modal-default .btn-primary");
			if (primaryButton) {
				primaryButton.style.display = 'block';
			}
		})
		.catch(function(error) {
			alert("Fehler bei der Anfrage! " + error.message);
			modalBody.innerHTML = '';
		});

		// Show modal using Bootstrap 5 API
		var modalElement = document.getElementById('modal-default');
		var modal = new bootstrap.Modal(modalElement);
		modal.show();
	};
</script>
<?php $this->end();?>

<div class="modal fade" tabindex="-1" role="dialog" id="modal-default">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Running test suite</h4>
			</div>
			<div class="modal-body">
				<p>&hellip;</p>
			</div>
			<div class="modal-footer">
				<div class="buttons">

				</div>
				<?php if (false) { ?>
					<button type="button" class="btn btn-primary pull-left" style="display: none"><i class="fa fa-repeat"></i> Re-Run</button>
				<?php } ?>
				<button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>

