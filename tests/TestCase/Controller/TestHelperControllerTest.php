<?php

namespace TestHelper\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \TestHelper\Controller\TestHelperController
 */
class TestHelperControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @return void
	 */
	public function testIndex() {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index']);

		$this->assertResponseCode(200);
	}

	/**
	 * Without the config, no admin back link is rendered in the layout.
	 *
	 * @return void
	 */
	public function testAdminBackLinkHidden() {
		Configure::delete('TestHelper.adminBackUrl');

		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index']);

		$this->assertResponseCode(200);
		$this->assertResponseNotContains('fa-arrow-left');
	}

	/**
	 * With adminBackUrl set, the layout renders a back link with the configured label.
	 *
	 * @return void
	 */
	public function testAdminBackLinkShown() {
		Configure::write('TestHelper.adminBackUrl', '/admin');
		Configure::write('TestHelper.adminBackLabel', 'Back to dashboard');

		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index']);

		Configure::delete('TestHelper.adminBackUrl');
		Configure::delete('TestHelper.adminBackLabel');

		$this->assertResponseCode(200);
		$this->assertResponseContains('Back to dashboard');
		$this->assertResponseContains('fa-arrow-left');
	}

	/**
	 * @return void
	 */
	public function testIndexPost() {
		$this->disableErrorHandlerMiddleware();

		$data = [
			'url' => '/foo',
			'verbose' => true,
		];

		$this->post(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index'], $data);

		$this->assertResponseCode(200);

		$content = (string)$this->_response->getBody();
		$expected = <<<TXT
    'prefix' => null,
    'plugin' => null,
    'controller' => 'Foo',
    'action' => 'index'
TXT;

		$this->assertTextContains($expected, $content);
	}

	/**
	 * @return void
	 */
	public function testIndexPostNonVerbose() {
		$this->disableErrorHandlerMiddleware();

		$data = [
			'url' => '/foo',
		];

		$this->post(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index'], $data);

		$this->assertResponseCode(200);

		$content = (string)$this->_response->getBody();
		$expected = <<<TXT
    'prefix' => null,
    'plugin' => null,
    'controller' => 'Foo',
    'action' => 'index'
TXT;

		$this->assertTextNotContains($expected, $content);
	}

}
