<?php

namespace TestHelper\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use TestHelper\Controller\Component\TestGeneratorComponent;

class TestGeneratorComponentTest extends TestCase {

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->path = TMP . 'test_generator' . DS;
		mkdir($this->path . 'Admin', 0770, true);
		mkdir($this->path . '.hidden', 0770, true);
		touch($this->path . 'PostsController.php');
		touch($this->path . 'ArticlesController.php');
		touch($this->path . '.gitkeep');
		touch($this->path . 'Admin' . DS . 'UsersController.php');
		touch($this->path . '.hidden' . DS . 'SecretController.php');
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		unlink($this->path . 'PostsController.php');
		unlink($this->path . 'ArticlesController.php');
		unlink($this->path . '.gitkeep');
		unlink($this->path . 'Admin' . DS . 'UsersController.php');
		unlink($this->path . '.hidden' . DS . 'SecretController.php');
		rmdir($this->path . 'Admin');
		rmdir($this->path . '.hidden');
		rmdir($this->path);
	}

	/**
	 * @return void
	 */
	public function testGetFiles() {
		$component = new TestGeneratorComponent(new ComponentRegistry(new Controller(new ServerRequest())));

		$result = $component->getFiles([$this->path]);

		$expected = [
			'ArticlesController',
			'PostsController',
			'Admin/UsersController',
		];
		$this->assertSame($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testGetFilesMissingFolder() {
		$component = new TestGeneratorComponent(new ComponentRegistry(new Controller(new ServerRequest())));

		$result = $component->getFiles([$this->path . 'Missing' . DS]);

		$this->assertSame([], $result);
	}

}
