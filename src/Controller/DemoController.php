<?php

namespace TestHelper\Controller;

use Cake\Event\EventInterface;

class DemoController extends TestHelperAppController {

	/**
     * @var string
     */
	protected const LAYOUT_SESSION_KEY = 'TestHelper.Demo.layout';

	/**
     * @var string
     */
	protected const LAYOUT_APP = 'app';

	/**
     * @var string
     */
	protected const LAYOUT_PLUGIN = 'plugin';

	/**
	 * Before filter callback.
	 *
	 * Handle layout switching via query string and persist in session.
	 *
	 * @param \Cake\Event\EventInterface $event The event instance.
	 * @return void
	 */
	public function beforeFilter(EventInterface $event): void {
		parent::beforeFilter($event);

		$this->handleLayoutSwitching();
	}

	/**
	 * Handle layout switching logic.
	 *
	 * @return void
	 */
	protected function handleLayoutSwitching(): void {
		$session = $this->request->getSession();
		$layoutParam = $this->request->getQuery('layout');

		// If layout param is provided, store in session
		if ($layoutParam !== null) {
			if ($layoutParam === static::LAYOUT_APP) {
				$session->write(static::LAYOUT_SESSION_KEY, static::LAYOUT_APP);
			} else {
				$session->write(static::LAYOUT_SESSION_KEY, static::LAYOUT_PLUGIN);
			}
		}

		// Get current layout choice from session (defaults to plugin layout)
		$currentLayout = $session->read(static::LAYOUT_SESSION_KEY) ?? static::LAYOUT_PLUGIN;

		// Set the layout (plugin layout 'TestHelper.test_helper' is already set by TestHelperAppController)
		if ($currentLayout === static::LAYOUT_APP) {
			// Use app's default layout (won't conflict since plugin layout is named 'test_helper')
			$this->viewBuilder()->setLayout('default');
		}

		// Pass layout info to view
		$this->set('currentDemoLayout', $currentLayout);
		$this->set('layoutApp', static::LAYOUT_APP);
		$this->set('layoutPlugin', static::LAYOUT_PLUGIN);
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function index() {
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function formElements() {
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function html5Elements() {
	}

	/**
	 * Demo page for all flash message types.
	 *
	 * @return \Cake\Http\Response|null|void
	 */
	public function flashMessages() {
		if ($this->request->is('post')) {
			$type = $this->request->getData('type');
			$message = $this->request->getData('message') ?: 'This is a sample ' . $type . ' message.';

			switch ($type) {
				case 'success':
					$this->Flash->success($message);

					break;
				case 'error':
					$this->Flash->error($message);

					break;
				case 'warning':
					$this->Flash->warning($message);

					break;
				case 'info':
					$this->Flash->info($message);

					break;
				default:
					$this->Flash->set($message);
			}

			return $this->redirect(['action' => 'flashMessages']);
		}
	}

	/**
	 * Demo page for table styles.
	 *
	 * @return \Cake\Http\Response|null|void
	 */
	public function tables() {
	}

	/**
	 * Demo page for pagination styles.
	 *
	 * @return \Cake\Http\Response|null|void
	 */
	public function pagination() {
	}

	/**
	 * Demo page for buttons and links.
	 *
	 * @return \Cake\Http\Response|null|void
	 */
	public function buttons() {
	}

	/**
	 * Demo page for typography.
	 *
	 * @return \Cake\Http\Response|null|void
	 */
	public function typography() {
	}

	/**
	 * Demo page for navigation elements.
	 *
	 * @return \Cake\Http\Response|null|void
	 */
	public function navigation() {
	}

}
