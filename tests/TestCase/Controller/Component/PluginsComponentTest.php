<?php

namespace TestHelper\Test\TestCase\Controller;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use TestHelper\Controller\Component\PluginsComponent;

class PluginsComponentTest extends TestCase {

	/**
	 * @return void
	 */
	public function testAdjustPluginClass() {
		$component = new PluginsComponent(new ComponentRegistry(new Controller(new ServerRequest())));

		$parts = $component->hooks();

		$pluginResult = [];
		foreach ($parts as $part) {
			$pluginResult[$part . 'Exists'] = false;
		}
		foreach ($parts as $part) {
			$pluginResult[$part . 'Enabled'] = null;
		}

		$content = <<<TXT
<?php

namespace MyPlugin;

use Cake\Core\BasePlugin;
use Cake\Routing\RouteBuilder;

class Plugin extends BasePlugin {

	/**
	 * @var bool
	 */
	protected \$foo = false;

}
TXT;

		$result = $component->adjustPluginClass('MyPlugin', $content, $pluginResult);
		$expected = <<<TXT
<?php

namespace MyPlugin;

use Cake\Core\BasePlugin;
use Cake\Routing\RouteBuilder;

class Plugin extends BasePlugin {

	/**
	 * @var bool
	 */
	protected \$bootstrapEnabled = false;

	/**
	 * @var bool
	 */
	protected \$consoleEnabled = false;

	/**
	 * @var bool
	 */
	protected \$routesEnabled = false;

	/**
	 * @var bool
	 */
	protected \$foo = false;

}
TXT;
		$this->assertTextEquals($expected, $result);
	}

}
