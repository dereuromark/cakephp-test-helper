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
	jQuery(document).ready(function () {
		$('.run').click(function (e) {
			e.preventDefault();

			var url = $(this).attr('href');
			remoteModal(url);
		});

		$('.coverage').click(function (e) {
			e.preventDefault();


			var url = $(this).attr('href');
			remoteModal(url);
		});
	});

	var remoteModal = function(url) {
		// reset modal body with a spinner or empty content
		var spinner = "<div class='text-center'><i class='fa fa-spinner fa-spin fa-5x fa-fw'></i></div>";

		$("#modal-default .modal-body").html(spinner);

		$.ajax({
			type: "post",
			url: url,
			beforeSend: function(xhr) {
				xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
			},
			data: {
			},
			dataType: "json",
			success: function(response) { //so, if data is retrieved, store it in html
				$("#modal-default .modal-body").html(response.output);
				$("#modal-default .btn-primary").show();
			},
			error: function(e) {
				var errorText = 'Error ' + e.status + ': ' + e.statusText;

				alert("Fehler bei der Anfrage! " + errorText);
				$("#modal-default .modal-body").html('');
			}
		});

		$("#modal-default").modal("show");
	};
</script>
<?php $this->end();?>

<div class="modal fade" tabindex="-1" role="dialog" id="modal-default">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Running test suite</h4>
			</div>
			<div class="modal-body">
				<p>One fine body&hellip;</p>
			</div>
			<div class="modal-footer">
				<?php if (false) { ?>
					<button type="button" class="btn btn-primary pull-left" style="display: none"><i class="fa fa-repeat"></i> Re-Run</button>
				<?php } ?>
				<button type="button" class="btn btn-default " data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>

