<?php /**
    * This file is part of the {API}$hop
    *
    * @license http://opensource.org/licenses/MIT
    * @link https://github.com/pllano/api-shop
    * @version 1.0.1
    * @package pllano.api-shop
    *
    * For the full copyright and license information, please view the LICENSE
    * file that was distributed with this source code.
*/

/* 
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1); 
*/
ini_set('error_reporting', 0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Запускаем сессию PHP
session_start();

if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    
    if (is_file($file)) {
        return false;
    }
}

// Connect \Pllano\AutoRequire\Autoloader
require __DIR__ . '/vendor/AutoRequire.php';
// instantiate the loader
$require = new \Pllano\AutoRequire\Autoloader();
// Указываем путь к папке vendor для AutoRequire
$vendor_dir = __DIR__ . '/vendor';
// Указываем путь к auto_require.json
$auto_require_min = __DIR__ . '/vendor/auto_require_min.json';
$auto_require = __DIR__ . '/vendor/auto_require.json';
if (file_exists(__DIR__ . '/../vendor/_autoload.php')) {
    // Запускаем Автозагрузку
    $require->run($vendor_dir, $auto_require_min);
    // Подключаем Composer
    require __DIR__ . '/../vendor/autoload.php';
    } else {
    // Запускаем Автозагрузку
    $require->run($vendor_dir, $auto_require);
}

// Подключаем файл конфигурации системы
require __DIR__ . '/app/settings.php';
// Получаем конфигурацию
$config = \ApiShop\Config\Settings::get();

$slimArr = [];
// На всякий случай, конвертируем конфигурацию в правильный формат
foreach ($config['slim']['settings'] as $key => $value) {
    $value = str_replace(array("1", '1', 1), true, $value);
    $value = str_replace(array("0", '0', 0), false, $value);
    $slimArr[$key] = $value;
}
$slim['settings'] = $slimArr;
$config = array_replace_recursive($config, $slim);

// Подключаем Slim и отдаем ему конфигурацию
$app = new \Slim\App($config);

// Run User Session
// Запускаем сессию пользователя
(new \ApiShop\Model\User())->run();

// Подключаем Routers и Container
require __DIR__ . '/app/run.php';

// Automatically register routers
// Автоматическое подключение роутеров
$routers = glob(__DIR__ . '/app/routers/*.php');
foreach ($routers as $router) {
    require $router;
}

// Если одина из баз json запускаем jsonDB
if ($config["db"]["master"] == "json" || $config["db"]["slave"] == "json") {
    // Запускаем jsonDB\Db
    $jsonDb = new \jsonDB\Db($config['db']['json']['dir']);
    $jsonDb->run();
}

// Slim Run
$app->run();
 