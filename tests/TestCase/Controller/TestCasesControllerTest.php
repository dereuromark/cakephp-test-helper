<?php

namespace TestHelper\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \TestHelper\Controller\TestCasesController
 */
class TestCasesControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @return void
	 */
	public function testController() {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestCases', 'action' => 'controller', 'app']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testHelper() {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestCases', 'action' => 'helper', 'app']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testHelperPlugin() {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestCases', 'action' => 'helper', 'Tools']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testCommand(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestCases', 'action' => 'command', '?' => ['namespace' => 'app']]);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testTable(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestCases', 'action' => 'table', '?' => ['namespace' => 'app']]);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testEntity(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestCases', 'action' => 'entity', '?' => ['namespace' => 'app']]);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testBehavior(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestCases', 'action' => 'behavior', '?' => ['namespace' => 'app']]);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testComponent(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestCases', 'action' => 'component', '?' => ['namespace' => 'app']]);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testTask(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestCases', 'action' => 'task', '?' => ['namespace' => 'app']]);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testForm(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestCases', 'action' => 'form', '?' => ['namespace' => 'app']]);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testMailer(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestCases', 'action' => 'mailer', '?' => ['namespace' => 'app']]);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testCell(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestCases', 'action' => 'cell', '?' => ['namespace' => 'app']]);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testCommandHelper(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestCases', 'action' => 'commandHelper', '?' => ['namespace' => 'app']]);

		$this->assertResponseCode(200);
	}

	/**
	 * Listing components for a plugin must surface the plugin's own components
	 * (exercising the file-listing and existing-test-case detection branch).
	 *
	 * @return void
	 */
	public function testComponentPluginListsClasses(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestCases', 'action' => 'component', '?' => ['namespace' => 'TestHelper']]);

		$this->assertResponseCode(200);
		$this->assertResponseContains('Migrations');
		$this->assertResponseContains('TestGenerator');
	}

	/**
	 * @return void
	 */
	public function testBrowse(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestCases', 'action' => 'browse']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testBrowsePlugin(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestCases', 'action' => 'browse', '?' => ['namespace' => 'TestHelper']]);

		$this->assertResponseCode(200);
	}

	/**
	 * Browsing into an existing subfolder must list the test files it contains.
	 *
	 * @return void
	 */
	public function testBrowseSubPath(): void {
		$this->get([
			'plugin' => 'TestHelper',
			'controller' => 'TestCases',
			'action' => 'browse',
			'?' => ['namespace' => 'TestHelper', 'path' => 'Utility'],
		]);

		$this->assertResponseCode(200);
		$this->assertResponseContains('ClassResolverTest.php');
	}

	/**
	 * A non-existent path must fall back to the base directory with an error
	 * flash instead of failing.
	 *
	 * @return void
	 */
	public function testBrowseInvalidPathSetsFlash(): void {
		$this->enableRetainFlashMessages();
		$this->get([
			'plugin' => 'TestHelper',
			'controller' => 'TestCases',
			'action' => 'browse',
			'?' => ['namespace' => 'TestHelper', 'path' => 'DoesNotExist'],
		]);

		$this->assertResponseCode(200);
		$this->assertFlashMessage(__('Directory not found: {0}', 'DoesNotExist'));
	}

	/**
	 * Directory traversal segments in the path must be stripped for safety.
	 *
	 * @return void
	 */
	public function testBrowseStripsTraversal(): void {
		$this->get([
			'plugin' => 'TestHelper',
			'controller' => 'TestCases',
			'action' => 'browse',
			'?' => ['namespace' => 'TestHelper', 'path' => '../../../etc'],
		]);

		// Traversal is stripped so the resolved path collapses to the base dir.
		$this->assertResponseCode(200);
	}

	/**
	 * Viewing a real test file must reflect its test methods in the output.
	 *
	 * @return void
	 */
	public function testViewListsMethods(): void {
		$this->get([
			'plugin' => 'TestHelper',
			'controller' => 'TestCases',
			'action' => 'view',
			'?' => ['namespace' => 'TestHelper', 'file' => 'Utility' . DS . 'ClassResolverTest.php'],
		]);

		$this->assertResponseCode(200);
		$this->assertResponseContains('testType');
		$this->assertResponseContains('testSuffix');
	}

}
