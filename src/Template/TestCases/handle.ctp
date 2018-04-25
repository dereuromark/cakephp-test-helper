<?php
/**
 * @var \App\View\AppView $this
 */
?>

<h1><?php echo h($this->request->getQuery('namespace')); ?> tests</h1>

<?php echo $this->element('TestHelper.test_cases'); ?>
