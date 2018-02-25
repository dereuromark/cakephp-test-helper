<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\EtchatMessage[]|\Cake\Collection\CollectionInterface $etchatMessages
 */

?>
<h1><?php echo h($this->request->getQuery('test')); ?></h1>

<code><?php echo h($result['command']); ?></code>
<br><br>
Coverage-Result: <?php echo h($result['file']); ?>

<h2>Details</h2>

<iframe src="<?php echo $result['url']; ?>" style="width: 98%; height: 800px;"></iframe>
