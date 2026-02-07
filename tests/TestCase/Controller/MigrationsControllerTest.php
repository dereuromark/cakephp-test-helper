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

	/**
	 * @return void
	 */
	public function testDriftCheck(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'Migrations', 'action' => 'driftCheck']);

		// Will redirect if Migrations plugin is not installed
		if ($this->_response->getStatusCode() === 302) {
			$this->assertRedirect(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index']);
		} else {
			$this->assertResponseCode(200);
			$this->assertResponseContains('Schema Drift Detection');
		}
	}

	/**
	 * @return void
	 */
	public function testDriftCheckExportJson(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'Migrations', 'action' => 'driftCheck', '?' => ['compare' => '1', 'format' => 'json']]);

		// Will redirect if Migrations plugin is not installed
		if ($this->_response->getStatusCode() === 302) {
			$this->assertRedirect(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index']);
		} else {
			$this->assertResponseCode(200);
			$this->assertContentType('application/json');

			$body = (string)$this->_response->getBody();
			$data = json_decode($body, true);

			$this->assertIsArray($data);
			$this->assertArrayHasKey('connection', $data);
			$this->assertArrayHasKey('database', $data);
			$this->assertArrayHasKey('hasDrift', $data);
			$this->assertArrayHasKey('drift', $data);
			$this->assertArrayHasKey('generatedAt', $data);
		}
	}

	/**
	 * @return void
	 */
	public function testDriftCheckExportMarkdown(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'Migrations', 'action' => 'driftCheck', '?' => ['compare' => '1', 'format' => 'markdown']]);

		// Will redirect if Migrations plugin is not installed
		if ($this->_response->getStatusCode() === 302) {
			$this->assertRedirect(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index']);
		} else {
			$this->assertResponseCode(200);
			$this->assertContentType('text/markdown');

			$body = (string)$this->_response->getBody();
			$this->assertStringContainsString('# Schema Drift Report', $body);
			$this->assertStringContainsString('**Database:**', $body);
			$this->assertStringContainsString('**Shadow Database:**', $body);
		}
	}

	/**
	 * @return void
	 */
	public function testDriftCheckExportText(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'Migrations', 'action' => 'driftCheck', '?' => ['compare' => '1', 'format' => 'text']]);

		// Will redirect if Migrations plugin is not installed
		if ($this->_response->getStatusCode() === 302) {
			$this->assertRedirect(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index']);
		} else {
			$this->assertResponseCode(200);
			$this->assertContentType('text/plain');

			$body = (string)$this->_response->getBody();
			$this->assertStringContainsString('SCHEMA DRIFT REPORT', $body);
			$this->assertStringContainsString('Database:', $body);
			$this->assertStringContainsString('Status:', $body);
		}
	}

	/**
	 * @return void
	 */
	public function testDriftCheckExportMdAlias(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'Migrations', 'action' => 'driftCheck', '?' => ['compare' => '1', 'format' => 'md']]);

		// Will redirect if Migrations plugin is not installed
		if ($this->_response->getStatusCode() === 302) {
			$this->assertRedirect(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index']);
		} else {
			$this->assertResponseCode(200);
			$this->assertContentType('text/markdown');
		}
	}

	/**
	 * @return void
	 */
	public function testDriftCheckExportTxtAlias(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'Migrations', 'action' => 'driftCheck', '?' => ['compare' => '1', 'format' => 'txt']]);

		// Will redirect if Migrations plugin is not installed
		if ($this->_response->getStatusCode() === 302) {
			$this->assertRedirect(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index']);
		} else {
			$this->assertResponseCode(200);
			$this->assertContentType('text/plain');
		}
	}

}
