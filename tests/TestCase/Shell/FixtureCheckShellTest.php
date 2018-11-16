<?php
namespace TestHelper\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use TestHelper\Shell\FixtureCheckShell;
use Tools\TestSuite\ConsoleOutput;

class FixtureCheckShellTest extends TestCase {

	/**
	 * @var array
	 */
	public $fixtures = [
		'core.posts',
	];

	/**
	 * @var \Tools\TestSuite\ConsoleOutput
	 */
	public $out;

	/**
	 * @var \Tools\TestSuite\ConsoleOutput
	 */
	public $err;

	/**
	 * @var \TestHelper\Shell\FixtureCheckShell
	 */
	public $FixtureCheckShell;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$io = new ConsoleIo($this->out, $this->err);

		$this->FixtureCheckShell = new FixtureCheckShell($io);
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		unset($this->FixtureCheckShell);

		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testDiff() {
		$this->FixtureCheckShell->runCommand(['diff', '-p', 'Tools', '-t', 'f,c,i']);

		$output = $this->out->output();
		$this->assertNotEmpty($output, $output);

		$error = $this->err->output();
		$this->assertNotEmpty($error, $error);
	}

}
