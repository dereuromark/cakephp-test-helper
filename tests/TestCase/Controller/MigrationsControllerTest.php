<?php

namespace TestHelper\Test\TestCase\Controller;

use Cake\Database\Driver\Mysql;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \TestHelper\Controller\MigrationsController
 *
 * These tests require the optional `cakephp/migrations` plugin (a require-dev dependency) so
 * the controller's beforeFilter guard passes and the action bodies actually execute. They
 * assert the behavior reachable in this suite (rendering + validation/guard paths). The
 * drift-report and export happy paths need a MySQL/PostgreSQL shadow database plus a real
 * migration run, so they are exercised manually/integration-only, not here.
 */
class MigrationsControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @param string $action
	 * @return array<string, string>
	 */
	protected function url(string $action): array {
		return ['plugin' => 'TestHelper', 'controller' => 'Migrations', 'action' => $action];
	}

	/**
	 * index renders the database overview.
	 *
	 * @return void
	 */
	public function testIndex(): void {
		$this->disableErrorHandlerMiddleware();

		$this->get($this->url('index'));

		$this->assertResponseCode(200);
		$this->assertResponseContains('Default DB');
	}

	/**
	 * The temp-database flow is MySQL-only; on other drivers it degrades to a flash + redirect
	 * rather than erroring on the unsupported SQL.
	 *
	 * @return void
	 */
	public function testTmpDbRequiresMysqlOnOtherDrivers(): void {
		if (ConnectionManager::get('default')->getDriver() instanceof Mysql) {
			$this->markTestSkipped('Driver is MySQL; the non-MySQL guard is not exercised.');
		}

		$this->enableRetainFlashMessages();

		$this->get($this->url('tmpDb'));

		$this->assertRedirect($this->url('index'));
		$this->assertFlashMessage(__('The temporary database feature requires MySQL.'));
	}

	/**
	 * @return void
	 */
	public function testSnapshotRenders(): void {
		$this->disableErrorHandlerMiddleware();

		$this->get($this->url('snapshot'));

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testSnapshotTestRenders(): void {
		$this->disableErrorHandlerMiddleware();

		$this->get($this->url('snapshotTest'));

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testSeedTestRenders(): void {
		$this->disableErrorHandlerMiddleware();

		$this->get($this->url('seedTest'));

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testConfirmRenders(): void {
		$this->disableErrorHandlerMiddleware();

		$this->get($this->url('confirm'));

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testCleanupRenders(): void {
		$this->disableErrorHandlerMiddleware();

		$this->get($this->url('cleanup'));

		$this->assertResponseCode(200);
	}

	/**
	 * driftCheck renders for any driver: it validates the connection/driver and shows the
	 * report page (on an unsupported driver or shared shadow DB it renders the error notice).
	 *
	 * @return void
	 */
	public function testDriftCheckRenders(): void {
		$this->disableErrorHandlerMiddleware();

		$this->get($this->url('driftCheck'));

		$this->assertResponseCode(200);
		$this->assertResponseContains('Schema Drift Detection');
	}

	/**
	 * driftCheck honors the `connection` query parameter.
	 *
	 * @return void
	 */
	public function testDriftCheckWithConnectionParam(): void {
		$this->disableErrorHandlerMiddleware();

		$this->get($this->url('driftCheck') + ['?' => ['connection' => 'default']]);

		$this->assertResponseCode(200);
		$this->assertResponseContains('Schema Drift Detection');
	}

}
