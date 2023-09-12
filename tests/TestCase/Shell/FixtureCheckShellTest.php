<?php

namespace TestHelper\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use Shim\TestSuite\ConsoleOutput;
use TestHelper\Shell\FixtureCheckShell;

class FixtureCheckShellTest extends TestCase {

	/**
	 * @var array<string>
	 */
	protected array $fixtures = [
		'plugin.TestHelper.Posts',
	];

	protected ConsoleOutput $out;

	protected ConsoleOutput $err;

	protected FixtureCheckShell $FixtureCheckShell;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
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
	public function tearDown(): void {
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
