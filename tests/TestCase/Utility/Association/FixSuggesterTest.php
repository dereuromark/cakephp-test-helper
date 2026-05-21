<?php

namespace TestHelper\Test\TestCase\Utility\Association;

use Cake\TestSuite\TestCase;
use TestHelper\Utility\Association\FixSuggester;
use TestHelper\Utility\Association\ForeignKey;

class FixSuggesterTest extends TestCase {

	protected FixSuggester $suggester;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->suggester = new FixSuggester();
	}

	/**
	 * @return void
	 */
	public function testAssociationCall() {
		$fk = new ForeignKey('default', 'posts', 'author_id', 'authors', 'id', ForeignKey::SOURCE_DB);

		$result = $this->suggester->associationCall($fk);

		$this->assertStringContainsString("\$this->belongsTo('Authors', ['foreignKey' => 'author_id']);", $result);
		$this->assertStringContainsString("\$this->hasMany('Posts', ['foreignKey' => 'author_id']);", $result);
	}

	/**
	 * @return void
	 */
	public function testMigrationLine() {
		$fk = new ForeignKey('default', 'posts', 'author_id', 'authors', 'id', ForeignKey::SOURCE_CODE, 'belongsTo', 'Posts', 'Authors', true);

		$result = $this->suggester->migrationLine($fk);

		$this->assertStringContainsString("\$table->addForeignKey('author_id', 'authors', 'id'", $result);
	}

}
