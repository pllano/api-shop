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
use Psr\Http\Message\{ServerRequestInterface as Request, ResponseInterface as Response};

// Register App
$app = $core->get('config')['vendor']['controllers']['controller'];
// For POST requests, we first generate the url post_id
// If the user does not have a session, he can not send POST requests
$post_id = '/_';
if(isset($session->post_id)){
    $post_id = '/'.$session->post_id;
}

// GET - Главная
$routing->get($router['index']['route'], function (Request $request, Response $response, array $args = []) use ($core, $app) {
    return (new $app($core, 'index'))->get($request, $response, $args);
});

// GET - Статьи
$routing->get($router['article']['route'], function (Request $request, Response $response, array $args = []) use ($core, $app) {
    return (new $app($core, 'article'))->get($request, $response, $args);
});

// GET - Категории статей
$routing->get($router['article_category']['route'], function (Request $request, Response $response, array $args = []) use ($core, $app) {
    return (new $app($core, 'article_category'))->get($request, $response, $args);
});

// GET - Категории товаров
$routing->get($router['category']['route'], function (Request $request, Response $response, array $args = []) use ($core, $app) {
    return (new $app($core, 'category'))->get($request, $response, $args);
});

// GET - Страница товара
$routing->get($router['product']['route'], function (Request $request, Response $response, array $args = []) use ($core, $app) {
    return (new $app($core, 'product'))->get($request, $response, $args);
});

// GET - Страница товара quick_view
$routing->get($router['quick_view']['route'], function (Request $request, Response $response, array $args = []) use ($core, $app) {
    return (new $app($core, 'quick_view'))->get($request, $response, $args);
});

// GET - Получить локализацию 
$routing->map(['GET', 'POST'], $post_id.$router['language']['route'], function (Request $request, Response $response, array $args = []) use ($core, $app) {
    $app = $this->get('config')['routers']['site']['language']['controller'];
    $function = strtolower($request->getMethod());
    return (new $app($core, 'language'))->$function($request, $response, $args);
});

// POST - Авторизоваться
$routing->post($post_id.$router['login']['route'], function (Request $request, Response $response, array $args = []) use ($core, $app) {
    return (new $app($core, 'login'))->post($request, $response, $args);
});

// GET - Страница авторизации. Войти в систему
$routing->get($router['sign_in']['route'], function (Request $request, Response $response, array $args = []) use ($core, $app) {
    $function = strtolower($request->getMethod());
    return (new $app($core, 'sign_in'))->get($request, $response, $args);
});

// GET - Страница авторизации. Войти в систему
$routing->get($router['sign_up']['route'], function (Request $request, Response $response, array $args = []) use ($core, $app) {
    $function = strtolower($request->getMethod());
    return (new $app($core, 'sign_up'))->get($request, $response, $args);
});

// GET - запросы к корзине
$routing->post($post_id.$router['cart']['route'].'{function:[a-z0-9_-]+}', function (Request $request, Response $response, array $args = []) use ($core, $app) {
    $cart = $this->get('config')['routers']['site']['cart']['controller'];
	$error = $this->get('config')['routers']['site']['error']['controller'];
	$function = strtolower($request->getMethod());
    if ($request->getAttribute('function')) {
        $method = 'post_'.str_replace("-", "_", $request->getAttribute('function'));
        if (method_exists($cart, $method)) {
            $function = $method;
			$app = $cart;
        } else {
		    $app = $error;
        }
    } else {
        $app = $error;
    }
    return (new $app($core, 'cart'))->$function($request, $response, $args);
});

// GET - запросы к корзине
$routing->get($router['cart']['route'].'{function:[a-z0-9_-]+}', function (Request $request, Response $response, array $args = []) use ($core, $app) {
    $cart = $this->get('config')['routers']['site']['cart']['controller'];
	$error = $this->get('config')['routers']['site']['error']['controller'];
	$function = strtolower($request->getMethod());
    if ($request->getAttribute('function')) {
        $method = 'get_'.str_replace("-", "_", $request->getAttribute('function'));
        if (method_exists($cart, $method)) {
            $function = $method;
			$app = $cart;
        } else {
		    $app = $error;
        }
    } else {
        $app = $error;
    }
    return (new $app($core, 'cart'))->$function($request, $response, $args);
});

// POST - Зарегистрироваться
$routing->post($post_id.$router['check_in']['route'], function (Request $request, Response $response, array $args = []) use ($core, $app) {
    $function = strtolower($request->getMethod());
    return (new $app($core, 'check_in'))->post($request, $response, $args);
});

// POST - Выйти
$routing->post($post_id.$router['logout']['route'], function (Request $request, Response $response, array $args = []) use ($core, $app) {
    $function = strtolower($request->getMethod());
    return (new $app($core, 'logout'))->post($request, $response, $args);
});

/*
// Для тех кому нужны url кирилицей
$routing->get('/' . rawurlencode('новости'), function (Request $request, Response $response, array $args = []) use ($container, $app) {
    // it works
    return (new $app('новости', $container))->get($request, $response, $args);
});
*/

// Если решить эту задачку получим крутое подключение роутринга
// Сейчас подключает но только один роутер
// Думаю стоит взять юрл и по регулярке сравнить с конфигом если совпало выполнить
// Возможно просто не вижу очевидного :) буду очень благодарен за дельные советы
/* 
foreach ($router as $key => $val)
{
    if($key == 'index' || $key == '_article') {
 
        $rep = $container->get('settings');
        $rep->replace(['keys' => $key]);
        
        $return = '';
 
        $routing->get($val['route'], function (Request $request, Response $response, array $args = []) {
 
            print("<br>{$this->settings['keys']}<br>");
 
            $router = $this->config['routers']['site'][$this->settings['keys']];
            
           $controller = $router['controller'];
 
            print("<br>{$router['controller']}<br>");
 
            $function = strtolower($request->getMethod());
            $class = new $controller($request->getMethod(), $key, $this->get('config'), $this->get('package'), $this->get('view'), $this->get('logger'));
            
            $return = $class->$function($request, $response, $args);
 
            return $return;
 
        });
    }
}
*/
 