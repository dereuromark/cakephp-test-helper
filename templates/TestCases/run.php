<?php
/**
 * @var \App\View\AppView $this
 */
?>

<h1><?php echo h($this->request->getQuery('test')); ?></h1>

<code><?php echo h($result['command']); ?></code>

<h2><?php echo ($result['code'] === 0 ? 'OK' : 'ERROR (code ' . $result['code'] . ')'); ?></h2>
<pre><?php echo h(implode("\n", $result['content'])); ?></pre>
