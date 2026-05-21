<?php

namespace TestHelper\Test\TestCase\Utility\Association;

use Cake\TestSuite\TestCase;
use ReflectionClass;
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

	/**
	 * Every value topic() can return must have a flat-scan filter label (and vice versa).
	 * Reflection over the LAYER_* constants guards against a new audit layer being added
	 * without a matching topic chip - which would make those findings unfilterable.
	 *
	 * @return void
	 */
	public function testEveryTopicHasAFilterLabel() {
		$layers = [];
		foreach ((new ReflectionClass(Finding::class))->getConstants() as $name => $value) {
			if (str_starts_with($name, 'LAYER_')) {
				$layers[] = $value;
			}
		}
		// topic() returns a layer for normal findings, or the unsupported direction otherwise.
		$expectedTopics = array_merge($layers, [Finding::DIRECTION_UNSUPPORTED]);
		sort($expectedTopics);

		$labelled = array_keys(Finding::topicLabels());
		sort($labelled);

		$this->assertSame($expectedTopics, $labelled, 'Every finding topic must have exactly one flat-scan filter label (and vice versa).');
	}

	/**
	 * The per-topic chip counts must account for every finding: with one finding of each
	 * topic (including a mismatch, which is a constraint-layer finding), the totals across
	 * the labelled chips must equal the finding count. A finding whose topic has no chip
	 * (the regression we are guarding against) would drop out of this sum.
	 *
	 * @return void
	 */
	public function testTopicCountsCoverEveryFinding() {
		$findings = [
			new Finding('T', Finding::DIRECTION_MISMATCH, 'belongsTo', Finding::SEVERITY_ERROR, 'm', layer: Finding::LAYER_CONSTRAINT),
			new Finding('T', Finding::DIRECTION_COLUMN_MISSING, 'belongsTo', Finding::SEVERITY_ERROR, 'm', layer: Finding::LAYER_COLUMN),
			new Finding('T', Finding::DIRECTION_TYPE, 'belongsTo', Finding::SEVERITY_INFO, 'm', layer: Finding::LAYER_TYPE),
			new Finding('T', Finding::DIRECTION_RULE, 'hasMany', Finding::SEVERITY_INFO, 'm', layer: Finding::LAYER_RULE),
			new Finding('T', Finding::DIRECTION_INDEX, 'belongsTo', Finding::SEVERITY_INFO, 'm', layer: Finding::LAYER_INDEX),
			new Finding('T', Finding::DIRECTION_UNSUPPORTED, 'belongsToMany', Finding::SEVERITY_INFO, 'm'),
		];

		$counts = array_count_values(array_map(fn (Finding $finding): string => $finding->topic(), $findings));

		$chipTotal = 0;
		foreach (array_keys(Finding::topicLabels()) as $topic) {
			$chipTotal += $counts[$topic] ?? 0;
		}

		$this->assertSame(count($findings), $chipTotal, 'Every finding must be counted under a labelled topic chip.');
		// A mismatch finding is a constraint-layer topic.
		$this->assertSame(1, $counts[Finding::LAYER_CONSTRAINT] ?? 0);
	}

}
