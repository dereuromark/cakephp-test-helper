<?php

namespace TestApp\Controller;

use Cake\Controller\Controller;

class AppController extends Controller {

	/**
	 * @return void
	 */
	public function initialize(): void {
		$this->loadComponent('Flash');
	}

}
