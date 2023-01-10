<?php

namespace TestHelper\Test\TestCase\Controller;

/**
 * @uses \TestHelper\Controller\TestHelperController
 */
class TestHelperControllerTest extends TestCase {

	/**
	 * @return void
	 */
	public function testIndex() {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index']);

		$this->assertResponseCode(200);
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
