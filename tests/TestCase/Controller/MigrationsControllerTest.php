<?php

namespace TestHelper\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \TestHelper\Controller\MigrationsController
 */
class MigrationsControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @return void
	 */
	public function testIndex(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'Migrations', 'action' => 'index']);

		// Will redirect if Migrations plugin is not installed
		if ($this->_response->getStatusCode() === 302) {
			$this->assertRedirect(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index']);
		} else {
			$this->assertResponseCode(200);
		}
	}

	/**
	 * @return void
	 */
	public function testTmpDb(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'Migrations', 'action' => 'tmpDb']);

		// Will redirect if Migrations plugin is not installed
		if ($this->_response->getStatusCode() === 302) {
			$this->assertRedirect(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index']);
		} else {
			$this->assertResponseCode(200);
		}
	}

	/**
	 * @return void
	 */
	public function testSnapshot(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'Migrations', 'action' => 'snapshot']);

		// Will redirect if Migrations plugin is not installed
		if ($this->_response->getStatusCode() === 302) {
			$this->assertRedirect(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index']);
		} else {
			$this->assertResponseCode(200);
		}
	}

	/**
	 * @return void
	 */
	public function testSnapshotTest(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'Migrations', 'action' => 'snapshotTest']);

		// Will redirect if Migrations plugin is not installed
		if ($this->_response->getStatusCode() === 302) {
			$this->assertRedirect(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index']);
		} else {
			$this->assertResponseCode(200);
		}
	}

	/**
	 * @return void
	 */
	public function testSeedTest(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'Migrations', 'action' => 'seedTest']);

		// Will redirect if Migrations plugin is not installed
		if ($this->_response->getStatusCode() === 302) {
			$this->assertRedirect(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index']);
		} else {
			$this->assertResponseCode(200);
		}
	}

	/**
	 * @return void
	 */
	public function testConfirm(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'Migrations', 'action' => 'confirm']);

		// Will redirect if Migrations plugin is not installed
		if ($this->_response->getStatusCode() === 302) {
			$this->assertRedirect(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index']);
		} else {
			$this->assertResponseCode(200);
		}
	}

	/**
	 * @return void
	 */
	public function testCleanup(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'Migrations', 'action' => 'cleanup']);

		// Will redirect if Migrations plugin is not installed
		if ($this->_response->getStatusCode() === 302) {
			$this->assertRedirect(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index']);
		} else {
			$this->assertResponseCode(200);
		}
	}

}
