<?php

namespace TestHelper\Test\TestCase\Command;

use Cake\Console\ConsoleIo;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use TestHelper\Command\FixtureCheckCommand;

/**
 * @uses \TestHelper\Command\FixtureCheckCommand
 */
class FixtureCheckCommandTest extends TestCase {

	use ConsoleIntegrationTestTrait;

	/**
	 * @var array<string>
	 */
	protected array $fixtures = [
		'plugin.TestHelper.Posts',
	];

	protected FixtureCheckCommand $FixtureCheckCommand;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->out = new StubConsoleOutput();
		$this->err = new StubConsoleOutput();
		$this->FixtureCheckCommand = new FixtureCheckCommand();
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown(): void {
		unset($this->FixtureCheckCommand);

		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testDiff() {
		$io = new ConsoleIo($this->out, $this->err);
		$this->FixtureCheckCommand->run([], $io);

		$output = $this->out->output();
		$this->assertNotEmpty($output, $output);

		$error = $this->err->output();
		$this->assertNotEmpty($error, $error);
	}

}
