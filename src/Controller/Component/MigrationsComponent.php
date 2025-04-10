<?php

namespace TestHelper\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\PluginInterface;
use Cake\Datasource\ConnectionManager;

class MigrationsComponent extends Component {

	/**
	 * @return array<string>
	 */
	public function hooks(): array {
		$hooks = PluginInterface::VALID_HOOKS;
		sort($hooks);

		return $hooks;
	}

	/**
	 * @param string $database
	 * @return string
	 */
	public function getSchema(string $database): string {
		$dbConfig = ConnectionManager::getConfig('default');
		$command = 'cd ' . ROOT . ' && mysqldump --host=' . ($dbConfig['host'] ?? 'localhost') . ' --user="' . $dbConfig['username'] . '" --password="' . $dbConfig['password'] . '" --no-data ' . $database;
		exec($command, $output, $code);
		if ($code !== 0) {
			$this->getController()->Flash->error(print_r($output, true));
		}
		array_pop($output);
		$content = trim(implode(PHP_EOL, $output));

		return $content;
	}

}
