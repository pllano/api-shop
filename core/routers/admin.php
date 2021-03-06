<?php /**
    * This file is part of the REST API SHOP library
    *
    * @license http://opensource.org/licenses/MIT
    * @link https://github.com/API-Shop/api-shop
    * @version 1.0
    * @package api-shop.api-shop
    *
    * For the full copyright and license information, please view the LICENSE
    * file that was distributed with this source code.
*/

use Psr\Http\Message\{ServerRequestInterface as Request, ResponseInterface as Response};
use Pllano\Core\Models\{
	ModelLanguage, 
	ModelSite, 
	ModelTemplate, 
	ModelSessionUser, 
	ModelSecurity, 
	ModelInstall
};
use Pllano\Core\Models\Admin\AdminIndex;
use Pllano\Core\Plugins\{
	PluginFilter, 
	PluginPackages, 
	PluginFile, 
	PluginTemplate, 
	PluginConfig
};
use Pllano\Hooks\Hook;
use Pllano\RouterDb\Router as RouterDb;
 
$admin_uri = '/0';
$post_id = '/0';
if(isset($session->admin_uri)) {
    $admin_uri = '/'.$session->admin_uri;
}
if(isset($session->post_id)) {
    $post_id = '/'.$session->post_id.'/';
}

$admin_router = $config['routers']['admin']['all']['route'];
$admin_index = $config['routers']['admin']['index']['route'];

