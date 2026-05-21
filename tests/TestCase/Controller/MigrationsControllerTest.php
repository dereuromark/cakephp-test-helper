<?php

namespace TestHelper\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \TestHelper\Controller\MigrationsController
 */
class MigrationsControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * Whether the Migrations plugin is installed in this environment. When it is
	 * missing, every action redirects out via the controller's beforeFilter.
	 *
	 * @return bool
	 */
	protected function migrationsPluginInstalled(): bool {
		return file_exists(ROOT . DS . 'vendor/cakephp/migrations/composer.json');
	}

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

	/**
	 * @return void
	 */
	public function testDriftCheckWithConfiguredMigrations(): void {
		Configure::write('TestHelper.migrations', [
			['plugin' => 'Queue'],
			['plugin' => 'Users'],
			[], // App migrations
			['plugin' => 'Blog'],
		]);

		$this->get(['plugin' => 'TestHelper', 'controller' => 'Migrations', 'action' => 'driftCheck']);

		// Will redirect if Migrations plugin is not installed
		if ($this->_response->getStatusCode() === 302) {
			$this->assertRedirect(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index']);
		} else {
			$this->assertResponseCode(200);
			$this->assertResponseContains('Order configured via');
			$this->assertResponseContains('TestHelper.migrations');
			// Check that plugins are shown in configured order
			$this->assertResponseContains('Queue');
			$this->assertResponseContains('Users');
			$this->assertResponseContains('Blog');
		}

		Configure::delete('TestHelper.migrations');
	}

	/**
	 * @return void
	 */
	public function testDriftCheckWithoutConfiguredMigrations(): void {
		Configure::delete('TestHelper.migrations');

		$this->get(['plugin' => 'TestHelper', 'controller' => 'Migrations', 'action' => 'driftCheck']);

		// Will redirect if Migrations plugin is not installed
		if ($this->_response->getStatusCode() === 302) {
			$this->assertRedirect(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index']);
		} else {
			$this->assertResponseCode(200);
			$this->assertResponseContains('Order auto-detected');
			$this->assertResponseContains('To customize the order');
		}
	}

	/**
	 * When the Migrations plugin is missing the beforeFilter must redirect to the
	 * TestHelper landing page and set an error flash message.
	 *
	 * @return void
	 */
	public function testIndexRedirectsWithFlashWhenPluginMissing(): void {
		if ($this->migrationsPluginInstalled()) {
			$this->markTestSkipped('Migrations plugin is installed; redirect path is not exercised.');
		}

		$this->enableRetainFlashMessages();
		$this->get(['plugin' => 'TestHelper', 'controller' => 'Migrations', 'action' => 'index']);

		$this->assertRedirect(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index']);
		$this->assertFlashMessage(__('It seems the Migrations plugin is missing.'));
		$this->assertFlashElement('flash/error');
	}

	/**
	 * A POST to a write action must also be blocked by the beforeFilter redirect
	 * when the plugin is missing, so no schema changes can be triggered.
	 *
	 * @return void
	 */
	public function testTmpDbPostRedirectsWhenPluginMissing(): void {
		if ($this->migrationsPluginInstalled()) {
			$this->markTestSkipped('Migrations plugin is installed; redirect path is not exercised.');
		}

		$this->post(['plugin' => 'TestHelper', 'controller' => 'Migrations', 'action' => 'tmpDb'], []);

		$this->assertRedirect(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index']);
	}

	/**
	 * @return void
	 */
	public function testSnapshotPostRedirectsWhenPluginMissing(): void {
		if ($this->migrationsPluginInstalled()) {
			$this->markTestSkipped('Migrations plugin is installed; redirect path is not exercised.');
		}

		$this->post(
			['plugin' => 'TestHelper', 'controller' => 'Migrations', 'action' => 'snapshot'],
			['generate' => 1],
		);

		$this->assertRedirect(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index']);
	}

	/**
	 * @return void
	 */
	public function testDriftCheckPostRedirectsWhenPluginMissing(): void {
		if ($this->migrationsPluginInstalled()) {
			$this->markTestSkipped('Migrations plugin is installed; redirect path is not exercised.');
		}

		$this->post(
			['plugin' => 'TestHelper', 'controller' => 'Migrations', 'action' => 'driftCheck'],
			['action' => 'run_migrations'],
		);

		$this->assertRedirect(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index']);
	}

	/**
	 * The connection query parameter must be accepted on driftCheck. With the
	 * plugin missing it still redirects; with the plugin present it renders.
	 *
	 * @return void
	 */
	public function testDriftCheckWithConnectionQuery(): void {
		$this->get([
			'plugin' => 'TestHelper',
			'controller' => 'Migrations',
			'action' => 'driftCheck',
			'?' => ['connection' => 'default'],
		]);

		if ($this->_response->getStatusCode() === 302) {
			$this->assertRedirect(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index']);
		} else {
			$this->assertResponseCode(200);
			$this->assertResponseContains('Schema Drift Detection');
		}
	}

	/**
	 * An unknown export format must fall back to JSON output.
	 *
	 * @return void
	 */
	public function testDriftCheckExportUnknownFormatFallsBackToJson(): void {
		$this->get([
			'plugin' => 'TestHelper',
			'controller' => 'Migrations',
			'action' => 'driftCheck',
			'?' => ['compare' => '1', 'format' => 'xml'],
		]);

		if ($this->_response->getStatusCode() === 302) {
			$this->assertRedirect(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index']);
		} else {
			$this->assertResponseCode(200);
			$this->assertContentType('application/json');
		}
	}

}
