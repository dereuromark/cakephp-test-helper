<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\EtchatMessage[]|\Cake\Collection\CollectionInterface $etchatMessages
 */
?>
<table class="table">
	<?php
	foreach ($files as $file) {
		?>
		<tr>
			<td>
				<?php echo h($file['name']); ?>
			</td>
			<td>
				<?php
				if (!$file['hasTestCase'] || empty($file['needsNoTestCase'])) {
					echo $this->Format->yesNo($file['hasTestCase']);
				}
				?>
			</td>
			<td>
				<?php
				if (!$file['hasTestCase']) {
					echo '<span class="btn btn-default btn-xs">';
					echo $this->Form->postLink($this->Format->icon('plus', ['title' => 'Generate test case']), ['action' => 'controllers', $this->request->param('pass')[0]], ['escapeTitle' => false, 'data' => ['name' => $file['name']]]);
					echo '</span>';
				} else {
					?>
					<?php echo $this->Html->link($this->Format->icon('play', ['title' => 'Run tests']), ['action' => 'run', '?' => ['test' => $file['testCase']]], ['escapeTitle' => false, 'target' => '_blank', 'class' => 'run', 'data-test-case' => $file['testCase']]); ?>

					<?php echo $this->Html->link($this->Format->icon('bar-chart', ['title' => 'Coverage']), ['action' => 'coverage', '?' => ['test' => $file['testCase'], 'name' => $file['name'], 'type' => $file['type']]], ['escapeTitle' => false, 'target' => '_blank', 'class' => 'coverage', 'data-test-case' => $file['testCase'], 'data-name' => $file['name'], 'data-type' => $file['type']]); ?>
				<?php } ?>
			</td>
			<td>
				<small><?php echo h($file['testCase']); ?></small>
			</td>
		</tr>
		<?php
	}
	?>
</table>

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

			var testCase = $(this).data('test-case');
			var url = '/test-helper/test-cases/run.json';
			remoteModal(testCase, url);
		});

		$('.coverage').click(function (e) {
			e.preventDefault();

			var testCase = $(this).data('test-case');
			var url = '/test-helper/test-cases/coverage.json';
			remoteModal(testCase, url);
		});
	});

	var remoteModal = function(testCase, url) {
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
				test: testCase
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
		})

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