//print_r($admin_uri);
// Главная страница админ панели
$routing->get($admin_uri.$admin_index.'', function (Request $request, Response $response, array $args = []) use ($core, $app) {
    
    // Получаем конфигурацию
    $config = $core->get('config');
	// Подключаем мультиязычность
	$language = $core->get('languages')->get($request);
    // Подключаем сессию
    $session = $core->get('session');
    // Конфигурация роутинга
    $routers = $config['routers'];
    // Конфигурация шаблона
    $template = $core->get('admin_template');

    // Передаем данные Hooks для обработки ожидающим классам
    $hook = new $config['vendor']['hooks']['hook']($config);
    $hook->http($request, 'GET', 'admin');
    $request = $hook->request();

    // Получаем параметры из URL
    $getParams = $request->getQueryParams();
    $host = $request->getUri()->getHost();
    $path = $request->getUri()->getPath();

    // Данные пользователя из сессии
    $sessionUser =(new ModelSessionUser($core))->get();
    // Читаем ключи
    $token_key = $config['key']['token'];
    // Генерируем токен
    $token = random_token();
    // Записываем токен в сессию
    $session->token_admin = $config['vendor']['crypto']['crypt']::encrypt($token, $token_key);
    // Шаблон по умолчанию 404
    $render = $template['layouts']['404'] ? $template['layouts']['404'] : '404.html';
    // Контент по умолчанию
    $content = '';
    
    $post_id = '/_';
    $admin_uri = '/_';
    if(!empty($session->admin_uri)) {
        $admin_uri = '/'.$session->admin_uri;
    }
    if(!empty($session->post_id)) {
        $post_id = '/'.$session->post_id;
    }
    
    // Заголовки по умолчанию из конфигурации
    $title = $language["709"].' - '.$config['settings']['site']['title'];
    $keywords = $config['settings']['site']['keywords'];
    $description = $config['settings']['site']['description'];
    $robots = $config['settings']['site']['robots'];
    $og_title = $config['settings']['site']['og_title'];
    $og_description = $config['settings']['site']['og_description'];
    $og_image = $config['settings']['site']['og_image'];
    $og_type = $config['settings']['site']['og_type'];
    $og_locale = $config['settings']['site']['og_locale'];
    $og_url = $config['settings']['site']['og_url'];
    
    if (isset($session->authorize)) {
        if ($session->role_id == 100) {
            // Подключаем класс
            $index = new AdminIndex($core);
            // Получаем массив с настройками шаблона
            $content = $index->get();
            // Получаем название шаблона
            $render = $template['layouts']['index'] ? $template['layouts']['index'] : 'index.html';
        }
    } else {
        $session->authorize = null;
    }
    
    $head = [
        "page" => $render,
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
    
    $view = [
        "head" => $head,
        "routers" => $routers,
        "config" => $config,
        "language" => $language,
        "template" => $template,
        "token" => $session->token_admin,
        "admin_uri" => $admin_uri,
        "post_id" => $post_id,
        "session" => $sessionUser,
        "content" => $content
    ];
	
	//print_r($view);
    
    // Передаем данные Hooks для обработки ожидающим классам
    $hook->get($render, $view);
    // Запись в лог
    $core->get('logger')->info($hook->logger());
    // Отдаем данные шаблонизатору
	
    return $response->write($core->get('admin')->render($hook->render(), $hook->view()));
    
});

// Список items указанного resource
$routing->get($admin_uri.$admin_router.'resource/{resource:[a-z0-9_-]+}[/{id:[a-z0-9_]+}]', function (Request $request, Response $response, array $args = []) use ($core, $app) {
    
    // Получаем конфигурацию
    $config = $core->get('config');
    // Передаем данные Hooks для обработки ожидающим классам
    $hook = new $config['vendor']['hooks']['hook']($config);
    $hook->http($request, 'GET', 'admin');
    $request = $hook->request();

    // Получаем resource из url
    if ($request->getAttribute('resource')) {
        $resource = clean($request->getAttribute('resource'));
    } else {
        $resource = null;
    }
    // Получаем id из url
    if ($request->getAttribute('id')) {
        $id = clean($request->getAttribute('id'));
    } else {
        $id = null;
    }
    // Получаем параметры из URL
    $getParams = $request->getQueryParams();
    $host = $request->getUri()->getHost();
    $path = $request->getUri()->getPath();
    // Конфигурация роутинга
    $routers = $config['routers'];
    // Конфигурация шаблона
    $template = $core->get('admin_template');
    // Подключаем мультиязычность
    $languages = $core->get('languages');
    $language = $languages->get($request);
    // Подключаем сессию
    $session = $core->get('session');
    // Данные пользователя из сессии
    $sessionUser =(new ModelSessionUser($core))->get();
    // Читаем ключи
    $token_key = $config['key']['token'];
    // Генерируем токен
    $token = random_token();
    // Записываем токен в сессию
    $session->token_admin = $config['vendor']['crypto']['crypt']::encrypt($token, $token_key);
    // Шаблон по умолчанию 404
    $render = $template['layouts']['404'] ? $template['layouts']['404'] : '404.html';
    // Контент по умолчанию
    $content = '';
    
    $post_id = '/_';
    $admin_uri = '/_';
    if(!empty($session->admin_uri)) {
        $admin_uri = '/'.$session->admin_uri;
    }
    if(!empty($session->post_id)) {
        $post_id = '/'.$session->post_id;
    }
    
    // Заголовки по умолчанию из конфигурации
    $title = $language["709"].' - '.$config['settings']['site']['title'];
    $keywords = $config['settings']['site']['keywords'];
    $description = $config['settings']['site']['description'];
    $robots = $config['settings']['site']['robots'];
    $og_title = $config['settings']['site']['og_title'];
    $og_description = $config['settings']['site']['og_description'];
    $og_image = $config['settings']['site']['og_image'];
    $og_type = $config['settings']['site']['og_type'];
    $og_locale = $config['settings']['site']['og_locale'];
    $og_url = $config['settings']['site']['og_url'];
    
    $name_db = '';
    $type = 'get';
    
    if (isset($session->authorize) && isset($resource)) {
        if ($session->role_id == 100) {
            
            $resource_list = explode(',', str_replace(['"', "'", " "], '', $config['admin']['resource_list']));
            
            if (array_key_exists($resource, array_flip($resource_list))) {

                $routerDb = new RouterDb($config);
				$_table = $resource;
				$_database = $routerDb->ping($_table);
                $resourceConfig = $config['db']['resource'][$_database] ?? null;
			    $_driver = $resourceConfig['driver'] ?? null;
			    $_adapter = $resourceConfig['adapter'] ?? null;
			    $_format = $resourceConfig['format'] ?? null;
				$routerDb->setConfig([], $_driver, $_adapter, $_format);
				$db = $routerDb->run($_database);

                if($id >= 1) {
                    $render = $resource.'_id.html';
                    $type = 'edit';
                    // Отправляем запрос и получаем данные
                    $resp = $db->get($resource, [], $id);

                    if(is_object($resp)) {
                        $resp = (array)$resp;
                    }
                    if(isset($resp['0'])) {
                        $content = $resp['0'];
                    }
                    if($resource == 'article'){
                        $title = $content['seo_title'].'- API Shop';
                        $keywords = $content['seo_keywords'].'- API Shop';
                        $description = $content['seo_description'].'- API Shop';
                    }
                } else {
                    $render = $resource.'.html';
                    // Отправляем запрос и получаем данные
                    $resp = $db->get($resource);
                    $content = $resp;
                }
            }
        }
    } else {
        $session->authorize = null;
    }
    
    $head = [
        "page" => $render,
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
    
    $view = [
        "head" => $head,
        "routers" => $routers,
        "config" => $config,
        "language" => $language,
        "template" => $template,
        "token" => $session->token_admin,
        "admin_uri" => $admin_uri,
        "post_id" => $post_id,
        "session" => $sessionUser,
        "content" => $content,
        "editor" => $config['admin']['editor'],
        "name_db" => $name_db,
        "resource" => $resource,
        "type" => $type
    ];
    
    // Передаем данные Hooks для обработки ожидающим классам
    $hook->get($render, $view);
    // Запись в лог
    $core->get('logger')->info($hook->logger());
    // Отдаем данные шаблонизатору
    return $response->write($core->get('admin')->render($hook->render(), $hook->view()));
 
});

// Содать запись в resource
$routing->post($admin_uri.$admin_router.'resource-post', function (Request $request, Response $response, array $args = []) use ($core, $app) {
    
    // Подключаем конфиг Settings\Config
    $config = $core->get('config');
    // Передаем данные Hooks для обработки ожидающим классам
    $hook = new $config['vendor']['hooks']['hook']($config);
    $hook->http($request, 'POST', 'admin');
    $request = $hook->request();

    $today = date("Y-m-d H:i:s");
    // Получаем данные отправленные нам через POST
    $post = $request->getParsedBody();
    // Подключаем сессию
    $session = $core->get('session');
    // Читаем ключи
    $token_key = $config['key']['token'];
    // Подключаем систему безопасности
    $security = new ModelSecurity($core);
    
    $resource = null;
    if (isset($post['resource'])) {
        $resource = sanitize($post['resource']);
    }
    
    try {
        // Получаем токен из сессии
        $token = $config['vendor']['crypto']['crypt']::decrypt($session->token_admin, $token_key);
    } catch (\Exception $ex) {
        $token = 0;
        if (isset($session->authorize)) {
            if ($session->authorize != 1 || $session->role_id != 100) {
                // Сообщение об Атаке или подборе токена
                $security->token($request);
            }
        } else {
            // Сообщение об Атаке или подборе токена
            $security->token($request);
        }
    }
    
    try {
        // Получаем токен из POST
        $post_csrf = $config['vendor']['crypto']['crypt']::decrypt(sanitize($post['csrf']), $token_key);
        // Чистим данные на всякий случай пришедшие через POST
        $csrf = clean($post_csrf);
    } catch (\Exception $ex) {
        $csrf = 1;
        if (isset($session->authorize)) {
            if ($session->authorize != 1 || $session->role_id != 100) {
                // Сообщение об Атаке или подборе csrf
                $security->csrf($request);
            }
        } else {
            // Сообщение об Атаке или подборе csrf
            $security->csrf($request);
        }
    }
    
    $callbackStatus = 400;
    $callbackTitle = 'Соообщение системы';
    $callbackText = '';
    
    // Проверка токена - Если токен не совпадает то ничего не делаем. Можем записать в лог или написать письмо админу
    if ($csrf == $token) {
        if (isset($session->authorize)) {
            if ($session->authorize == 1 || $session->role_id == 100) {
                if (isset($resource)) {
                    $resource_list = explode(',', str_replace(['"', "'", " "], '', $config['admin']['resource_list']));
                    if (array_key_exists($resource, array_flip($resource_list))) {
                        
                        $postArr = [];
                        $random_alias_id = random_alias_id();
                        
                        if ($resource == 'article') {
                            $postArr['title'] = 'New Article';
                            $postArr['text'] = '<div class="text-red font_56">New Text Article</div>';
                            $postArr['alias'] = 'alias-'.$random_alias_id;
                            $postArr['alias_id'] = $random_alias_id;
                            $postArr['created'] = today();
                            $postArr['category_id'] = 0;
                            $postArr['state'] = 1;
                        } elseif ($resource == 'article_category' || $resource == 'category') {
                            $postArr['title'] = 'New Category';
                            $postArr['text'] = '<div class="text-red font_56">New Text Category</div>';
                            $postArr['alias'] = 'alias-'.$random_alias_id;
                            $postArr['parent_id'] = 0;
                            $postArr['alias_id'] = $random_alias_id;
                            $postArr['created'] = today();
                            $postArr['state'] = 1;
                        } elseif ($resource == 'currency') {
                            $postArr['name'] = 'New Article';
                            $postArr['course'] = 'course';
                            $postArr['iso_code'] = 'iso_code';
                            $postArr['iso_code_num'] = 'iso_code_num';
                            $postArr['modified'] = today();
                            $postArr['state'] = 1;
                        } elseif ($resource == 'user') {
                            $postArr['iname'] = 'New';
                            $postArr['fname'] = 'User';
                            $postArr['email'] = 'user.' . rand(0,9) . rand(0,9) . rand(0,9) .'@example.com';
                            $random_number = intval( rand(0,9) . rand(0,9) . rand(0,9) . rand(0,9) . rand(0,9) . rand(0,9) . rand(0,9) ); 
                            $postArr['phone'] = '38067'.$random_number;
                            $postArr['alias'] = $random_alias_id;
                            $postArr['language'] = 'ru';
                            $postArr['password'] = password_hash($random_alias_id, PASSWORD_DEFAULT);
                            $postArr['role_id'] = 1;
                            $postArr['state'] = 1;
                        }
                        
                        $routerDb = new RouterDb($config);
                        $_table = $resource;
                        $_database = $routerDb->ping($_table);
                        $resourceConfig = $config['db']['resource'][$_database] ?? null;
                        $_driver = $resourceConfig['driver'] ?? null;
                        $_adapter = $resourceConfig['adapter'] ?? null;
                        $_format = $resourceConfig['format'] ?? null;
                        $routerDb->setConfig([], $_driver, $_adapter, $_format);
                        $db = $routerDb->run($_database);

                        $dbState = $db->post($resource, $postArr);
                        
                        if (isset($dbState)) {
                            $callbackStatus = 200;
                        } else {
                            $callbackText = 'Действие заблокировано - 2';
                        }
                    } else {
                        $callbackText = 'Действие заблокировано - 1';
                    }
                } else {
                    $callbackText = 'Ошибка !';
                }
            } else {
                $callbackText = 'Вы не администратор';
            }
        } else {
            $callbackText = 'Вы не авторизованы';
        }
    } else {
        $callbackText = 'Обновите страницу';
    }
    
    $callback = ['status' => $callbackStatus, 'title' => $callbackTitle, 'text' => $callbackText];
    // Выводим заголовки
    $response->withStatus(200);
    $response->withHeader('Content-type', 'application/json');
    // Выводим json
    return $response->write(json_encode($hook->callback($callback)));
    
});

// Удалить запись в resource
$routing->post($admin_uri.$admin_router.'resource-delete', function (Request $request, Response $response, array $args = []) use ($core, $app) {
    
    // Подключаем конфиг Settings\Config
    $config = $core->get('config');
    // Передаем данные Hooks для обработки ожидающим классам
    $hook = new $config['vendor']['hooks']['hook']($config);
    $hook->http($request, 'POST', 'admin');
    $request = $hook->request();

    // Подключаем сессию
    $session = $core->get('session');
    // Читаем ключи
    $token_key = $config['key']['token'];
    // Подключаем систему безопасности
    $security = new ModelSecurity($core);
    
    // Получаем данные отправленные нам через POST
    $post = $request->getParsedBody();
    
    $resource = null;
    if (isset($post['resource'])) {
        $resource = sanitize($post['resource']);
    }
    
    $id = null;
    if (isset($post['id'])) {
        $id = intval($post['id']);
    }
    
    try {
        // Получаем токен из сессии
        $token = $config['vendor']['crypto']['crypt']::decrypt($session->token_admin, $token_key);
    } catch (\Exception $ex) {
        $token = 0;
        if (isset($session->authorize)) {
            if ($session->authorize != 1 || $session->role_id != 100) {
                // Сообщение об Атаке или подборе токена
                $security->token($request);
            }
        } else {
            // Сообщение об Атаке или подборе токена
            $security->token($request);
        }
    }
    
    try {
        // Получаем токен из POST
        $post_csrf = $config['vendor']['crypto']['crypt']::decrypt(sanitize($post['csrf']), $token_key);
        // Чистим данные на всякий случай пришедшие через POST
        $csrf = clean($post_csrf);
    } catch (\Exception $ex) {
        $csrf = 1;
        if (isset($session->authorize)) {
            if ($session->authorize != 1 || $session->role_id != 100) {
                // Сообщение об Атаке или подборе csrf
                $security->csrf($request);
            }
        } else {
            // Сообщение об Атаке или подборе csrf
            $security->csrf($request);
        }
    }
    
    $callbackStatus = 400;
    $callbackTitle = 'Соообщение системы';
    $callbackText = '';
	
/* 	$id = 10;
	$resource = 'article';
	$csrf = 1;
	$token = 1; */
    
    // Проверка токена - Если токен не совпадает то ничего не делаем. Можем записать в лог или написать письмо админу
    if ($csrf == $token) {
        if (isset($session->authorize)) {
            if ($session->authorize == 1 || $session->role_id == 100) {
                if (isset($resource) && isset($id)) {
                    $resource_list = explode(',', str_replace(['"', "'", " "], '', $config['admin']['resource_list']));
                    if (array_key_exists($resource, array_flip($resource_list))) {
                        
                        $routerDb = new RouterDb($config);
                        $_table = $resource;
                        $_database = $routerDb->ping($_table);
                        $resourceConfig = $config['db']['resource'][$_database] ?? null;
                        $_driver = $resourceConfig['driver'] ?? null;
                        $_adapter = $resourceConfig['adapter'] ?? null;
                        $_format = $resourceConfig['format'] ?? null;
                        $routerDb->setConfig([], $_driver, $_adapter, $_format);
                        $db = $routerDb->run($_database);
						
                        // Передаем данные Hooks для обработки ожидающим классам
                        $hook->post($resource, $_database, 'DELETE', [], $id);
                        $hookState = $hook->state();
                        // Если Hook вернул true
                        if ($hookState == true) {
                            // Обновленные Hooks данные
                            $hookResource = $hook->resource();
                            $hookId = $hook->id();
                            // Отправляем запрос в базу
                            $dbState = $db->delete($hookResource, $hookId);

                            if (isset($dbState)) {
                                $callbackStatus = 200;
                            } else {
                                $callbackText = 'Действие заблокировано';
                            }
                        }
                    } else {
                        $callbackText = 'Действие заблокировано';
                    }
                } else {
                    $callbackText = 'Ошибка !';
                }
            } else {
                $callbackText = 'Вы не администратор';
            }
        } else {
            $callbackText = 'Вы не авторизованы';
        }
    } else {
        $callbackText = 'Обновите страницу';
    }
    
    $callback = ['status' => $callbackStatus, 'title' => $callbackTitle, 'text' => $callbackText];
    // Выводим заголовки
    $response->withStatus(200);
    $response->withHeader('Content-type', 'application/json');


    // Выводим json
    return $response->write(json_encode($hook->callback($callback)));
    
});

// Редактируем запись в resource
$routing->post($admin_uri.$admin_router.'resource-put/{resource:[a-z0-9_-]+}[/{id:[a-z0-9_]+}]', function (Request $request, Response $response, array $args = []) use ($core, $app) {
    
    // Подключаем конфиг Settings\Config
    $config = $core->get('config');
    // Передаем данные Hooks для обработки ожидающим классам
    $hook = new $config['vendor']['hooks']['hook']($config);
    $hook->http($request, 'POST', 'admin');
    $request = $hook->request();

    // Подключаем сессию
    $session = $core->get('session');
    // Читаем ключи
    $token_key = $config['key']['token'];
    
    // Получаем resource из url
    if ($request->getAttribute('resource')) {
        $resource_list = explode(',', str_replace(['"', "'", " "], '', $config['admin']['resource_list']));
        $resource = clean($request->getAttribute('resource'));
        if (array_key_exists($resource, array_flip($resource_list))) {
            $table = json_decode(file_get_contents($config["db"]["json"]["dir"].'/'.$resource.'.config.json'), true);
            // Получаем данные отправленные нам через POST
            $post = $request->getParsedBody();
            $post = (array)$post;
        } else {
            $resource = null;
        }
    } else {
        $resource = null;
    }
    
    // Получаем id из url
    if ($request->getAttribute('id')) {
        $id = intval(clean($request->getAttribute('id')));
        } else {
        $id = null;
    }
    
    try {
        // Получаем токен из сессии
        $token = $config['vendor']['crypto']['crypt']::decrypt($session->token_admin, $token_key);
    } catch (\Exception $ex) {
        $token = 0;
        if (isset($session->authorize)) {
            if ($session->authorize != 1 || $session->role_id != 100) {
                // Сообщение об Атаке или подборе токена
                (new ModelSecurity($core))->token($request);
            }
        } else {
            // Сообщение об Атаке или подборе токена
            (new ModelSecurity($core))->token($request);
        }
    }
    
    try {
        // Получаем токен из POST
        $post_csrf = $config['vendor']['crypto']['crypt']::decrypt(sanitize($post['csrf']), $token_key);
        // Чистим данные на всякий случай пришедшие через POST
        $csrf = clean($post_csrf);
    } catch (\Exception $ex) {
        $csrf = 1;
        if (isset($session->authorize)) {
            if ($session->authorize != 1 || $session->role_id != 100) {
                // Сообщение об Атаке или подборе csrf
                (new ModelSecurity($core))->csrf($request);
            }
        } else {
            // Сообщение об Атаке или подборе csrf
            (new ModelSecurity($core))->csrf($request);
        }
    }
    
    $callbackStatus = 400;
    $callbackTitle = 'Соообщение системы';
    $callbackText = '';
    
    // Проверка токена - Если токен не совпадает то ничего не делаем. Можем записать в лог или написать письмо админу
    if ($csrf == $token) {
        if (isset($session->authorize)) {
            if ($session->authorize == 1 && $session->role_id == 100) {
                if (isset($resource) && isset($id)) {
                    if (array_key_exists($resource, array_flip($resource_list))) {
                        $saveArr = [];
                        $resource_id = $resource."_id";
                        
                        foreach($post as $key => $value)
                        {
                            if (array_key_exists($key, $table["schema"]) && $value != "" && $key != "id") {
                                if($key == "text" || $key == "text_ru" || $key == "text_ua" || $key == "text_de" || $key == "text_en") {
                                    $saveArr[$key] = cleanText($value);
                                } elseif ($key == $resource_id) {
                                    $value = str_replace(['"', "'", " "], '', $value);
                                    $saveArr[$key] = intval(clean($value));
                                } elseif ($key == "phone") {
                                    $value = str_replace(['"', "'", " "], '', $value);
                                    $saveArr[$key] = strval(clean($value));
                                } elseif ($key == "password") {
                                    if(strlen($value) >= 55 && strlen($value) <= 65) {
                                        $saveArr[$key] = sanitize($value);
                                    } else {
                                        $saveArr[$key] = password_hash(sanitize($value), PASSWORD_DEFAULT);
                                    }
                                } else {
                                    if (is_numeric(clean($value))) {
                                        $value = str_replace(['"', "'", " "], '', $value);
                                        $saveArr[$key] = intval(clean($value));
                                        } elseif (is_float(clean($value))) {
                                        $value = str_replace(['"', "'", " "], '', $value);
                                        $saveArr[$key] = float(clean($value));
                                        } elseif (is_bool(clean($value))) {
                                        $value = str_replace(['"', "'", " "], '', $value);
                                        $saveArr[$key] = boolval(clean($value));
                                        } elseif (is_string(clean($value))) {
                                        $saveArr[$key] = sanitize(strval($value));
                                    }
                                }
                            }
                        }
                        
                        $routerDb = new RouterDb($config);
                        $_table = $resource;
                        $_database = $routerDb->ping($_table);
                        $resourceConfig = $config['db']['resource'][$_database] ?? null;
                        $_driver = $resourceConfig['driver'] ?? null;
                        $_adapter = $resourceConfig['adapter'] ?? null;
                        $_format = $resourceConfig['format'] ?? null;
                        $routerDb->setConfig([], $_driver, $_adapter, $_format);
                        $db = $routerDb->run($_database);
                        // Обновляем данные
                        $requestDb = $db->put($_table, $saveArr, $id);

                        if (isset($requestDb)) {
                            $callbackStatus = 200;
                        } else {
                            $callbackText = 'Действие заблокировано';
                        }
                    } else {
                        $callbackText = 'Действие заблокировано';
                    }
                } else {
                    $callbackText = 'Ошибка !';
                }
            } else {
                $callbackText = 'Вы не администратор';
            }
        } else {
            $callbackText = 'Вы не авторизованы';
        }
    } else {
        $callbackText = 'Обновите страницу';
    }
    
    $callback = ['status' => $callbackStatus, 'title' => $callbackTitle, 'text' => $callbackText];
    // Выводим заголовки
    $response->withStatus(200);
    $response->withHeader('Content-type', 'application/json');


    // Выводим json
    return $response->write(json_encode($hook->callback($callback)));
    
});

// Активировать заказ
$routing->post($admin_uri.$admin_router.'order-activate', function (Request $request, Response $response, array $args = []) use ($core, $app) {
    
    // Подключаем конфиг Settings\Config
    $config = $core->get('config');
    // Передаем данные Hooks для обработки ожидающим классам
    $hook = new $config['vendor']['hooks']['hook']($config);
    $hook->http($request, 'POST', 'admin');
    $request = $hook->request();

    // Подключаем сессию
    $session = $core->get('session');
    // Читаем ключи
    $token_key = $config['key']['token'];
    
    // Получаем данные отправленные нам через POST
    $post = $request->getParsedBody();
    
    try {
        // Получаем токен из сессии
        $token = $config['vendor']['crypto']['crypt']::decrypt($session->token_admin, $token_key);
    } catch (\Exception $ex) {
        $token = 0;
        if (isset($session->authorize)) {
            if ($session->authorize != 1 || $session->role_id != 100) {
                // Сообщение об Атаке или подборе токена
                (new ModelSecurity($core))->token($request);
            }
        } else {
            // Сообщение об Атаке или подборе токена
            (new ModelSecurity($core))->token($request);
        }
    }
    
    try {
        // Получаем токен из POST
        $post_csrf = $config['vendor']['crypto']['crypt']::decrypt(sanitize($post['csrf']), $token_key);
        // Чистим данные на всякий случай пришедшие через POST
        $csrf = clean($post_csrf);
    } catch (\Exception $ex) {
        $csrf = 1;
        if (isset($session->authorize)) {
            if ($session->authorize != 1 || $session->role_id != 100) {
                // Сообщение об Атаке или подборе csrf
                (new ModelSecurity($core))->csrf($request);
            }
        } else {
            // Сообщение об Атаке или подборе csrf
            (new ModelSecurity($core))->csrf($request);
        }
    }
    
    $callbackStatus = 400;
    $callbackTitle = 'Соообщение системы';
    $callbackText = '';
    
    // Проверка токена - Если токен не совпадает то ничего не делаем. Можем записать в лог или написать письмо админу
    if ($csrf == $token) {
        if (isset($session->authorize)) {
            if ($session->authorize == 1 || $session->role_id == 100) {
                if (isset($post['alias'])) {
                    $alias = sanitize($post['alias']);
                    
                    if ($alias == true) {
                        // Ответ
                        $callbackStatus = 200;
                    } else {
                        $callbackText = 'Действие заблокировано';
                    }
                } else {
                    $callbackText = 'Не определен alias заказа';
                }
            } else {
                $callbackText = 'Вы не являетесь администратором';
            }
        } else {
            $callbackText = 'Вы не авторизованы';
        }
    } else {
        $callbackText = 'Ошибка';
    }
    
    $callback = ['status' => $callbackStatus, 'title' => $callbackTitle, 'text' => $callbackText];
    // Выводим заголовки
    $response->withStatus(200);
    $response->withHeader('Content-type', 'application/json');


    // Выводим json
    return $response->write(json_encode($hook->callback($callback)));
    
});

// Купить и установить шаблон
$routing->post($admin_uri.$admin_router.'template-buy', function (Request $request, Response $response, array $args = []) use ($core, $app) {
    
    // Подключаем конфиг Settings\Config
    $config = $core->get('config');
    // Передаем данные Hooks для обработки ожидающим классам
    $hook = new $config['vendor']['hooks']['hook']($config);
    $hook->http($request, 'POST', 'admin');
    $request = $hook->request();

    // Подключаем сессию
    $session = $core->get('session');
    // Читаем ключи
    $token_key = $config['key']['token'];
    
    try {
        // Получаем токен из сессии
        $token = $config['vendor']['crypto']['crypt']::decrypt($session->token_admin, $token_key);
    } catch (\Exception $ex) {
        (new ModelSecurity($core))->token($request);
        // Сообщение об Атаке или подборе токена
    }
    // Получаем данные отправленные нам через POST
    $post = $request->getParsedBody();
    try {
        // Получаем токен из POST
        $post_csrf = $config['vendor']['crypto']['crypt']::decrypt(sanitize($post['csrf']), $token_key);
        } catch (\Exception $ex) {
        (new ModelSecurity($core))->csrf($request);
        // Сообщение об Атаке или подборе csrf
    }

    // Чистим данные на всякий случай пришедшие через POST
    $csrf = clean($post_csrf);
    
    $callbackStatus = 400;
    $callbackTitle = 'Соообщение системы';
    $callbackText = '';
    
    // Проверка токена - Если токен не совпадает то ничего не делаем. Можем записать в лог или написать письмо админу
    if ($csrf == $token) {
        
        if (isset($post['alias'])) {
            $alias = sanitize($post['alias']);
            $callbackStatus = 200;
            } else {
            $callbackText = 'Ошибка !';
        }
        } else {
        $callbackText = 'Ошибка !';
    }
    
    $callback = ['status' => $callbackStatus, 'title' => $callbackTitle, 'text' => $callbackText];
    // Выводим заголовки
    $response->withStatus(200);
    $response->withHeader('Content-type', 'application/json');


    // Выводим json
    return $response->write(json_encode($hook->callback($callback)));
    
});

// Установить шаблон
$routing->post($admin_uri.$admin_router.'template-install', function (Request $request, Response $response, array $args = []) use ($core, $app) {
    
    // Подключаем конфиг Settings\Config
    $config = $core->get('config');
    // Передаем данные Hooks для обработки ожидающим классам
    $hook = new $config['vendor']['hooks']['hook']($config);
    $hook->http($request, 'POST', 'admin');
    $request = $hook->request();

    // Подключаем сессию
    $session = $core->get('session');
    // Читаем ключи
    $token_key = $config['key']['token'];
    // Получаем данные отправленные нам через POST
    $post = $request->getParsedBody();
    
    try {
        // Получаем токен из сессии
        $token = $config['vendor']['crypto']['crypt']::decrypt($session->token_admin, $token_key);
        } catch (\Exception $ex) {
        $token = 0;
        if (isset($session->authorize)) {
            if ($session->authorize != 1 || $session->role_id != 100) {
                // Сообщение об Атаке или подборе токена
                (new ModelSecurity($core))->token($request);
            }
            } else {
            // Сообщение об Атаке или подборе токена
            (new ModelSecurity($core))->token($request);
        }
    }
    
    try {
        // Получаем токен из POST
        $post_csrf = $config['vendor']['crypto']['crypt']::decrypt(sanitize($post['csrf']), $token_key);
        // Чистим данные на всякий случай пришедшие через POST
        $csrf = clean($post_csrf);
        } catch (\Exception $ex) {
        $csrf = 1;
        if (isset($session->authorize)) {
            if ($session->authorize != 1 || $session->role_id != 100) {
                // Сообщение об Атаке или подборе csrf
                (new ModelSecurity($core))->csrf($request);
            }
            } else {
            // Сообщение об Атаке или подборе csrf
            (new ModelSecurity($core))->csrf($request);
        }
    }
    
    $callbackStatus = 400;
    $callbackTitle = 'Соообщение системы';
    $callbackText = '';
    
    // Проверка токена - Если токен не совпадает то ничего не делаем. Можем записать в лог или написать письмо админу
    if ($csrf == $token) {
        if (isset($post['alias'])) {
            
            $dir = null;
            $uri = null;
            $name = null;
            
            $alias = sanitize($post['alias']);
            
            $templates_list = (new ModelInstall($app))->templates_list($config['seller']['store']);
            
            if (count($templates_list) >= 1) {
                foreach($templates_list as $value)
                {
                    if ($value['item']["alias"] == $alias) {
                        
                        $dir = $value['item']['dir'];
                        $uri = $value['item']['uri'];
                        $name = $value['item']['dir'];
                        
                        if(isset($dir) && isset($uri) && isset($name)) {
                            // Подключаем глобальную конфигурацию
                            $glob_config = new PluginConfig($config);
                            // Устанавливаем шаблон
                            $template_install = $glob_config->template_install($name, $dir, $uri);
                            
                            if ($template_install === true) {
                                $callbackStatus = 200;
                                }  else {
                                $callbackText = 'Ошибка !';
                            }
                            
                            } else {
                            $callbackText = 'Ошибка !';
                        }
                    }
                }
            }
            } else {
            $callbackText = 'Ошибка !';
        }
        } else {
        $callbackText = 'Ошибка !';
    }
    
    $callback = ['status' => $callbackStatus, 'title' => $callbackTitle, 'text' => $callbackText];
    // Выводим заголовки
    $response->withStatus(200);
    $response->withHeader('Content-type', 'application/json');
    // Выводим json
    return $response->write(json_encode($hook->callback($callback)));
    
});

// Активировать шаблон
$routing->post($admin_uri.$admin_router.'template-activate', function (Request $request, Response $response, array $args = []) use ($core, $app) {
    
    // Подключаем конфиг Settings\Config
    $config = $core->get('config');
    // Передаем данные Hooks для обработки ожидающим классам
    $hook = new $config['vendor']['hooks']['hook']($config);
    $hook->http($request, 'POST', 'admin');
    $request = $hook->request();

    // Подключаем сессию
    $session = $core->get('session');
    // Читаем ключи
    $token_key = $config['key']['token'];
    // Получаем данные отправленные нам через POST
    $post = $request->getParsedBody();
    
    try {
        // Получаем токен из сессии
        $token = $config['vendor']['crypto']['crypt']::decrypt($session->token_admin, $token_key);
        } catch (\Exception $ex) {
        $token = 0;
        if (isset($session->authorize)) {
            if ($session->authorize != 1 || $session->role_id != 100) {
                // Сообщение об Атаке или подборе токена
                (new ModelSecurity($core))->token($request);
            }
            } else {
            // Сообщение об Атаке или подборе токена
            (new ModelSecurity($core))->token($request);
        }
    }
    
    try {
        // Получаем токен из POST
        $post_csrf = $config['vendor']['crypto']['crypt']::decrypt(sanitize($post['csrf']), $token_key);
        // Чистим данные на всякий случай пришедшие через POST
        $csrf = clean($post_csrf);
        } catch (\Exception $ex) {
        $csrf = 1;
        if (isset($session->authorize)) {
            if ($session->authorize != 1 || $session->role_id != 100) {
                // Сообщение об Атаке или подборе csrf
                (new ModelSecurity($core))->csrf($request);
            }
            } else {
            // Сообщение об Атаке или подборе csrf
            (new ModelSecurity($core))->csrf($request);
        }
    }
    
    $callbackStatus = 400;
    $callbackTitle = 'Соообщение системы';
    $callbackText = '';
    
    // Проверка токена - Если токен не совпадает то ничего не делаем. Можем записать в лог или написать письмо админу
    if ($csrf == $token) {
        if (isset($session->authorize)) {
            if ($session->authorize == 1 && $session->role_id == 100) {
                if (isset($post['dir'])) {
                    
                    $dir = sanitize($post['dir']);
                    $alias = sanitize($post['alias']);
                    
                    // Активируем шаблон
                    (new PluginConfig($config))->template_activate($dir, $alias);
                    
                    $callbackStatus = 200;
                    
                    } else {
                    $callbackText = 'Не определено название шаблона';
                }
                } else {
                $callbackText = 'Вы не являетесь администратором';
            }
            } else {
            $callbackText = 'Вы не авторизованы';
        }
        } else {
        $callbackText = 'Ошибка !';
    }
    
    $callback = ['status' => $callbackStatus, 'title' => $callbackTitle, 'text' => $callbackText];
    // Выводим заголовки
    $response->withStatus(200);
    $response->withHeader('Content-type', 'application/json');


    // Выводим json
    return $response->write(json_encode($hook->callback($callback)));
    
});

// Удалить шаблон
$routing->post($admin_uri.$admin_router.'template-delete', function (Request $request, Response $response, array $args = []) use ($core, $app) {
    
    // Подключаем конфиг Settings\Config
    $config = $core->get('config');
    // Передаем данные Hooks для обработки ожидающим классам
    $hook = new $config['vendor']['hooks']['hook']($config);
    $hook->http($request, 'POST', 'admin');
    $request = $hook->request();

    // Подключаем сессию
    $session = $core->get('session');
    // Читаем ключи
    $token_key = $config['key']['token'];
    
    try {
        // Получаем токен из сессии
        $token = $config['vendor']['crypto']['crypt']::decrypt($session->token_admin, $token_key);
        } catch (\Exception $ex) {
        (new ModelSecurity($core))->token($request);
        // Сообщение об Атаке или подборе токена
    }
    // Получаем данные отправленные нам через POST
    $post = $request->getParsedBody();
    try {
        // Получаем токен из POST
        $post_csrf = $config['vendor']['crypto']['crypt']::decrypt(sanitize($post['csrf']), $token_key);
        } catch (\Exception $ex) {
        (new ModelSecurity($core))->csrf($request);
        // Сообщение об Атаке или подборе csrf
    }

    // Чистим данные на всякий случай пришедшие через POST
    $csrf = clean($post_csrf);
    
    $callbackStatus = 400;
    $callbackTitle = 'Соообщение системы';
    $callbackText = '';
    
    // Проверка токена - Если токен не совпадает то ничего не делаем. Можем записать в лог или написать письмо админу
    if ($csrf == $token) {
        
        if (isset($post['dir'])) {
            $dir = sanitize($post['dir']);
            
            $directory = $config["settings"]["themes"]["dir"].'/'.$config["template"]["front_end"]["themes"]["template"].'/'.$dir;
            // Подключаем класс
            $admin = new PluginFile();
            // Получаем массив
            $admin->delete_dir($directory);
            
            $callbackStatus = 200;
            
            } else {
            $callbackText = 'Ошибка !';
        }
        } else {
        $callbackText = 'Ошибка !';
    }
    
    $callback = ['status' => $callbackStatus, 'title' => $callbackTitle, 'text' => $callbackText];
    // Выводим заголовки
    $response->withStatus(200);
    $response->withHeader('Content-type', 'application/json');


    // Выводим json
    return $response->write(json_encode($hook->callback($callback)));
    
});

// Список шаблонов
$routing->get($admin_uri.$admin_router.'template', function (Request $request, Response $response, array $args = []) use ($core, $app) {
    
    // Получаем конфигурацию
    $config = $core->get('config');
    // Передаем данные Hooks для обработки ожидающим классам
    $hook = new $config['vendor']['hooks']['hook']($config);
    $hook->http($request, 'GET', 'admin');
    $request = $hook->request();

    // Получаем параметры из URL
    $getParams = $request->getQueryParams();
    $host = $request->getUri()->getHost();
    $path = $request->getUri()->getPath();
    // Конфигурация роутинга
    $routers = $config['routers'];
    // Конфигурация шаблона
    $template = $core->get('admin_template');
    // Подключаем мультиязычность
    $languages = $core->get('languages');
    $language = $languages->get($request);
    // Подключаем сессию
    $session = $core->get('session');
    // Данные пользователя из сессии
    $sessionUser =(new ModelSessionUser($core))->get();
    // Читаем ключи
    $token_key = $config['key']['token'];
    // Генерируем токен
    $token = random_token();
    // Записываем токен в сессию
    $session->token_admin = $config['vendor']['crypto']['crypt']::encrypt($token, $token_key);
    // Шаблон по умолчанию 404
    $render = $template['layouts']['404'] ? $template['layouts']['404'] : '404.html';
    // Контент по умолчанию
    $content = '';
    
    $post_id = '/_';
    $admin_uri = '/_';
    if(!empty($session->admin_uri)) {
        $admin_uri = '/'.$session->admin_uri;
    }
    if(!empty($session->post_id)) {
        $post_id = '/'.$session->post_id;
    }
    
    // Заголовки по умолчанию из конфигурации
    $title = $language["815"].' - '.$config['settings']['site']['title'];
    $keywords = $config['settings']['site']['keywords'];
    $description = $config['settings']['site']['description'];
    $robots = $config['settings']['site']['robots'];
    $og_title = $config['settings']['site']['og_title'];
    $og_description = $config['settings']['site']['og_description'];
    $og_image = $config['settings']['site']['og_image'];
    $og_type = $config['settings']['site']['og_type'];
    $og_locale = $config['settings']['site']['og_locale'];
    $og_url = $config['settings']['site']['og_url'];
    
    $api = '';
    
    if (isset($session->authorize)) {
        if ($session->role_id == 100) {
            // Подключаем класс
            $templates = new PluginTemplate($config);
            // Получаем массив с настройками шаблона
            $content = $templates->get();
            $api = (new ModelInstall($app))->templates_list($config['seller']['store']);
 
            $render = $template['layouts']['templates'] ? $template['layouts']['templates'] : 'templates.html';
        }
    } else {
        $session->authorize = null;
    }
    
    $head = [
        "page" => $render,
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
    
    $view = [
        "head" => $head,
        "routers" => $routers,
        "config" => $config,
        "language" => $language,
        "template" => $template,
        "token" => $session->token_admin,
        "admin_uri" => $admin_uri,
        "post_id" => $post_id,
        "session" => $sessionUser,
        "content" => $content,
        "editor" => $config['admin']['editor'],
        "api" => $api
    ];
    
    // Передаем данные Hooks для обработки ожидающим классам
    $hook->get($render, $view);
    // Запись в лог
    $core->get('logger')->info($hook->logger());
    // Отдаем данные шаблонизатору
    return $response->write($core->get('admin')->render($hook->render(), $hook->view()));
    
});

// Страница шаблона
$routing->get($admin_uri.$admin_router.'template/{alias:[a-z0-9_-]+}', function (Request $request, Response $response, array $args = []) use ($core, $app) {
    
    // Получаем конфигурацию
    $config = $core->get('config');
    // Передаем данные Hooks для обработки ожидающим классам
    $hook = new $config['vendor']['hooks']['hook']($config);
    $hook->http($request, 'GET', 'admin');
    $request = $hook->request();

    // Получаем alias из url
    if ($request->getAttribute('alias')) {
        $alias = clean($request->getAttribute('alias'));
    } else {
        $alias = null;
    }
    // Получаем параметры из URL
    $getParams = $request->getQueryParams();
    $host = $request->getUri()->getHost();
    $path = $request->getUri()->getPath();
    // Конфигурация роутинга
    $routers = $config['routers'];
    // Конфигурация шаблона
    $template = $core->get('admin_template');
    // Подключаем мультиязычность
    $languages = $core->get('languages');
    $language = $languages->get($request);
    // Подключаем сессию
    $session = $core->get('session');
    // Данные пользователя из сессии
    $sessionUser =(new ModelSessionUser($core))->get();
    // Читаем ключи
    $token_key = $config['key']['token'];
    // Генерируем токен
    $token = random_token();
    // Записываем токен в сессию
    $session->token_admin = $config['vendor']['crypto']['crypt']::encrypt($token, $token_key);
    // Шаблон по умолчанию 404
    $render = $template['layouts']['404'] ? $template['layouts']['404'] : '404.html';
    // Контент по умолчанию
    $content = '';
    
    $post_id = '/_';
    $admin_uri = '/_';
    if(!empty($session->admin_uri)) {
        $admin_uri = '/'.$session->admin_uri;
    }
    if(!empty($session->post_id)) {
        $post_id = '/'.$session->post_id;
    }
    
    // Заголовки по умолчанию из конфигурации
    $title = $language["709"].' '.$language["814"].' - '.$config['settings']['site']['title'];
    $keywords = $config['settings']['site']['keywords'];
    $description = $config['settings']['site']['description'];
    $robots = $config['settings']['site']['robots'];
    $og_title = $config['settings']['site']['og_title'];
    $og_description = $config['settings']['site']['og_description'];
    $og_image = $config['settings']['site']['og_image'];
    $og_type = $config['settings']['site']['og_type'];
    $og_locale = $config['settings']['site']['og_locale'];
    $og_url = $config['settings']['site']['og_url'];
    
    if (isset($session->authorize) && isset($alias)) {
        if ($session->role_id) {
            // Подключаем класс
            $templates = new PluginTemplate($config, $alias);
            $content = $templates->getOne();
            $render = $template['layouts']['template'] ? $template['layouts']['template'] : 'template.html';
        }
    } else {
        $session->authorize = null;
    }
    
    $head = [
        "page" => $render,
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
    
    $view = [
        "head" => $head,
        "routers" => $routers,
        "config" => $config,
        "language" => $language,
        "template" => $template,
        "token" => $session->token_admin,
        "admin_uri" => $admin_uri,
        "post_id" => $post_id,
        "session" => $sessionUser,
        "content" => $content
    ];
    
    // Передаем данные Hooks для обработки ожидающим классам
    $hook->get($render, $view);
    // Запись в лог
    $core->get('logger')->info($hook->logger());
    // Отдаем данные шаблонизатору
    return $response->write($core->get('admin')->render($hook->render(), $hook->view()));
    
});

// Редактируем настройки шаблона
$routing->post($admin_uri.$admin_router.'template/{alias:[a-z0-9_-]+}', function (Request $request, Response $response, array $args = []) use ($core, $app) {
    
    // Получаем конфигурацию
    $config = $core->get('config');
    // Передаем данные Hooks для обработки ожидающим классам
    $hook = new $config['vendor']['hooks']['hook']($config);
    $hook->http($request, 'POST', 'admin');
    $request = $hook->request();

    // Получаем alias из url
    if ($request->getAttribute('alias')) {
        $alias = clean($request->getAttribute('alias'));
        } else {
        $alias = null;
    }
    // Получаем параметры из URL
    $getParams = $request->getQueryParams();
    $host = $request->getUri()->getHost();
    $path = $request->getUri()->getPath();
    // Конфигурация роутинга
    $routers = $config['routers'];
    // Конфигурация шаблона
    $template = $core->get('admin_template');
    // Подключаем мультиязычность
    $languages = $core->get('languages');
    $language = $languages->get($request);
    // Подключаем сессию
    $session = $core->get('session');
    // Данные пользователя из сессии
    $sessionUser =(new ModelSessionUser($core))->get();
    // Читаем ключи
    $token_key = $config['key']['token'];
    // Генерируем токен
    $token = random_token();
    // Записываем токен в сессию
    $session->token_admin = $config['vendor']['crypto']['crypt']::encrypt($token, $token_key);
    // Шаблон по умолчанию 404
    $render = $template['layouts']['404'] ? $template['layouts']['404'] : '404.html';
    // Контент по умолчанию
    $content = '';
    
    $post_id = '/_';
    $admin_uri = '/_';
    if(!empty($session->admin_uri)) {
        $admin_uri = '/'.$session->admin_uri;
    }
    if(!empty($session->post_id)) {
        $post_id = '/'.$session->post_id;
    }
    
    // Заголовки по умолчанию из конфигурации
    $title = $language["709"].' '.$language["814"].' - '.$config['settings']['site']['title'];
    $keywords = $config['settings']['site']['keywords'];
    $description = $config['settings']['site']['description'];
    $robots = $config['settings']['site']['robots'];
    $og_title = $config['settings']['site']['og_title'];
    $og_description = $config['settings']['site']['og_description'];
    $og_image = $config['settings']['site']['og_image'];
    $og_type = $config['settings']['site']['og_type'];
    $og_locale = $config['settings']['site']['og_locale'];
    $og_url = $config['settings']['site']['og_url'];
    
    if (isset($session->authorize) && isset($alias)) {
        if ($session->role_id) {
            // Подключаем класс
            $templates = new PluginTemplate($config, $alias);
            // Получаем массив
            $arrJson = $templates->getOne();
            //print_r($content);
            // Массив из POST
            $paramPost = $request->getParsedBody();
            // Соеденяем массивы
            $newArr = array_replace_recursive($arrJson, $paramPost);
            // Сохраняем в файл
            $templates->put($newArr);
            $content = $templates->getOne();
            
            $render = $template['layouts']['template'] ? $template['layouts']['template'] : 'template.html';
            
        }
    } else {
        $session->authorize = null;
    }
    
    $head = [
    "page" => $render,
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
    
    $view = [
    "head" => $head,
    "routers" => $routers,
    "config" => $config,
    "language" => $language,
    "template" => $template,
    "token" => $session->token_admin,
    "admin_uri" => $admin_uri,
    "post_id" => $post_id,
    "session" => $sessionUser,
    "content" => $content
    ];
    
    // Передаем данные Hooks для обработки ожидающим классам
    $hook->get($render, $view);
    // Запись в лог
    $core->get('logger')->info($hook->logger());
    // Отдаем данные шаблонизатору
    return $response->write($core->get('admin')->render($hook->render(), $hook->view()));
    
});

// Станица пакета
$routing->map(['GET', 'POST'], $admin_uri.$admin_router.'package/{vendor:[a-z0-9_-]+}.{package:[a-z0-9_-]+}', function (Request $request, Response $response, array $args = []) use ($core, $app) {
    // Получаем конфигурацию
    $config = $core->get('config');
    // Передаем данные Hooks для обработки ожидающим классам
    $hook = new $config['vendor']['hooks']['hook']($config);
    $hook->http($request, 'GET', 'admin');
    $request = $hook->request();

    // Получаем vendor из url
    if ($request->getAttribute('vendor')) {
        $vendor = clean($request->getAttribute('vendor'));
    } else {
        $vendor = null;
    }
    if ($request->getAttribute('package')) {
        $package = clean($request->getAttribute('package'));
    } else {
        $package = null;
    }
    // Получаем параметры из URL
    $getParams = $request->getQueryParams();
    $host = $request->getUri()->getHost();
    $path = $request->getUri()->getPath();
    // Конфигурация роутинга
    $routers = $config['routers'];
    // Конфигурация шаблона
    $template = $core->get('admin_template');
    // Подключаем мультиязычность
    $languages = $core->get('languages');
    $language = $languages->get($request);
    // Подключаем сессию
    $session = $core->get('session');
    // Данные пользователя из сессии
    $sessionUser =(new ModelSessionUser($core))->get();
    // Читаем ключи
    $token_key = $config['key']['token'];
    // Генерируем токен
    $token = random_token();
    // Записываем токен в сессию
    $session->token_admin = $config['vendor']['crypto']['crypt']::encrypt($token, $token_key);
    // Шаблон по умолчанию 404
    $render = $template['layouts']['404'] ? $template['layouts']['404'] : '404.html';
    // Контент по умолчанию
    $content = '';
 
    $post_id = '/_';
    $admin_uri = '/_';
    if(!empty($session->admin_uri)) {
        $admin_uri = '/'.$session->admin_uri;
    }
    if(!empty($session->post_id)) {
        $post_id = '/'.$session->post_id;
    }
 
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
 
    if (isset($session->authorize)) {
        if ($session->role_id == 100) {
            // Подключаем класс
            $packages = new PluginPackages($config);
            if ($request->getMethod() == 'POST') {
                $paramPost = $request->getParsedBody();
                $packages->put($paramPost);
            }
            if (isset($vendor) && isset($package)) {
                // Получаем массив
                $content = $packages->getOne($vendor, $package);
            }
            $render = $template['layouts']['package'] ? $template['layouts']['package'] : 'package.html'; 
        }
    } else {
        $session->authorize = null;
    }
 
    $head = [
        "page" => $render,
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
    
    $view = [
        "head" => $head,
        "routers" => $routers,
        "config" => $config,
        "language" => $language,
        "template" => $template,
        "token" => $session->token_admin,
        "admin_uri" => $admin_uri,
        "post_id" => $post_id,
        "session" => $sessionUser,
        "content" => $content
    ];
 
    // Передаем данные Hooks для обработки ожидающим классам
    $hook->get($render, $view);
    // Запись в лог
    $core->get('logger')->info($hook->logger());
    // Отдаем данные шаблонизатору
    return $response->write($core->get('admin')->render($hook->render(), $hook->view()));
 
});
 
// Изменение статуса пакета
$routing->post($admin_uri.$admin_router.'package-{querys:[a-z0-9_-]+}/{vendor:[a-z0-9_-]+}.{package:[a-z0-9_-]+}', function (Request $request, Response $response, array $args = []) use ($core, $app) {
    
    // Подключаем конфиг Settings\Config
    $config = $core->get('config');
    // Передаем данные Hooks для обработки ожидающим классам
    $hook = new $config['vendor']['hooks']['hook']($config);
    $hook->http($request, 'POST', 'admin');
    $request = $hook->request();

    if ($args['querys']) {
        $querys = clean($args['querys']);
    } else {
        $querys = null;
    }
    if ($args['vendor']) {
        $vendor = clean($args['vendor']);
    } else {
        $vendor = null;
    }
    if ($args['package']) {
        $package = clean($args['package']);
    } else {
        $package = null;
    }
    // Получаем данные отправленные нам через POST
    $post = $request->getParsedBody();
    // Подключаем сессию
    $session = $core->get('session');
    // Читаем ключи
    $token_key = $config['key']['token'];
    
    try {
        // Получаем токен из сессии
        $token = $config['vendor']['crypto']['crypt']::decrypt($session->token_admin, $token_key);
        } catch (\Exception $ex) {
        $token = 0;
        if (isset($session->authorize)) {
            if ($session->authorize != 1 || $session->role_id != 100) {
                // Сообщение об Атаке или подборе токена
                (new ModelSecurity($core))->token($request);
            }
            } else {
            // Сообщение об Атаке или подборе токена
            (new ModelSecurity($core))->token($request);
        }
    }
    try {
        // Получаем токен из POST
        $post_csrf = $config['vendor']['crypto']['crypt']::decrypt(sanitize($post['csrf']), $token_key);
        // Чистим данные на всякий случай пришедшие через POST
        $csrf = clean($post_csrf);
        } catch (\Exception $ex) {
        $csrf = 1;
        if (isset($session->authorize)) {
            if ($session->authorize != 1 || $session->role_id != 100) {
                // Сообщение об Атаке или подборе csrf
                (new ModelSecurity($core))->csrf($request);
            }
            } else {
            // Сообщение об Атаке или подборе csrf
            (new ModelSecurity($core))->csrf($request);
        }
    }
    
    $callbackStatus = 400;
    $callbackTitle = 'Соообщение системы';
    $callbackText = '';
    
    // Проверка токена - Если токен не совпадает то ничего не делаем. Можем записать в лог или написать письмо админу
    if ($csrf == $token) {
        if (isset($session->authorize)) {
            if ($session->authorize == 1 && $session->role_id == 100) {
                if (isset($vendor) && isset($package) && isset($querys)) {
                    // Подключаем класс
                    $packages = new PluginPackages($config);
                    if($querys == 'delete') {
                        $content = $packages->del($vendor, $package);
                    } elseif($querys == 'activate'){
                        $state = '1';
                        $content = $packages->state($vendor, $package, $state);
                    } else {
                        $state = '0';
                        $content = $packages->state($vendor, $package, $state);
                    }
                    
                    if($content == true){
                        $callbackStatus = 200;
                    } else {
                        $callbackText = 'Ошибка !';
                    }
                } else {
                    $callbackText = 'Ошибка !';
                }
            } else {
                $callbackText = 'Вы не администратор';
            }
        } else {
            $callbackText = 'Вы не авторизованы';
        }
    } else {
        $callbackText = 'Обновите страницу';
    }
    
    $callback = ['status' => $callbackStatus, 'title' => $callbackTitle, 'text' => $callbackText];
    // Выводим заголовки
    $response->withStatus(200);
    $response->withHeader('Content-type', 'application/json');


    // Выводим json
    return $response->write(json_encode($hook->callback($callback)));
    
});

// Список пакетов
$routing->get($admin_uri.$admin_router.'packages', function (Request $request, Response $response, array $args = []) use ($core, $app) {
    
    // Получаем конфигурацию
    $config = $core->get('config');
    // Передаем данные Hooks для обработки ожидающим классам
    $hook = new $config['vendor']['hooks']['hook']($config);
    $hook->http($request, 'GET', 'admin');
    $request = $hook->request();

    // Получаем параметры из URL
    $getParams = $request->getQueryParams();
    $host = $request->getUri()->getHost();
    $path = $request->getUri()->getPath();
    // Конфигурация роутинга
    $routers = $config['routers'];
    // Конфигурация шаблона
    $template = $core->get('admin_template');
    // Подключаем мультиязычность
    $languages = $core->get('languages');
    $language = $languages->get($request);
    // Подключаем сессию
    $session = $core->get('session');
    // Данные пользователя из сессии
    $sessionUser =(new ModelSessionUser($core))->get();
    // Читаем ключи
    $token_key = $config['key']['token'];
    // Генерируем токен
    $token = random_token();
    // Записываем токен в сессию
    $session->token_admin = $config['vendor']['crypto']['crypt']::encrypt($token, $token_key);
    // Шаблон по умолчанию 404
    $render = $template['layouts']['404'] ? $template['layouts']['404'] : '404.html';
    // Контент по умолчанию
    $content = '';
    
    $post_id = '/_';
    $admin_uri = '/_';
    if(!empty($session->admin_uri)) {
        $admin_uri = '/'.$session->admin_uri;
    }
    if(!empty($session->post_id)) {
        $post_id = '/'.$session->post_id;
    }
    
    // Заголовки по умолчанию из конфигурации
    $title = $language["709"].' '.$language["814"].' - '.$config['settings']['site']['title'];
    $keywords = $config['settings']['site']['keywords'];
    $description = $config['settings']['site']['description'];
    $robots = $config['settings']['site']['robots'];
    $og_title = $config['settings']['site']['og_title'];
    $og_description = $config['settings']['site']['og_description'];
    $og_image = $config['settings']['site']['og_image'];
    $og_type = $config['settings']['site']['og_type'];
    $og_locale = $config['settings']['site']['og_locale'];
    $og_url = $config['settings']['site']['og_url'];
    
    if (isset($session->authorize)) {
        if ($session->role_id == 100) {
            // Подключаем класс
            $packages = new PluginPackages($config);
            // Получаем массив
            $content = $packages->get();
            $render = $template['layouts']['packages'] ? $template['layouts']['packages'] : 'packages.html';
            
        }
        } else {
        $session->authorize = null;
    }
    
    $head = [
        "page" => $render,
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
    
    $view = [
        "head" => $head,
        "routers" => $routers,
        "config" => $config,
        "language" => $language,
        "template" => $template,
        "token" => $session->token_admin,
        "admin_uri" => $admin_uri,
        "post_id" => $post_id,
        "session" => $sessionUser,
        "content" => $content
    ];
    
    // Передаем данные Hooks для обработки ожидающим классам
    $hook->get($render, $view);
    // Запись в лог
    $core->get('logger')->info($hook->logger());
    // Отдаем данные шаблонизатору
    return $response->write($core->get('admin')->render($hook->render(), $hook->view()));
    
});

// Репозиторий
$routing->get($admin_uri.$admin_router.'packages-install', function (Request $request, Response $response, array $args = []) use ($core, $app) {
    
    // Получаем конфигурацию
    $config = $core->get('config');
    // Передаем данные Hooks для обработки ожидающим классам
    $hook = new $config['vendor']['hooks']['hook']($config);
    $hook->http($request, 'GET', 'admin');
    $request = $hook->request();

    // Получаем параметры из URL
    $getParams = $request->getQueryParams();
    $host = $request->getUri()->getHost();
    $path = $request->getUri()->getPath();
    // Конфигурация роутинга
    $routers = $config['routers'];
    // Конфигурация шаблона
    $template = $core->get('admin_template');
    // Подключаем мультиязычность
    $languages = $core->get('languages');
    $language = $languages->get($request);
    // Подключаем сессию
    $session = $core->get('session');
    // Данные пользователя из сессии
    $sessionUser =(new ModelSessionUser($core))->get();
    // Читаем ключи
    $token_key = $config['key']['token'];
    // Генерируем токен
    $token = random_token();
    // Записываем токен в сессию
    $session->token_admin = $config['vendor']['crypto']['crypt']::encrypt($token, $token_key);
    // Шаблон по умолчанию 404
    $render = $template['layouts']['404'] ? $template['layouts']['404'] : '404.html';
    // Контент по умолчанию
    $content = '';
    
    $post_id = '/_';
    $admin_uri = '/_';
    if(!empty($session->admin_uri)) {
        $admin_uri = '/'.$session->admin_uri;
    }
    if(!empty($session->post_id)) {
        $post_id = '/'.$session->post_id;
    }
    
    // Заголовки по умолчанию из конфигурации
    $title = $language["709"].' '.$language["814"].' - '.$config['settings']['site']['title'];
    $keywords = $config['settings']['site']['keywords'];
    $description = $config['settings']['site']['description'];
    $robots = $config['settings']['site']['robots'];
    $og_title = $config['settings']['site']['og_title'];
    $og_description = $config['settings']['site']['og_description'];
    $og_image = $config['settings']['site']['og_image'];
    $og_type = $config['settings']['site']['og_type'];
    $og_locale = $config['settings']['site']['og_locale'];
    $og_url = $config['settings']['site']['og_url'];
    
    if (isset($session->authorize)) {
        if ($session->role_id == 100) {
            // Подключаем класс
            $packages = new PluginPackages($config);
            // Получаем массив
            $content = $packages->get();
            $render = $template['layouts']['packages'] ? $template['layouts']['packages'] : 'packages.html';
            
        }
        } else {
        $session->authorize = null;
    }
    
    $head = [
    "page" => $render,
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
    
    $view = [
    "head" => $head,
    "routers" => $routers,
    "config" => $config,
    "language" => $language,
    "template" => $template,
    "token" => $session->token_admin,
    "admin_uri" => $admin_uri,
    "post_id" => $post_id,
    "session" => $sessionUser,
    "content" => $content
    ];
    
    // Передаем данные Hooks для обработки ожидающим классам
    $hook->get($render, $view);
    // Запись в лог
    $core->get('logger')->info($hook->logger());
    // Отдаем данные шаблонизатору
    return $response->write($core->get('admin')->render($hook->render(), $hook->view()));
    
});

// Страница установки из json файла
$routing->get($admin_uri.$admin_router.'packages-install-json', function (Request $request, Response $response, array $args = []) use ($core, $app) {
    
    // Получаем конфигурацию
    $config = $core->get('config');
    // Передаем данные Hooks для обработки ожидающим классам
    $hook = new $config['vendor']['hooks']['hook']($config);
    $hook->http($request, 'GET', 'admin');
    $request = $hook->request();

    // Получаем параметры из URL
    $getParams = $request->getQueryParams();
    $host = $request->getUri()->getHost();
    $path = $request->getUri()->getPath();
    // Конфигурация роутинга
    $routers = $config['routers'];
    // Конфигурация шаблона
    $template = $core->get('admin_template');
    // Подключаем мультиязычность
    $languages = $core->get('languages');
    $language = $languages->get($request);
    // Подключаем сессию
    $session = $core->get('session');
    // Данные пользователя из сессии
    $sessionUser =(new ModelSessionUser($core))->get();
    // Читаем ключи
    $token_key = $config['key']['token'];
    // Генерируем токен
    $token = random_token();
    // Записываем токен в сессию
    $session->token_admin = $config['vendor']['crypto']['crypt']::encrypt($token, $token_key);
    // Шаблон по умолчанию 404
    $render = $template['layouts']['404'] ? $template['layouts']['404'] : '404.html';
    // Контент по умолчанию
    $content = '';
    
    $post_id = '/_';
    $admin_uri = '/_';
    if(!empty($session->admin_uri)) {
        $admin_uri = '/'.$session->admin_uri;
    }
    if(!empty($session->post_id)) {
        $post_id = '/'.$session->post_id;
    }
    
    // Заголовки по умолчанию из конфигурации
    $title = $language["709"].' '.$language["814"].' - '.$config['settings']['site']['title'];
    $keywords = $config['settings']['site']['keywords'];
    $description = $config['settings']['site']['description'];
    $robots = $config['settings']['site']['robots'];
    $og_title = $config['settings']['site']['og_title'];
    $og_description = $config['settings']['site']['og_description'];
    $og_image = $config['settings']['site']['og_image'];
    $og_type = $config['settings']['site']['og_type'];
    $og_locale = $config['settings']['site']['og_locale'];
    $og_url = $config['settings']['site']['og_url'];
    
    if (isset($session->authorize)) {
        if ($session->role_id == 100) {
            // Подключаем класс
            $packages = new PluginPackages($config);
            // Получаем массив
            $content = $packages->get();
            $render = $template['layouts']['packages'] ? $template['layouts']['packages'] : 'packages.html';
            
        }
        } else {
        $session->authorize = null;
    }
    
    $head = [
    "page" => $render,
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
    
    $view = [
    "head" => $head,
    "routers" => $routers,
    "config" => $config,
    "language" => $language,
    "template" => $template,
    "token" => $session->token_admin,
    "admin_uri" => $admin_uri,
    "post_id" => $post_id,
    "session" => $sessionUser,
    "content" => $content
    ];
    
    // Передаем данные Hooks для обработки ожидающим классам
    $hook->get($render, $view);
    // Запись в лог
    $core->get('logger')->info($hook->logger());
    // Отдаем данные шаблонизатору
    return $response->write($core->get('admin')->render($hook->render(), $hook->view()));
    
});

// Глобальные настройки
$routing->get($admin_uri.$admin_router.'config', function (Request $request, Response $response, array $args = []) use ($core, $app) {
    
    // Получаем конфигурацию
    $config = $core->get('config');
    // Передаем данные Hooks для обработки ожидающим классам
    $hook = new $config['vendor']['hooks']['hook']($config);
    $hook->http($request, 'GET', 'admin');
    $request = $hook->request();

    // Получаем параметры из URL
    $getParams = $request->getQueryParams();
    $host = $request->getUri()->getHost();
    $path = $request->getUri()->getPath();
    // Конфигурация роутинга
    $routers = $config['routers'];
    // Конфигурация шаблона
    $template = $core->get('admin_template');
    // Подключаем мультиязычность
    $languages = $core->get('languages');
    $language = $languages->get($request);
    // Подключаем сессию
    $session = $core->get('session');
    // Данные пользователя из сессии
    $sessionUser =(new ModelSessionUser($core))->get();
    // Читаем ключи
    $token_key = $config['key']['token'];
    // Генерируем токен
    $token = random_token();
    // Записываем токен в сессию
    $session->token_admin = $config['vendor']['crypto']['crypt']::encrypt($token, $token_key);
    // Шаблон по умолчанию 404
    $render = $template['layouts']['404'] ? $template['layouts']['404'] : '404.html';
    // Контент по умолчанию
    $content = '';
    
    $post_id = '/_';
    $admin_uri = '/_';
    if(!empty($session->admin_uri)) {
        $admin_uri = '/'.$session->admin_uri;
    }
    if(!empty($session->post_id)) {
        $post_id = '/'.$session->post_id;
    }
    
    // Заголовки по умолчанию из конфигурации
    $title = $language["709"].' '.$language["814"].' - '.$config['settings']['site']['title'];
    $keywords = $config['settings']['site']['keywords'];
    $description = $config['settings']['site']['description'];
    $robots = $config['settings']['site']['robots'];
    $og_title = $config['settings']['site']['og_title'];
    $og_description = $config['settings']['site']['og_description'];
    $og_image = $config['settings']['site']['og_image'];
    $og_type = $config['settings']['site']['og_type'];
    $og_locale = $config['settings']['site']['og_locale'];
    $og_url = $config['settings']['site']['og_url'];
    
    if (isset($session->authorize)) {
        if ($session->role_id == 100) {
            // Подключаем класс
            $settings = new PluginConfig($config);
            // Получаем массив с настройками шаблона
            $content = $settings->get();
            $render = $template['layouts']['config'] ? $template['layouts']['config'] : 'config.html';
        }
        } else {
        $session->authorize = null;
    }
    
    $head = [
        "page" => $render,
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
    
    $view = [
        "head" => $head,
        "routers" => $routers,
        "config" => $config,
        "language" => $language,
        "template" => $template,
        "token" => $session->token_admin,
        "admin_uri" => $admin_uri,
        "post_id" => $post_id,
        "session" => $sessionUser,
        "content" => $content,
        "type" => "edit",
        "editor" => $config['admin']['editor']
    ];
    
    // Передаем данные Hooks для обработки ожидающим классам
    $hook->get($render, $view);
    // Запись в лог
    $core->get('logger')->info($hook->logger());
    // Отдаем данные шаблонизатору
    return $response->write($core->get('admin')->render($hook->render(), $hook->view()));
    
});

// Редактируем глобальные настройки
$routing->post($admin_uri.$admin_router.'config', function (Request $request, Response $response, array $args = []) use ($core, $app) {
    
    // Получаем конфигурацию
    $config = $core->get('config');
    // Передаем данные Hooks для обработки ожидающим классам
    $hook = new $config['vendor']['hooks']['hook']($config);
    $hook->http($request, 'POST', 'admin');
    $request = $hook->request();

    // Получаем параметры из URL
    $getParams = $request->getQueryParams();
    $host = $request->getUri()->getHost();
    $path = $request->getUri()->getPath();
    // Конфигурация роутинга
    $routers = $config['routers'];
    // Конфигурация шаблона
    $template = $core->get('admin_template');
    // Подключаем мультиязычность
    $languages = $core->get('languages');
    $language = $languages->get($request);
    // Подключаем сессию
    $session = $core->get('session');
    // Данные пользователя из сессии
    $sessionUser =(new ModelSessionUser($core))->get();
    // Читаем ключи
    $token_key = $config['key']['token'];
    // Генерируем токен
    $token = random_token();
    // Записываем токен в сессию
    $session->token_admin = $config['vendor']['crypto']['crypt']::encrypt($token, $token_key);
    // Шаблон по умолчанию 404
    $render = $template['layouts']['404'] ? $template['layouts']['404'] : '404.html';
    // Контент по умолчанию
    $content = '';
    
    $post_id = '/_';
    $admin_uri = '/_';
    if(!empty($session->admin_uri)) {
        $admin_uri = '/'.$session->admin_uri;
    }
    if(!empty($session->post_id)) {
        $post_id = '/'.$session->post_id;
    }
    
    // Заголовки по умолчанию из конфигурации
    $title = $language["709"].' '.$language["814"].' - '.$config['settings']['site']['title'];
    $keywords = $config['settings']['site']['keywords'];
    $description = $config['settings']['site']['description'];
    $robots = $config['settings']['site']['robots'];
    $og_title = $config['settings']['site']['og_title'];
    $og_description = $config['settings']['site']['og_description'];
    $og_image = $config['settings']['site']['og_image'];
    $og_type = $config['settings']['site']['og_type'];
    $og_locale = $config['settings']['site']['og_locale'];
    $og_url = $config['settings']['site']['og_url'];
    
    if (isset($session->authorize)) {
        if ($session->role_id == 100) {
            // Подключаем класс
            $settings = new PluginConfig($config);
            // Массив из POST
            $paramPost = $request->getParsedBody();
            // Сохраняем в файл
            $settings->put($paramPost);
            // Получаем обновленные данные
            $content = $settings->get();
            
            $render = $template['layouts']['config'] ? $template['layouts']['config'] : 'config.html';
        }
        } else {
        $session->authorize = null;
    }
    
    $head = [
        "page" => $render,
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
    
    $view = [
        "head" => $head,
        "routers" => $routers,
        "config" => $config,
        "language" => $language,
        "template" => $template,
        "token" => $session->token_admin,
        "admin_uri" => $admin_uri,
        "post_id" => $post_id,
        "session" => $sessionUser,
        "content" => $content,
        "type" => "edit",
        "editor" => $config['admin']['editor']
    ];
    
    // Передаем данные Hooks для обработки ожидающим классам
    $hook->get($render, $view);
    // Запись в лог
    $core->get('logger')->info($hook->logger());
    // Отдаем данные шаблонизатору
    return $response->write($core->get('admin')->render($hook->render(), $hook->view()));
    
});

// Список баз данных
$routing->get($admin_uri.$admin_router.'db', function (Request $request, Response $response, array $args = []) use ($core, $app) {
    
    // Получаем конфигурацию
    $config = $core->get('config');
    // Передаем данные Hooks для обработки ожидающим классам
    $hook = new $config['vendor']['hooks']['hook']($config);
    $hook->http($request, 'GET', 'admin');
    $request = $hook->request();

    // Получаем параметры из URL
    $getParams = $request->getQueryParams();
    $host = $request->getUri()->getHost();
    $path = $request->getUri()->getPath();
    // Конфигурация роутинга
    $routers = $config['routers'];
    // Конфигурация шаблона
    $template = $core->get('admin_template');
    // Подключаем мультиязычность
    $languages = $core->get('languages');
    $language = $languages->get($request);
    // Подключаем сессию
    $session = $core->get('session');
    // Данные пользователя из сессии
    $sessionUser =(new ModelSessionUser($core))->get();
    // Читаем ключи
    $token_key = $config['key']['token'];
    // Генерируем токен
    $token = random_token();
    // Записываем токен в сессию
    $session->token_admin = $config['vendor']['crypto']['crypt']::encrypt($token, $token_key);
    // Шаблон по умолчанию 404
    $render = $template['layouts']['404'] ? $template['layouts']['404'] : '404.html';
    // Контент по умолчанию
    $content = '';
    
    $post_id = '/_';
    $admin_uri = '/_';
    if(!empty($session->admin_uri)) {
        $admin_uri = '/'.$session->admin_uri;
    }
    if(!empty($session->post_id)) {
        $post_id = '/'.$session->post_id;
    }
    
    // Заголовки по умолчанию из конфигурации
    $title = $language["814"].' - '.$config['settings']['site']['title'];
    $keywords = $config['settings']['site']['keywords'];
    $description = $config['settings']['site']['description'];
    $robots = $config['settings']['site']['robots'];
    $og_title = $config['settings']['site']['og_title'];
    $og_description = $config['settings']['site']['og_description'];
    $og_image = $config['settings']['site']['og_image'];
    $og_type = $config['settings']['site']['og_type'];
    $og_locale = $config['settings']['site']['og_locale'];
    $og_url = $config['settings']['site']['og_url'];
    
    if (isset($session->authorize)) {
        if ($session->role_id) {
            $adminDatabase = new PluginDatabase($config);
            $content = $adminDatabase->list();
            $render = $template['layouts']['db'] ? $template['layouts']['db'] : 'db.html';
        }
        } else {
        $session->authorize = null;
    }
    
    $head = [
    "page" => $render,
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
    
    $view = [
    "head" => $head,
    "routers" => $routers,
    "config" => $config,
    "language" => $language,
    "template" => $template,
    "token" => $session->token_admin,
    "admin_uri" => $admin_uri,
    "post_id" => $post_id,
    "session" => $sessionUser,
    "content" => $content
    ];
    
    // Передаем данные Hooks для обработки ожидающим классам
    $hook->get($render, $view);
    // Запись в лог
    $core->get('logger')->info($hook->logger());
    // Отдаем данные шаблонизатору
    return $response->write($core->get('admin')->render($hook->render(), $hook->view()));
    
});

// Страница таблицы (ресурса)
$routing->get($admin_uri.$admin_router.'db/{resource:[a-z0-9_-]+}[/{id:[0-9_]+}]', function (Request $request, Response $response, array $args = []) use ($core, $app) {
    
    // Получаем конфигурацию
    $config = $core->get('config');
    // Передаем данные Hooks для обработки ожидающим классам
    $hook = new $config['vendor']['hooks']['hook']($config);
    $hook->http($request, 'GET', 'admin');
    $request = $hook->request();

    // Получаем resource из url
    if ($request->getAttribute('resource')) {
        $resource = clean($request->getAttribute('resource'));
        } else {
        $resource = null;
    }
    // Получаем параметры из URL
    $getParams = $request->getQueryParams();
    $host = $request->getUri()->getHost();
    $path = $request->getUri()->getPath();
    // Конфигурация роутинга
    $routers = $config['routers'];
    // Конфигурация шаблона
    $template = $core->get('admin_template');
    // Подключаем мультиязычность
    $languages = $core->get('languages');
    $language = $languages->get($request);
    // Подключаем сессию
    $session = $core->get('session');
    // Данные пользователя из сессии
    $sessionUser =(new ModelSessionUser($core))->get();
    // Читаем ключи
    $token_key = $config['key']['token'];
    // Генерируем токен
    $token = random_token();
    // Записываем токен в сессию
    $session->token_admin = $config['vendor']['crypto']['crypt']::encrypt($token, $token_key);
    // Шаблон по умолчанию 404
    $render = $template['layouts']['404'] ? $template['layouts']['404'] : '404.html';
    // Контент по умолчанию
    $content = '';
    
    $post_id = '/_';
    $admin_uri = '/_';
    if(!empty($session->admin_uri)) {
        $admin_uri = '/'.$session->admin_uri;
    }
    if(!empty($session->post_id)) {
        $post_id = '/'.$session->post_id;
    }
    
    // Заголовки по умолчанию из конфигурации
    $title = $language["814"].' - '.$config['settings']['site']['title'];
    $keywords = $config['settings']['site']['keywords'];
    $description = $config['settings']['site']['description'];
    $robots = $config['settings']['site']['robots'];
    $og_title = $config['settings']['site']['og_title'];
    $og_description = $config['settings']['site']['og_description'];
    $og_image = $config['settings']['site']['og_image'];
    $og_type = $config['settings']['site']['og_type'];
    $og_locale = $config['settings']['site']['og_locale'];
    $og_url = $config['settings']['site']['og_url'];
    
    if (isset($id)) {
        $render = $template['layouts']['db_id'] ? $template['layouts']['db_id'] : 'db_id.html';
        } else {
        $render = $template['layouts']['db_item'] ? $template['layouts']['db_item'] : 'db_item.html';
    }
    
    $name_db = null;
    
    if (isset($session->authorize) && isset($resource)) {
        if ($session->role_id) {
            
            // Получаем массив параметров uri
            $queryParams = $request->getQueryParams();
            $arr = [];
            $arr['state'] = 1;
            $arr['offset'] = 0;
            $arr['limit'] = 30;
            $arr['order'] = "ASC";
            if (count($queryParams) >= 1) {
                foreach($queryParams as $key => $value)
                {
                    if (isset($key) && isset($value)) {
                        $arr[$key] = clean($value);
                    }
                }
            }
            
            // Собираем полученные параметры в url и отдаем шаблону
            $get_array = http_build_query($arr);
            // Вытягиваем URL_PATH для правильного формирования юрл
            //$url_path = parse_url($request->getUri(), PHP_URL_PATH);
            $url_path = $path;
            // Подключаем сортировки
            $filter = new PluginFilter($url_path, $arr);
            $orderArray = $filter->order();
            $limitArray = $filter->limit();
            // Формируем массив по которому будем сортировать
            
            $resourceDd = $adminDatabase->getOne($resource);
            
            $arrs["id"] = "id";
            $resourceArray = $arrs + $resourceDd; 
            
            $content_key = array_keys($resourceArray);
            
            //print_r($content_key);
            
            foreach($resourceArray as $key => $value)
            {
                $sortArr[$key] = $key;
            }
            
            $sortArray = $filter->sort($sortArr);
            
            $routerDb = new RouterDb($config);
            $_table = $resource;
            $_database = $routerDb->ping($_table);
            $resourceConfig = $config['db']['resource'][$_database] ?? null;
            $_driver = $resourceConfig['driver'] ?? null;
            $_adapter = $resourceConfig['adapter'] ?? null;
            $_format = $resourceConfig['format'] ?? null;
            $routerDb->setConfig([], $_driver, $_adapter, $_format);
            $db = $routerDb->run($_database);
            // Отправляем запрос и получаем данные
            $resp = $db->get($resource);
            
            $count = 0;
            if (isset($resp["response"]['total'])) {
                $count = $resp["response"]['total'];
            }
            $paginator = $filter->paginator($count);
            // Если ответ не пустой
            if (count($resp["body"]['items']) >= 1) {
                $content = '';
                // Отдаем пагинатору колличество
                foreach($resp["body"]['items'] as $item)
                {
                    foreach($item["item"] as $key => $value)
                    {
                        if ($value == ''){$value = "--";}
                        $contentArr[$key] = clean($value);
                    }
                    $content["items"][] = $contentArr;
                }
                } else {
                $content = null;
            }
            } else {
            $render = "404";
        }
        } else {
        $session->authorize = null;
        $render = "404";
    }
    
    $head = [
        "page" => $render,
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
    
    $view = [
        "head" => $head,
        "routers" => $routers,
        "config" => $config,
        "language" => $language,
        "template" => $template,
        "token" => $session->token_admin,
        "admin_uri" => $admin_uri,
        "post_id" => $post_id,
        "session" => $sessionUser,
        "content" => $content,
        "content_key" => $content_key,
        "paginator" => $paginator,
        "order" => $orderArray,
        "sort" => $sortArray,
        "limit" => $limitArray,
        "param" => $arr,
        "total" => $count,
        "url_param" => $get_array,
        "url" => $url_path
    ];
    
    // Передаем данные Hooks для обработки ожидающим классам
    $hook->get($render, $view);
    // Запись в лог
    $core->get('logger')->info($hook->logger());
    // Отдаем данные шаблонизатору
    return $response->write($core->get('admin')->render($hook->render(), $hook->view()));
    
});

// Глобально
$routing->get($admin_uri.$admin_router.'_{resource:[a-z0-9_-]+}[/{id:[a-z0-9_]+}]', function (Request $request, Response $response, array $args = []) use ($core, $app) {
    
    // Получаем конфигурацию
    $config = $core->get('config');
    // Передаем данные Hooks для обработки ожидающим классам
    $hook = new $config['vendor']['hooks']['hook']($config);
    $hook->http($request, 'GET', 'admin');
    $request = $hook->request();

    // Получаем resource из url
    if ($request->getAttribute('resource')) {
        $resource = clean($request->getAttribute('resource'));
        } else {
        $resource = null;
    }
    // Получаем id из url
    if ($request->getAttribute('id')) {
        $id = clean($request->getAttribute('id'));
        } else {
        $id = null;
    }
    // Получаем параметры из URL
    $getParams = $request->getQueryParams();
    $host = $request->getUri()->getHost();
    $path = $request->getUri()->getPath();
    // Конфигурация роутинга
    $routers = $config['routers'];
    // Конфигурация шаблона
    $template = $core->get('admin_template');
    // Подключаем мультиязычность
    $languages = $core->get('languages');
    $language = $languages->get($request);
    // Подключаем сессию
    $session = $core->get('session');
    // Данные пользователя из сессии
    $sessionUser =(new ModelSessionUser($core))->get();
    // Читаем ключи
    $token_key = $config['key']['token'];
    // Генерируем токен
    $token = random_token();
    // Записываем токен в сессию
    $session->token_admin = $config['vendor']['crypto']['crypt']::encrypt($token, $token_key);
    // Шаблон по умолчанию 404
    $render = $template['layouts']['404'] ? $template['layouts']['404'] : '404.html';
    // Контент по умолчанию
    $content = '';
    
    $post_id = '/_';
    $admin_uri = '/_';
    if(!empty($session->admin_uri)) {
        $admin_uri = '/'.$session->admin_uri;
    }
    if(!empty($session->post_id)) {
        $post_id = '/'.$session->post_id;
    }
    
    // Заголовки по умолчанию из конфигурации
    $title = $language["814"].' - '.$config['settings']['site']['title'];
    $keywords = $config['settings']['site']['keywords'];
    $description = $config['settings']['site']['description'];
    $robots = $config['settings']['site']['robots'];
    $og_title = $config['settings']['site']['og_title'];
    $og_description = $config['settings']['site']['og_description'];
    $og_image = $config['settings']['site']['og_image'];
    $og_type = $config['settings']['site']['og_type'];
    $og_locale = $config['settings']['site']['og_locale'];
    $og_url = $config['settings']['site']['og_url'];
    
    $control = new PluginResources($config);
    $test = $control->test_query($resource);
    if ($test === true) {
        
        $site = new ModelSite($config);
        $site_config = $site->get();
        $site_template = $site->template();
        
        $param = $request->getQueryParams();
        
        if (isset($session->authorize)) {
            if ($session->role_id == 100) {
                
                $render = $template['layouts'][$resource] ? $template['layouts'][$resource] : $resource.'.html';
                
                if(stristr($resource, '_') === FALSE) {
                    $resourceName = "\\ApiShop\\Admin\\".ucfirst($resource);
                    } else {
                    $resourceNew = (str_replace(" ", "", ucwords(str_replace("_", " ", $resource))));
                    $resourceName = "\\ApiShop\\Admin\\".$resourceNew;
                }
                // Подключаем класс
                $resourceClass = new $resourceName($site_template);
                // Отправляем запрос
                $get = $resourceClass->get($resource, $param, $id);
                
                if ($resource == "settings") {
                    $content = $get;
                    } else {
                    $content = $get["body"]["items"];
                }
            }
            } else {
            $session->authorize = null;
        }
    }
    
    $head = [
    "page" => $render,
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
    
    $view = [
    "head" => $head,
    "routers" => $routers,
    "config" => $config,
    "language" => $language,
    "template" => $template,
    "token" => $session->token_admin,
    "admin_uri" => $admin_uri,
    "post_id" => $post_id,
    "session" => $sessionUser,
    "content" => $content
    ];
    
    // Передаем данные Hooks для обработки ожидающим классам
    $hook->get($render, $view);
    // Запись в лог
    $core->get('logger')->info($hook->logger());
    // Отдаем данные шаблонизатору
    return $response->write($core->get('admin')->render($hook->render(), $hook->view()));
    
});
 