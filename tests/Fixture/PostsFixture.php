<?php

namespace TestHelper\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * PostFixture
 */
class PostsFixture extends TestFixture {

	/**
	 * fields property
	 *
	 * @var array
	 */
	public array $fields = [
		'id' => ['type' => 'integer'],
		'author_id' => ['type' => 'integer', 'null' => false],
		'title' => ['type' => 'string', 'null' => false],
		'body' => 'text',
		'published' => ['type' => 'string', 'length' => 1, 'default' => 'N'],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
	];

	/**
	 * records property
	 *
	 * @var array
	 */
	public array $records = [
		['author_id' => 1, 'title' => 'First Post', 'body' => 'First Post Body', 'published' => 'Y'],
		['author_id' => 3, 'title' => 'Second Post', 'body' => 'Second Post Body', 'published' => 'Y'],
		['author_id' => 1, 'title' => 'Third Post', 'body' => 'Third Post Body', 'published' => 'Y'],
	];

}
