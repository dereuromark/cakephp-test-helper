<?php

namespace TestApp\View;

use Cake\View\View;
use Templating\View\Helper\IconHelper;

/**
 * @property \TestHelper\View\Helper\TestHelperHelper $TestHelper
 */
class AppView extends View {

	/**
	 * @return void
	 */
	public function initialize(): void {
		$this->addHelper('TestHelper.TestHelper');
		if (class_exists(IconHelper::class)) {
			$this->addHelper('Templating.Icon');
		} else {
			$this->addHelper('TestHelper.Icon');
		}
	}

}
