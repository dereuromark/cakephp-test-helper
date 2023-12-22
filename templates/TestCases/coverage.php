<?php
/**
 * @var \App\View\AppView $this
 * @var array $result
 */

?>
<h1><?php echo h($this->request->getQuery('test')); ?></h1>

<code><?php echo h($result['command']); ?></code>
<br><br>

<div style="float: right">
	<?php echo $this->Html->link('Refresh', ['?' => ['force' => true] + $this->request->getQuery()]); ?> | <?php echo $this->Html->link('Open in new tab', $result['url']); ?>
</div>

Coverage-Result of <?php echo h($result['file']); ?>

<h2>Details</h2>

<?php if ($result['testFileExists']) { ?>
<iframe src="<?php echo $result['url']; ?>" style="width: 98%; height: 800px;"></iframe>
<?php } else { ?>
<i>Coverage file could not be created, coverage driver issues?</i>
<?php } ?>
