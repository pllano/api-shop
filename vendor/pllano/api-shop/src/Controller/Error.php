<?php /**
    * This file is part of the {API}$hop
    *
    * @license http://opensource.org/licenses/MIT
    * @link https://github.com/pllano/api-shop
    * @version 1.1.1
    * @package pllano.api-shop
    *
    * For the full copyright and license information, please view the LICENSE
    * file that was distributed with this source code.
*/

namespace ApiShop\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
 
use ApiShop\Model\Language;
use ApiShop\Model\Site;
use ApiShop\Model\Template;
use ApiShop\Model\SessionUser;
use ApiShop\Adapter\Menu;
use ApiShop\Utilities\Utility;
 
class Error
{
    
    private $config = [];
    protected $logger;
    protected $view;
    
    function __construct($config, $view, $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->view = $view;
    }
     
    public function post(Request $request, Response $response, array $args)
    {
        $callbackStatus = 200;
        $callbackTitle = 'Сообщение системы';
        $callbackText = 'Ошибка';
        $callback = ['status' => $callbackStatus, 'title' => $callbackTitle, 'text' => $callbackText];
        // Выводим заголовки
        $response->withStatus(200);
        $response->withHeader('Content-type', 'application/json');
        // Выводим json
        echo json_encode($callback);
    }
    
    public function get(Request $request, Response $response, array $args)
    {
        $config = $this->config;
 
        // Подключаем плагины
        $utility = new Utility();
        // Получаем параметры из URL
        $host = $request->getUri()->getHost();
        $path = $request->getUri()->getPath();
        // Конфигурация роутинга
        $routers = $config['routers'];
        // Подключаем мультиязычность
        $languages = new Language($request, $config);
        $language = $languages->get();
        // Меню, берет название класса из конфигурации
        $menu = (new Menu($config))->get();
        // Подключаем сессию, берет название класса из конфигурации
        $session = new $config['vendor']['session']['session']($config['settings']['session']['name']);
        // Данные пользователя из сессии
        $sessionUser =(new SessionUser($config))->get();
        // Подключаем временное хранилище
        $session_temp = new $config['vendor']['session']['session']("_temp");
        // Читаем ключи
        $token_key = $config['key']['token'];
        // Генерируем токен
        $token = $utility->random_token();
        // Записываем токен в сессию
        $session->token = $config['vendor']['crypto']['crypt']::encrypt($token, $token_key);
        // Контент по умолчанию
        $content = [];
        $render = '';
        
        $post_id = '/_';
        $admin_uri = '/_';
        if(!empty($session->admin_uri)) {
            $admin_uri = '/'.$session->admin_uri;
        }
        if(!empty($session->post_id)) {
            $post_id = '/'.$session->post_id;
        }
 
        // Настройки сайта
        $site = new Site($config);
        $site_config = $site->get();
        // Получаем название шаблона
        $site_template = $site->template();
        // Конфигурация шаблона
        $templateConfig = new Template($site_template);
        $template = $templateConfig->get();
        // Шаблон по умолчанию 404
        $render = $template['layouts']['404'] ? $template['layouts']['404'] : '404.html';
            
        // Заголовки по умолчанию из конфигурации
        $title = $config['settings']['site']['title'];
        $keywords = $config['settings']['site']['keywords'];
        $description = $config['settings']['site']['description'];
        $robots = $config['settings']['site']['robots'];
        $og_title = $config['settings']['site']['og_title'];
        $og_description = $config['settings']['site']['og_description'];
        $og_image = $config['settings']['site']['og_image'];
        $og_type = $config['settings']['site']['og_type'];
        $og_locale = $config['settings']['site']['og_locale'];
        $og_url = $config['settings']['site']['og_url'];
 
        $head = [
            "page" => 'home',
            "title" => $title,
            "keywords" => $keywords,
            "description" => $description,
            "robots" => $robots,
            "og_title" => $og_title,
            "og_description" => $og_description,
            "og_image" => $og_image,
            "og_type" => $og_type,
            "og_locale" => $og_locale,
            "og_url" => $og_url,
            "host" => $host,
            "path" => $path
        ];
        
        $data = [
            "head" => $head,
            "routers" => $routers,
            "site" => $site_config,
            "config" => $config['settings']['site'],
            "language" => $language,
            "template" => $template,
            "token" => $session->token,
            "post_id" => $post_id,
            "admin_uri" => $admin_uri,
            "session" => $sessionUser,
            "menu" => $menu,
            "content" => $content
        ];
 
        return $this->view->render($response, $render, $data);
    }
    
}