<?php
namespace TestHelper\Controller;

use App\Controller\AppController;
use Cake\Core\Plugin;
use Cake\Event\Event;

class TestHelperController extends AppController {

	/**
	 * @param \Cake\Event\Event $event
	 * @return \Cake\Http\Response|null
	 */
	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);

		if (isset($this->Auth)) {
			$this->Auth->allow();
		}
	}

	/**
	 * @return \Cake\Http\Response|null
	 */
	public function index() {
		$plugins = Plugin::loaded();

		$this->set(compact('plugins'));
	}

}
