<?php
namespace TestHelper\Controller;

use App\Controller\AppController;
use Cake\Core\Plugin;

class TestHelperController extends AppController {

	/**
	 * @return \Cake\Http\Response|null
	 */
	public function index() {
		$plugins = Plugin::loaded();

		$this->set(compact('plugins'));
	}

}
