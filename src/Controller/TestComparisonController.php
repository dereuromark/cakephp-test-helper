<?php

namespace TestHelper\Controller;

use Cake\Core\Configure;
use Cake\Core\Plugin;

/**
 * @property \TestHelper\Controller\Component\CollectorComponent $Collector
 */
class TestComparisonController extends TestHelperAppController {

	protected ?string $defaultTable = '';

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->loadComponent('TestHelper.Collector', [
			'connection' => $this->request->getQuery('connection', 'default'),
		] + (array)Configure::read('TestHelper.Collector'));
	}

	/**
	 * @return void
	 */
	public function index() {
		if ($this->request->getQuery('plugin')) {
			/** @var array<string> $plugins */
			$plugins = [$this->request->getQuery('plugin')];
		} else {
			$plugins = Plugin::loaded();
		}

		$result = $this->Collector->modelComparison($plugins);

		$this->set(compact('result'));
	}

}
