<?php

namespace TestHelper\Test\TestCase\Controller;

use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use TestHelper\Controller\AssociationsController;
use TestHelper\Utility\Association\Finding;

/**
 * @uses \TestHelper\Controller\AssociationsController
 */
class AssociationsControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * The matrix exposes the association-type columns, a per-table grid and severity totals.
	 *
	 * @return void
	 */
	public function testIndex() {
		$this->disableErrorHandlerMiddleware();

		$this->get(['plugin' => 'TestHelper', 'controller' => 'Associations', 'action' => 'index']);

		$this->assertResponseCode(200);
		$this->assertResponseContains('Association');
		$this->assertSame(['belongsTo', 'hasMany', 'hasOne', 'belongsToMany', 'looseColumn', 'keyType', 'cascadeRule'], $this->viewVariable('columns'));
		$this->assertIsArray($this->viewVariable('matrix'));
		$this->assertIsArray($this->viewVariable('tables'));
		$this->assertSame(['error', 'warning', 'info'], array_keys($this->viewVariable('totals')));
		$this->assertFalse($this->viewVariable('includeVendor'));
	}

	/**
	 * Cross-cutting layers route to their own matrix columns; everything else stays under
	 * its association type.
	 *
	 * @return void
	 */
	public function testColumnForRoutesLayersToOwnColumns() {
		$controller = new class (new ServerRequest()) extends AssociationsController {

			public function columnForPublic(Finding $finding): string {
				return $this->columnFor($finding);
			}

		};

		$type = new Finding('Posts', Finding::DIRECTION_TYPE, 'belongsTo', Finding::SEVERITY_ERROR, 'm', layer: Finding::LAYER_TYPE);
		$rule = new Finding('Posts', Finding::DIRECTION_RULE, 'hasMany', Finding::SEVERITY_INFO, 'm', layer: Finding::LAYER_RULE);
		$constraint = new Finding('Posts', Finding::DIRECTION_DB_MISSING, 'belongsTo', Finding::SEVERITY_WARNING, 'm', layer: Finding::LAYER_CONSTRAINT);
		$loose = new Finding('Posts', Finding::DIRECTION_CODE_MISSING, 'looseColumn', Finding::SEVERITY_INFO, 'm', layer: Finding::LAYER_COLUMN);

		$this->assertSame('keyType', $controller->columnForPublic($type));
		$this->assertSame('cascadeRule', $controller->columnForPublic($rule));
		$this->assertSame('belongsTo', $controller->columnForPublic($constraint));
		$this->assertSame('looseColumn', $controller->columnForPublic($loose));
	}

	/**
	 * The vendor toggle flows through to the view.
	 *
	 * @return void
	 */
	public function testIndexIncludeVendor() {
		$this->disableErrorHandlerMiddleware();

		$this->get(['plugin' => 'TestHelper', 'controller' => 'Associations', 'action' => 'index', '?' => ['vendor' => 1]]);

		$this->assertResponseCode(200);
		$this->assertTrue($this->viewVariable('includeVendor'));
	}

	/**
	 * The flat scan exposes a findings list and severity totals.
	 *
	 * @return void
	 */
	public function testScan() {
		$this->disableErrorHandlerMiddleware();

		$this->get(['plugin' => 'TestHelper', 'controller' => 'Associations', 'action' => 'scan']);

		$this->assertResponseCode(200);
		$this->assertIsArray($this->viewVariable('findings'));
		$this->assertSame(['error', 'warning', 'info'], array_keys($this->viewVariable('totals')));
	}

	/**
	 * Without a model the detail page renders empty (no findings, all direction groups empty).
	 *
	 * @return void
	 */
	public function testViewWithoutModel() {
		$this->disableErrorHandlerMiddleware();

		$this->get(['plugin' => 'TestHelper', 'controller' => 'Associations', 'action' => 'view']);

		$this->assertResponseCode(200);
		$this->assertSame([], $this->viewVariable('findings'));
		$this->assertArrayHasKey(Finding::DIRECTION_MISMATCH, $this->viewVariable('grouped'));
	}

	/**
	 * With a model the detail page audits it and groups findings by direction.
	 *
	 * @return void
	 */
	public function testViewWithModel() {
		$this->disableErrorHandlerMiddleware();

		$this->get(['plugin' => 'TestHelper', 'controller' => 'Associations', 'action' => 'view', 'Posts']);

		$this->assertResponseCode(200);
		$this->assertSame('Posts', $this->viewVariable('model'));
		$grouped = $this->viewVariable('grouped');
		$this->assertArrayHasKey(Finding::DIRECTION_MISMATCH, $grouped);
		$this->assertArrayHasKey(Finding::DIRECTION_TYPE, $grouped);
		$this->assertArrayHasKey(Finding::DIRECTION_DB_MISSING, $grouped);
	}

}
