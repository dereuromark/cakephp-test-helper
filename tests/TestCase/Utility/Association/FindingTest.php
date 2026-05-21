<?php

namespace TestHelper\Test\TestCase\Utility\Association;

use Cake\TestSuite\TestCase;
use TestHelper\Utility\Association\Finding;

class FindingTest extends TestCase {

	/**
	 * The topic of a normal finding is its layer (constraint / column / type).
	 *
	 * @return void
	 */
	public function testTopicFromLayer() {
		$constraint = new Finding('Posts', Finding::DIRECTION_DB_MISSING, 'belongsTo', Finding::SEVERITY_WARNING, 'msg', layer: Finding::LAYER_CONSTRAINT);
		$this->assertSame(Finding::LAYER_CONSTRAINT, $constraint->topic());

		$column = new Finding('Posts', Finding::DIRECTION_COLUMN_MISSING, 'belongsTo', Finding::SEVERITY_ERROR, 'msg', layer: Finding::LAYER_COLUMN);
		$this->assertSame(Finding::LAYER_COLUMN, $column->topic());

		$type = new Finding('Posts', Finding::DIRECTION_TYPE, 'belongsTo', Finding::SEVERITY_INFO, 'msg', layer: Finding::LAYER_TYPE);
		$this->assertSame(Finding::LAYER_TYPE, $type->topic());
	}

	/**
	 * Unsupported findings get their own topic, regardless of the (default) layer.
	 *
	 * @return void
	 */
	public function testTopicUnsupported() {
		$finding = new Finding('Posts', Finding::DIRECTION_UNSUPPORTED, 'belongsToMany', Finding::SEVERITY_INFO, 'msg');

		$this->assertSame(Finding::DIRECTION_UNSUPPORTED, $finding->topic());
	}

}
