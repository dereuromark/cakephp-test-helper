<?php

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\Filesystem\Folder;
use TestApp\Controller\AppController;
use TestApp\View\AppView;
use TestHelper\Plugin as TestHelperPlugin;
use Tools\Plugin as ToolsPlugin;

if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('WINDOWS')) {
	if (DS === '\\' || substr(PHP_OS, 0, 3) === 'WIN') {
		define('WINDOWS', true);
	} else {
		define('WINDOWS', false);
	}
}

define('ROOT', dirname(__DIR__));
define('TMP', ROOT . DS . 'tmp' . DS);
define('LOGS', ROOT . DS . 'logs' . DS);
define('CACHE', TMP . 'cache' . DS);
define('APP', sys_get_temp_dir());
define('APP_DIR', 'src');
define('CAKE_CORE_INCLUDE_PATH', ROOT . '/vendor/cakephp/cakephp');
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . APP_DIR . DS);
define('TESTS', ROOT . DS . 'tests' . DS);

define('WWW_ROOT', ROOT . DS . 'webroot' . DS);
define('CONFIG', dirname(__FILE__) . DS . 'config' . DS);

ini_set('intl.default_locale', 'de-DE');

require ROOT . '/vendor/autoload.php';
require CORE_PATH . 'config/bootstrap.php';

Configure::write('App', [
	'namespace' => 'TestApp',
	'encoding' => 'UTF-8',
	'paths' => [
		'templates' => [ROOT . DS . 'tests' . DS . 'test_app' . DS . 'templates' . DS],
	],
]);
Configure::write('debug', true);

mb_internal_encoding('UTF-8');

$Tmp = new Folder(TMP);
$Tmp->create(TMP . 'cache/models', 0770);
$Tmp->create(TMP . 'cache/persistent', 0770);
$Tmp->create(TMP . 'cache/views', 0770);

$cache = [
	'default' => [
		'engine' => 'File',
		'path' => CACHE,
	],
	'_cake_core_' => [
		'className' => 'File',
		'prefix' => 'crud_myapp_cake_core_',
		'path' => CACHE . 'persistent/',
		'serialize' => true,
		'duration' => '+10 seconds',
	],
	'_cake_model_' => [
		'className' => 'File',
		'prefix' => 'crud_my_app_cake_model_',
		'path' => CACHE . 'models/',
		'serialize' => 'File',
		'duration' => '+10 seconds',
	],
];

class_alias(AppController::class, 'App\Controller\AppController');
class_alias(AppView::class, 'App\View\AppView');

Cache::setConfig($cache);
Plugin::getCollection()->add(new TestHelperPlugin());
Plugin::getCollection()->add(new ToolsPlugin());

if (getenv('db_dsn')) {
	ConnectionManager::setConfig('test', [
		'className' => 'Cake\Database\Connection',
		'url' => getenv('db_dsn'),
		'timezone' => 'UTC',
		'quoteIdentifiers' => true,
		'cacheMetadata' => true,
	]);

	return;
}

// Ensure default test connection is defined
if (!getenv('db_class')) {
	putenv('db_class=Cake\Database\Driver\Sqlite');
	putenv('db_dsn=sqlite:///:memory:');
}

ConnectionManager::setConfig('test', [
	'className' => 'Cake\Database\Connection',
	'url' => getenv('db_dsn') ?: null,
	'driver' => getenv('db_class') ?: null,
	'database' => getenv('db_database') ?: null,
	'username' => getenv('db_username') ?: null,
	'password' => getenv('db_password') ?: null,
	'timezone' => 'UTC',
	'quoteIdentifiers' => false,
	'cacheMetadata' => true,
]);
