<?php

namespace TestHelper\Test\TestCase\Utility\Association;

use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use TestHelper\Utility\Association\AssociationReader;
use TestHelper\Utility\Association\ForeignKey;

class AssociationReaderTest extends TestCase {

	use LocatorAwareTrait;

	protected AssociationReader $reader;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->reader = new AssociationReader();
		$this->getTableLocator()->clear();
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		$this->getTableLocator()->clear();

		parent::tearDown();
	}

	/**
	 * Register a table alias with an explicit schema (no DB needed).
	 *
	 * @param string $alias
	 * @param string $table
	 * @param array<string, mixed> $columns
	 * @return \Cake\ORM\Table
	 */
	protected function table(string $alias, string $table, array $columns): Table {
		$columns += ['_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]];
		$instance = $this->getTableLocator()->get($alias, ['table' => $table]);
		$instance->setSchema($columns);

		return $instance;
	}

	/**
	 * belongsTo keeps the FK on the source table.
	 *
	 * @return void
	 */
	public function testBelongsToNormalizesToSourceOwner() {
		$this->table('Authors', 'authors', ['id' => ['type' => 'integer'], 'name' => ['type' => 'string']]);
		$posts = $this->table('Posts', 'posts', ['id' => ['type' => 'integer'], 'author_id' => ['type' => 'integer']]);
		$posts->belongsTo('Authors', ['foreignKey' => 'author_id']);

		[$keys, $unsupported] = $this->reader->read($posts);

		$this->assertSame([], $unsupported);
		$this->assertCount(1, $keys);
		/** @var \TestHelper\Utility\Association\ForeignKey $key */
		$key = $keys[0];
		$this->assertSame('posts', $key->ownerTable);
		$this->assertSame('author_id', $key->column);
		$this->assertSame('authors', $key->referencedTable);
		$this->assertSame('belongsTo', $key->associationType);
		// Column types are captured from each side's schema.
		$this->assertSame('integer', $key->ownerColumnType);
		$this->assertSame('integer', $key->referencedColumnType);
	}

	/**
	 * The reader captures a uuid referenced-key type (for the key-type audit layer).
	 *
	 * @return void
	 */
	public function testBelongsToCapturesUuidReferencedType() {
		$this->table('Authors', 'authors', ['id' => ['type' => 'uuid']]);
		$posts = $this->table('Posts', 'posts', ['id' => ['type' => 'integer'], 'author_id' => ['type' => 'integer']]);
		$posts->belongsTo('Authors', ['foreignKey' => 'author_id']);

		[$keys] = $this->reader->read($posts);

		$this->assertCount(1, $keys);
		$this->assertSame('integer', $keys[0]->ownerColumnType);
		$this->assertSame('uuid', $keys[0]->referencedColumnType);
	}

	/**
	 * With no explicit bindingKey, the reader resolves the target's actual primary key
	 * (not a hard-coded `id`) — verifies the type lookup works for non-`id` PKs.
	 *
	 * @return void
	 */
	public function testBelongsToResolvesNonIdPrimaryKey() {
		$authors = $this->getTableLocator()->get('Authors', ['table' => 'authors']);
		$authors->setSchema([
			'reference' => ['type' => 'uuid'],
			'name' => ['type' => 'string'],
			'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['reference']]],
		]);
		$posts = $this->table('Posts', 'posts', ['id' => ['type' => 'integer'], 'author_id' => ['type' => 'integer']]);
		$posts->belongsTo('Authors', ['foreignKey' => 'author_id']);

		[$keys] = $this->reader->read($posts);

		$this->assertCount(1, $keys);
		// Referenced column is the real PK `reference`, and its type is read from there.
		$this->assertSame('reference', $keys[0]->referencedColumn);
		$this->assertSame('uuid', $keys[0]->referencedColumnType);
	}

	/**
	 * hasMany moves the FK onto the target table.
	 *
	 * @return void
	 */
	public function testHasManyNormalizesToTargetOwner() {
		$authors = $this->table('Authors', 'authors', ['id' => ['type' => 'integer'], 'name' => ['type' => 'string']]);
		$this->table('Posts', 'posts', ['id' => ['type' => 'integer'], 'author_id' => ['type' => 'integer']]);
		$authors->hasMany('Posts', ['foreignKey' => 'author_id']);

		[$keys] = $this->reader->read($authors);

		$this->assertCount(1, $keys);
		/** @var \TestHelper\Utility\Association\ForeignKey $key */
		$key = $keys[0];
		// FK lives on posts (the target), pointing back at authors.
		$this->assertSame('posts', $key->ownerTable);
		$this->assertSame('author_id', $key->column);
		$this->assertSame('authors', $key->referencedTable);
		$this->assertSame('hasMany', $key->associationType);
	}

	/**
	 * belongsToMany expands into two junction-table foreign keys.
	 *
	 * @return void
	 */
	public function testBelongsToManyExpandsToJunctionKeys() {
		$articles = $this->table('Articles', 'articles', ['id' => ['type' => 'integer']]);
		$this->table('Tags', 'tags', ['id' => ['type' => 'integer']]);
		$this->table('ArticlesTags', 'articles_tags', [
			'id' => ['type' => 'integer'],
			'article_id' => ['type' => 'integer'],
			'tag_id' => ['type' => 'integer'],
		]);
		$articles->belongsToMany('Tags');

		[$keys] = $this->reader->read($articles);

		$this->assertCount(2, $keys);
		$owners = array_map(fn (ForeignKey $k): string => $k->ownerTable, $keys);
		$this->assertSame(['articles_tags', 'articles_tags'], $owners);
		$referenced = array_map(fn (ForeignKey $k): string => $k->referencedTable, $keys);
		$this->assertContains('articles', $referenced);
		$this->assertContains('tags', $referenced);
	}

	/**
	 * A missing junction FK column is reported as non-existent (not just a missing constraint).
	 *
	 * @return void
	 */
	public function testBelongsToManyFlagsMissingJunctionColumn() {
		$articles = $this->table('Articles', 'articles', ['id' => ['type' => 'integer']]);
		$this->table('Tags', 'tags', ['id' => ['type' => 'integer']]);
		// Junction is missing `tag_id`.
		$this->table('ArticlesTags', 'articles_tags', [
			'id' => ['type' => 'integer'],
			'article_id' => ['type' => 'integer'],
		]);
		$articles->belongsToMany('Tags');

		[$keys] = $this->reader->read($articles);

		$byColumn = [];
		foreach ($keys as $key) {
			$byColumn[$key->column] = $key;
		}
		$this->assertTrue($byColumn['article_id']->columnExists);
		$this->assertFalse($byColumn['tag_id']->columnExists);
	}

	/**
	 * A composite binding key on belongsToMany is flagged unsupported, not truncated.
	 *
	 * @return void
	 */
	public function testBelongsToManyCompositeBindingKeyIsUnsupported() {
		$articles = $this->table('Articles', 'articles', [
			'id' => ['type' => 'integer'],
			'sub' => ['type' => 'integer'],
			'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id', 'sub']]],
		]);
		$this->table('Tags', 'tags', ['id' => ['type' => 'integer']]);
		$this->table('ArticlesTags', 'articles_tags', [
			'id' => ['type' => 'integer'],
			'article_id' => ['type' => 'integer'],
			'tag_id' => ['type' => 'integer'],
		]);
		$articles->belongsToMany('Tags');

		[$keys, $unsupported] = $this->reader->read($articles);

		$this->assertSame([], $keys);
		$this->assertCount(1, $unsupported);
		$this->assertSame('unsupported', $unsupported[0]->direction);
		$this->assertStringContainsString('composite binding key', $unsupported[0]->message);
	}

	/**
	 * foreignKey => false yields an unsupported info finding, not a key.
	 *
	 * @return void
	 */
	public function testForeignKeyFalseIsUnsupported() {
		$this->table('Authors', 'authors', ['id' => ['type' => 'integer']]);
		$posts = $this->table('Posts', 'posts', ['id' => ['type' => 'integer'], 'author_id' => ['type' => 'integer']]);
		$posts->belongsTo('Authors', ['foreignKey' => false, 'conditions' => ['Authors.id = Posts.author_id']]);

		[$keys, $unsupported] = $this->reader->read($posts);

		$this->assertSame([], $keys);
		$this->assertCount(1, $unsupported);
		$this->assertSame('unsupported', $unsupported[0]->direction);
	}

}
