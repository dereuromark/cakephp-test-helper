<?php

namespace TestHelper\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use TestHelper\Utility\ClassResolver;

class ClassResolverTest extends TestCase {

	/**
	 * Test type() method
	 *
	 * @return void
	 */
	public function testType() {
		$result = ClassResolver::type('Table');
		$this->assertSame('Model/Table', $result);

		$result = ClassResolver::type('Entity');
		$this->assertSame('Model/Entity', $result);

		$result = ClassResolver::type('Behavior');
		$this->assertSame('Model/Behavior', $result);

		$result = ClassResolver::type('Task');
		$this->assertSame('Command/Task', $result);

		$result = ClassResolver::type('Component');
		$this->assertSame('Controller/Component', $result);

		$result = ClassResolver::type('Helper');
		$this->assertSame('View/Helper', $result);

		$result = ClassResolver::type('CommandHelper');
		$this->assertSame('Command/Helper', $result);

		$result = ClassResolver::type('Cell');
		$this->assertSame('View/Cell', $result);

		$result = ClassResolver::type('Form');
		$this->assertSame('Form', $result);

		$result = ClassResolver::type('Mailer');
		$this->assertSame('Mailer', $result);

		// Unmapped type returns as-is
		$result = ClassResolver::type('Controller');
		$this->assertSame('Controller', $result);

		$result = ClassResolver::type('Command');
		$this->assertSame('Command', $result);
	}

	/**
	 * Test suffix() method
	 *
	 * @return void
	 */
	public function testSuffix() {
		$result = ClassResolver::suffix('Entity');
		$this->assertSame('', $result);

		$result = ClassResolver::suffix('CommandHelper');
		$this->assertSame('Helper', $result);

		$result = ClassResolver::suffix('Cell');
		$this->assertSame('Cell', $result);

		$result = ClassResolver::suffix('Form');
		$this->assertSame('Form', $result);

		$result = ClassResolver::suffix('Mailer');
		$this->assertSame('Mailer', $result);

		// Unmapped suffix returns type name
		$result = ClassResolver::suffix('Table');
		$this->assertSame('Table', $result);

		$result = ClassResolver::suffix('Controller');
		$this->assertSame('Controller', $result);

		$result = ClassResolver::suffix('Component');
		$this->assertSame('Component', $result);
	}

}
