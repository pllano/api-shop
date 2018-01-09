<?php
/**
 * This file is part of the API SHOP
 *
 * @license http://opensource.org/licenses/MIT
 * @link https://github.com/pllano/api-shop
 * @version 1.0.1
 * @package pllano.api-shop
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiShop\Config;

use Defuse\Crypto\Key;

class Settings {
    
    public static function get() {
 
    $config = array();
 
    $config['settings']['site']['title'] = "API Shop";
    $config['settings']['site']['description'] = "Работает через RESTful API";
    $copyYear = 2016; // Set your website start date
    $curYear = date('Y'); // Keeps the second year updated
    $config['settings']['site']['copyright']['date'] = $copyYear . (($copyYear != $curYear) ? '-' . $curYear : '');
    $config['settings']['site']['copyright']['text'] = "API Shop";
 
    // Путь к папке шаблонов
    $config["settings"]["themes"]["dir"] = __DIR__ . "/../../themes";
    // Название папки с шаблонами
    $config["settings"]["themes"]["templates"] = "templates";
    // Название шаблона. По умолчанию mini-mo
    // Если работает через api будет брать название шаблона с конфигурации api
    $config["settings"]["themes"]["template"] = "mini-mo";
    // Папка куда будет кешироваться Slim\Views\Twig
    $config["settings"]["cache"] =  __DIR__ . "/../_cache/";
    // Коды ответов и ошибок
    $config["settings"]['http-codes'] = "https://github.com/pllano/APIS-2018/tree/master/http-codes/";
 
    // Конфигурация Slim
    $config["settings"]["dir"] = "config";
    $config["settings"]["displayErrorDetails"] = true;
    $config["settings"]["addContentLengthHeader"] = false;
    $config["settings"]["determineRouteBeforeAppMiddleware"] = true;
    $config["settings"]["cookies.httponly"] = true;
    $config["settings"]["phpSettings.session.cookie_httponly"] = true;
    $config["settings"]["rebodys.session.cookie_httponly"] = true;
    $config["settings"]["debug"] = true;
 
    // Конфигурация session
    $config["settings"]["session"]["name"] = "_session";
    $config["settings"]["session"]["lifetime"] = 48;
    $config["settings"]["session"]["path"] = "/";
    $config["settings"]["session"]["domain"] = "";
    $config["settings"]["session"]["secure"] = false;
    $config["settings"]["session"]["httponly"] = true;
 
    // Set session cookie path, domain and secure automatically
    $config["settings"]["session"]["cookie_autoset"] = true;
    // Path where session files are stored, PHP"s default path will be used if set null
    $config["settings"]["session"]["save_path"] = null;
    // Session cache limiter
    $config["settings"]["session"]["cache_limiter"] = "nocache";
    // Extend session lifetime after each user activity
    $config["settings"]["session"]["autorefresh"] = false;
    // Encrypt session data if string is set
    $config["settings"]["session"]["encryption_key"] = null;
    // Session namespace
    $config["settings"]["session"]["namespace"] = "_user";
 
    // Папка куда будут писатся логи Monolog
    $config["settings"]["logger"]["path"]   = isset($_ENV["docker"]) ? "php://stdout" : __DIR__ . "/../_logs/app.log";
    $config["settings"]["logger"]["name"]   = "slim-app";
    $config["settings"]["logger"]["level"] = \Monolog\Logger::DEBUG;
 
    // Путь к ключам шифрования
	$key = __DIR__ . "/key";
	if (!file_exists($key)) {
		mkdir($key, 0777, true);
	}
	
    $key_session = $key."/session.txt";
    $key_cookie = $key."/cookie.txt";
    $key_token = $key."/token.txt";
    $key_password = $key."/password.txt";
    $key_user = $key."/user.txt";
    $key_card = $key."/card.txt";
    $key_db = $key."/db.txt";
 
    // Устанавливаем ключи шифрования
    if (!file_exists($key_session)) {
        file_put_contents($key_session, (Key::createNewRandomKey())->saveToAsciiSafeString());
    }
    if (!file_exists($key_cookie)) {
        file_put_contents($key_cookie, (Key::createNewRandomKey())->saveToAsciiSafeString());
    }
    if (!file_exists($key_token)) {
        file_put_contents($key_token, (Key::createNewRandomKey())->saveToAsciiSafeString());
    }
    if (!file_exists($key_password)) {
        file_put_contents($key_password, (Key::createNewRandomKey())->saveToAsciiSafeString());
    }
    if (!file_exists($key_user)) {
        file_put_contents($key_user, (Key::createNewRandomKey())->saveToAsciiSafeString());
    }
    if (!file_exists($key_card)) {
        file_put_contents($key_card, (Key::createNewRandomKey())->saveToAsciiSafeString());
    }
    if (!file_exists($key_db)) {
        file_put_contents($key_db, (Key::createNewRandomKey())->saveToAsciiSafeString());
    }
 
    $config["key"]["session"] = Key::loadFromAsciiSafeString(file_get_contents($key_session, true));
    $config["key"]["token"] = Key::loadFromAsciiSafeString(file_get_contents($key_token, true));
    $config["key"]["cookie"] = Key::loadFromAsciiSafeString(file_get_contents($key_cookie, true));
    $config["key"]["password"] = Key::loadFromAsciiSafeString(file_get_contents($key_password, true));
    $config["key"]["user"] = Key::loadFromAsciiSafeString(file_get_contents($key_user, true));
    $config["key"]["card"] = Key::loadFromAsciiSafeString(file_get_contents($key_card, true));
    // Динамический ключ шифрования для ajax
    $config["key"]["ajax"] = (Key::createNewRandomKey())->saveToAsciiSafeString();

    // Глобальные установки баз данных
    // Доступные значения: api, json, jsonapi, mysql, elasticsearch
    // Название основной базы данных. По умолчанию api
    $config["db"]["master"] = "api";
    // Название резервной базы данных. По умолчанию json
    $config["db"]["slave"] = "json";
    // Включить или выключить переключение баз
    $config["db"]["queue"]["status"] = false; // false|true
    // Лимит выполнения запросов из очереди queue за один раз. По умолчанию 5
    $config["db"]["queue"]["limit"] = 5;
    // Использовать ping роутер или нет. По умолчанию нет true
    $config["db"]["router"] = false; // false|true
    // Синхронизировать ресурсы или нет. По умолчанию нет false
    $config["db"]["synchronize"] = false; // false|true
    // Коды ответов и ошибок
    $config["db"]['http-codes'] = "https://github.com/pllano/APIS-2018/tree/master/http-codes/";
    // Ключ шифрования в базах данных. Отдаем в чистом виде.
    $config["db"]["key"] = file_get_contents($key_db, true);

    // Настройки подключения к jsondb напрямую
    // Директория для хранения файлов json базы данных.
    $config["db"]["json"]["dir"] = __DIR__ . "/../../_json_db_/";
    // Если директории нет создать.
	if (!file_exists($config["db"]["json"]["dir"])) {
		mkdir($config["db"]["json"]["dir"], 0777, true);
	}
    // Кеширование запросов
    $config["db"]["json"]["cached"] = false; // true|false
    // Время жизни кеша
    $config["db"]["json"]["cache_lifetime"] = 60;
    // Очередь на запись
    $config["db"]["json"]["temp"] = false;
    // Работает через API
    $config["db"]["json"]["api"] = false;
    // Шифруем базу
    $config["db"]["json"]["crypt"] = false;
 
    // Настройки подключения к jsondb через API
    // URL API jsondb
    $config["db"]["jsonapi"]["url"] = "https://xti.com.ua/json-db/";
    // Доступные методы аутентификации: null, CryptoAuth, QueryKeyAuth, HttpTokenAuth, LoginPasswordAuth
    $config["db"]["jsonapi"]["auth"] = null;
    // Публичный ключ аутентификации
    $config["db"]["jsonapi"]["public_key"] = "";
    // Приватный ключ шифрования
    $config["db"]["jsonapi"]["private_key"] = "";
 
    // Если работает через API будет брать часть конфигурации из api
    $config["db"]["api"]["config"] = true; // true|false
    // URL API
    $config["db"]["api"]["url"] = "https://ua.pllano.com/api/v1/json/";
    // Доступные методы аутентификации: CryptoAuth, QueryKeyAuth, HttpTokenAuth, LoginPasswordAuth
    $config["db"]["api"]["auth"] = "QueryKeyAuth";
    // Публичный ключ аутентификации
    $config["db"]["api"]["public_key"] = "3903f7b3fb82c2e609b3f07ccfa119352f1d26c55723c3f7f8fb36a0d0e31dae";
    // Приватный ключ шифрования
    $config["db"]["api"]["private_key"] = "";
 
    // Настройки подключения к базе MySQL
    $config["db"]["mysql"]["host"] = "localhost";
    $config["db"]["mysql"]["dbname"] = "";
    $config["db"]["mysql"]["port"] = "";
    $config["db"]["mysql"]["charset"] = "utf8";
    $config["db"]["mysql"]["connect_timeout"] = 15;
    $config["db"]["mysql"]["user"] = "";
    $config["db"]["mysql"]["password"] = "";
 
    // Настройки подключения к Elasticsearch
    // По умолчанию http://localhost:9200/
    $config["db"]["elasticsearch"]["host"] = "localhost";
    $config["db"]["elasticsearch"]["port"] = 9200;
    // Учитывая то что в следующих версиях Elasticsearch не будет type
    // вы можете отключить type поставив false
    // в этом случае index будет формироватся так index_type
    $config["db"]["elasticsearch"]["type"] = true; // true|false
    $config["db"]["elasticsearch"]["index"] = "apishop";
    // Если подключение к elasticsearch требует логин и пароль установите auth=true
    $config["db"]["elasticsearch"]["auth"] = false; // true|false
    $config["db"]["elasticsearch"]["user"] = "elastic";
    $config["db"]["elasticsearch"]["password"] = "elastic_password";
 
    // API Shop позволяет одновременно работать с любым количеством баз данных
    // Название базы данных для каждого ресурса. По умолчанию api
 
    // Хранилище для ресурса site
    $config["db"]["resource"]["site"]["db"] = "api"; // +
    // Синхронизировать ресурс site или нет. По умолчанию false
    $config["db"]["resource"]["site"]["synchronize"] = false;
 
    // Хранилище для ресурса price
    $config["db"]["resource"]["price"]["db"] = "api";
    // Синхронизировать ресурс price или нет. По умолчанию false
    $config["db"]["resource"]["price"]["synchronize"] = false;

    // Хранилище для ресурса language
    $config["db"]["resource"]["language"]["db"] = "json";
    // Синхронизировать ресурс language или нет. По умолчанию false
    $config["db"]["resource"]["language"]["synchronize"] = false;
 
    // Хранилище для ресурса user
    $config["db"]["resource"]["user"]["db"] = "json";
    // Синхронизировать ресурс user или нет. По умолчанию false
    $config["db"]["resource"]["user"]["synchronize"] = false;
 
    // Хранилище для ресурса cart
    $config["db"]["resource"]["cart"]["db"] = "json";
    // Синхронизировать ресурс cart или нет. По умолчанию false
    $config["db"]["resource"]["cart"]["synchronize"] = false;
 
    // Хранилище для ресурса order
    $config["db"]["resource"]["order"]["db"] = "json";
    // Синхронизировать ресурс order или нет. По умолчанию false
    $config["db"]["resource"]["order"]["synchronize"] = false;
 
    // Хранилище для ресурса address
    $config["db"]["resource"]["address"]["db"] = "json";
    // Синхронизировать ресурс address или нет. По умолчанию false
    $config["db"]["resource"]["address"]["synchronize"] = false;
 
    // Хранилище для ресурса pay
    $config["db"]["resource"]["pay"]["db"] = "json";
    // Синхронизировать ресурс pay или нет. По умолчанию false
    $config["db"]["resource"]["pay"]["synchronize"] = false;
 
    // Хранилище для ресурса product
    $config["db"]["resource"]["product"]["db"] = "json";
    // Синхронизировать ресурс product или нет. По умолчанию false
    $config["db"]["resource"]["product"]["synchronize"] = false;
 
    // Хранилище для ресурса type
    $config["db"]["resource"]["type"]["db"] = "json";
    // Синхронизировать ресурс type или нет. По умолчанию false
    $config["db"]["resource"]["type"]["synchronize"] = false;
 
    // Хранилище для ресурса brand
    $config["db"]["resource"]["brand"]["db"] = "json";
    // Синхронизировать ресурс brand или нет. По умолчанию false
    $config["db"]["resource"]["brand"]["synchronize"] = false;
 
    // Хранилище для ресурса serie
    $config["db"]["resource"]["serie"]["db"] = "json";
    // Синхронизировать ресурс serie или нет. По умолчанию false
    $config["db"]["resource"]["serie"]["synchronize"] = false;
 
    // Хранилище для ресурса images
    $config["db"]["resource"]["images"]["db"] = "json";
    // Синхронизировать ресурс images или нет. По умолчанию false
    $config["db"]["resource"]["images"]["synchronize"] = false;
 
    // Хранилище для ресурса seo
    $config["db"]["resource"]["seo"]["db"] = "json";
    // Синхронизировать ресурс seo или нет. По умолчанию false
    $config["db"]["resource"]["seo"]["synchronize"] = false;
 
    // Хранилище для ресурса description
    $config["db"]["resource"]["description"]["db"] = "json";
    // Синхронизировать ресурс description или нет. По умолчанию false
    $config["db"]["resource"]["description"]["synchronize"] = false;
 
    // Хранилище для ресурса params
    $config["db"]["resource"]["params"]["db"] = "json";
    // Синхронизировать ресурс params или нет. По умолчанию false
    $config["db"]["resource"]["params"]["synchronize"] = false;
 
    // Хранилище для ресурса contact
    $config["db"]["resource"]["contact"]["db"] = "json";
    // Синхронизировать ресурс contact или нет. По умолчанию false
    $config["db"]["resource"]["contact"]["synchronize"] = false;
 
    // Хранилище для ресурса category
    $config["db"]["resource"]["category"]["db"] = "json";
    // Синхронизировать ресурс category или нет. По умолчанию false
    $config["db"]["resource"]["category"]["synchronize"] = false;
 
    // Хранилище для ресурса role
    $config["db"]["resource"]["role"]["db"] = "json";
    // Синхронизировать ресурс role или нет. По умолчанию false
    $config["db"]["resource"]["role"]["synchronize"] = false;
 
    // Хранилище для ресурса currency
    $config["db"]["resource"]["currency"]["db"] = "json";
    // Синхронизировать ресурс currency или нет. По умолчанию false
    $config["db"]["resource"]["currency"]["synchronize"] = false;
 
    // Хранилище для ресурса article
    $config["db"]["resource"]["article"]["db"] = "json";
    // Синхронизировать ресурс article или нет. По умолчанию false
    $config["db"]["resource"]["article"]["synchronize"] = false;
 
    // Хранилище для ресурса article_category
    $config["db"]["resource"]["article_category"]["db"] = "json";
    // Синхронизировать ресурс article_category или нет. По умолчанию false
    $config["db"]["resource"]["article_category"]["synchronize"] = false;
 
    return $config;
 
    }
 
}
 
