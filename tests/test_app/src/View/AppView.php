<?php

namespace TestApp\View;

use Cake\View\View;

/**
 * @property \TestHelper\View\Helper\TestHelperHelper $TestHelper
 */
class AppView extends View {

	/**
	 * @return void
	 */
	public function initialize(): void {
		$this->addHelper('Tools.Icon');
	}

}
