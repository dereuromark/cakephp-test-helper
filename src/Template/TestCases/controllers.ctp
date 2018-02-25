<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\EtchatMessage[]|\Cake\Collection\CollectionInterface $etchatMessages
 */
?>

<h1><?php echo h($this->request->param('pass')[0]); ?> tests</h1>

<?php echo $this->element('TestHelper.test_cases'); ?>
