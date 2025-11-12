<?php

namespace TestHelper\Controller;

use Authorization\AuthorizationService;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Log\Log;
use Templating\View\Helper\IconHelper;

/**
 * TestHelper Plugin Base Controller
 *
 * This controller provides common functionality for all TestHelper plugin controllers.
 * It extends Cake\Controller\Controller directly to be self-contained and not depend
 * on the host application's AppController.
 */
class TestHelperAppController extends Controller {

	/**
	 * Initialization hook method.
	 *
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		// Load Flash component for all plugin controllers
		$this->loadComponent('Flash');

		// Set default layout for the plugin
		$this->viewBuilder()->setLayout('TestHelper.default');

		$helpers = [
			'TestHelper.TestHelper',
		];
		if (class_exists(IconHelper::class)) {
			$helpers[] = 'Templating.Icon';
		}
		$this->viewBuilder()->setHelpers($helpers);
	}

	/**
	 * Before filter callback.
	 *
	 * @param \Cake\Event\EventInterface $event The event instance.
	 * @return void
	 */
	public function beforeFilter(EventInterface $event): void {
		parent::beforeFilter($event);

		if (!class_exists(AuthorizationService::class)) {
			return;
		}

		// Handle Authorization plugin (CakePHP Authorization)
		$authorizationService = $this->getRequest()->getAttribute('authorization');
		if ($authorizationService instanceof AuthorizationService) {
			if (Configure::read('TestHelper.ignoreAuthorization')) {
				$authorizationService->skipAuthorization();
			} else {
				Log::info(
					'TestHelper: Authorization plugin is active but authorization checks are not being skipped. '
					. 'Set `Configure::write(\'TestHelper.ignoreAuthorization\', true);` in your config/bootstrap.php '
					. 'if you want to bypass authorization for TestHelper routes.',
				);
			}
		}
	}

}
