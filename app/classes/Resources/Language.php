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

namespace ApiShop\Resources;

use ApiShop\Database\Router as Database;
use ApiShop\Database\Ping;

class Language {

    private $db_name;
    private $language = "en";
    private $resource = "language";
 
    function __construct()
    {
        // Database\Ping контролирует состояние master и slave
        // Если база указанная в конфигурации resource недоступна, подключит master или slave
        $ping = new Ping($this->resource); // return jsonapi
        $this->db_name = $ping->get();
    }

    // Ресурс language доступен только на чтение
    public function get($language = null)
    {
        if (isset($language)){$this->language = $language;}
        // Подключаемся к базе
        $db = new Database($this->db_name);
        // Отправляем запрос и отдаем результат
        $response = $db->get($this->resource);
        foreach ($response["items"]["item"] as $value) {
            $arr[$value["id"]] = $value[$this->language];
        }
        return $arr;
    }
 
}
 