# «API Shop» — E-Commerce Platform
Написан на PHP. Поддерживает самую последнюю версию PHP 7.0.*. Распространяется по лицензии [MIT](https://opensource.org/licenses/MIT).
## `{API}$hop` — это конструктор с огромной гибкостью
`{API}$hop` — имеет свой установщик [install.php](https://github.com/pllano/api-shop/blob/master/install.php), админ панель, систему управления шаблонами, пакетами и все остальное чтобы быстро создать интернет-магазин или сайт. Потдерживает значительную часть стандартов [PSR](https://www.php-fig.org/) в том числе [PSR-7](https://www.php-fig.org/psr/psr-7/). Использует самую новую версию [`Micro Framework Slim 4.x`](https://www.slimframework.com/) который отвечает за то, что у него получается лучше всего - `Роутинг`. Есть возможность использовать компоненты `Slim`, `Symfony`, `Laravel` и любого другого фреймворка. Вы можете менять классы обработки на свои или на любые другие.
## Отличная скорость работы без дополнительных оптимизаций
`{API}$hop` — способен выдерживать огромную посещаемость и подойдет для высоконагруженных проектов. Если вы запустите обычный интернет-магазин или Landing Page - вы получите сайт который открывается со скоростью мысли. 
- Демо - [xti.com.ua](https://xti.com.ua/)
- Конфигурация Демо сайта: [Slim Framework 4.x](https://github.com/slimphp/Slim/tree/4.x) + FastCGI (Nginx + PHP-FPM) + PHP 7.2.2 + Кэш Predis + Кэш Twig
- Тест скорости - https://developers.google.com/speed/pagespeed/insights/?url=https://inbox.com.ua
## Низкий порог входа для программистов
Самым главным преимуществом API Shop является низкий порог входа для разработчиков. Мы стараемся писать максимально просто ! За несколько дней с платформой разберется даже начинающий программист. Не важно какой шаблонизатор, кеширование или базу данных хотите использовать - `{API}$hop` из коробки поддерживает все самые популярные базы данных, кеширование и шаблонизаторы. Вы можете использовать заготовки для пакетов и расширений, чтобы написать свой обработчик для чего угодно и заменить им стандартный. Установить и подключить свой пакет очень легко.
### [Документация по установке](https://github.com/pllano/api-shop/blob/master/INSTALL.md)
### [Конфигурация](https://github.com/pllano/api-shop/blob/master/app/README.md)
### [Структура баз данных](https://github.com/pllano/structure-db)
### [Заготовки для расширений](https://github.com/pllano/skeleton-extensions)
#### Мы ищем единомышленников ! Присоединяйтесь.
## API Shop — из коробки будет поддерживать:
- Автозагрузчики и управление пакетами: [`AutoRequire`](https://github.com/pllano/auto-require) - Автозагрузка по стандартам PSR-0 и PSR-4.
- Подключение классов через [Hooks](https://github.com/pllano/hooks) без внесения изменений в код API Shop
- Шаблонизаторы через [TemplateEngine](https://github.com/pllano/template-engine): - [`Twig`](https://github.com/twigphp/Twig) [`PhpRenderer`](https://github.com/slimphp/PHP-View) [`Smarty`](https://github.com/smarty-php/smarty) [`Dwoo`](https://github.com/dwoo-project/dwoo) [`Fenom`](https://github.com/fenom-template/fenom)  [`Mustache`](https://github.com/bobthecow/mustache.php) [`Blade`](https://github.com/PhiloNL/Laravel-Blade) - Замена шаблонизатора при установке или активации шаблонов.
- Кеширование через [Cache](https://github.com/pllano/cache): `Memcached`, `Memcache`, `Redis`, `Predis`, `Filesystem`, `JsonCache`, `MongoDB`, `Elasticsearch`, `Array`, `Apcu`, `Apc` + `illuminate`, `Doctrine` - Горячая замена системы кеширования
- Управление данными - [routerDb](https://github.com/pllano/router-db) - Один интерфейс для работы со всеми базами данных
- Хранение данных: `RESTful API`, [`JsonDB`](https://github.com/pllano/json-db), `MySQL`, `PostgreSQL`, `MongoDB`, `SQLite`, `MariaDB`, `Redis`, `Elasticsearch` - Одновременная работа с любым количество баз данных. У каждой таблицы может быть своя база.
- HTTP клиенты: `Guzzle`, `Buzz`, `Httplug`, `Httpful`, `Requests`, `Yii2 Httpclient`, `Unirest PHP`
- [Обработчики изображений](https://github.com/pllano/router-image): `Imagine`, `Intervention`, `Spatie`, `ImageOptimizer`
- и многое другое ...
## Требования к хостингу
### Для работы API Shop необходим хостинг, который поддерживает:
- PHP версии от 7.0.25 до 7.2.2
- Протокол HTTPS
- Расширение PHP: `openssl` `zip`
- Для кеширования необходимо установить требуемое хранилище кеша (по умолчанию используется файловое кеширование) и расширение PHP: `memcache` `memcached` или другое.
### Настройки `php.ini`
- `max_execution_time` = 120 или 240 (по умолчанию 30)
- `memory_limit` = 512 или 1024 (по умолчанию 128)

Эти параметры нужны для установки API Shop и загрузки пакетов с помощью 
[install.php](https://github.com/pllano/api-shop/blob/master/install.php). 
При работе он менее требователен.

![](https://github.com/pllano/api-shop/blob/master/themes/templates/mini-mo/img/logo.png)

<a name="feedback"></a>
## Поддержка, обратная связь, новости

Общайтесь с нами через почту open.source@avantis.com.ua

Если вы нашли баг в работе API Shop загляните в
[issues](https://github.com/pllano/api-shop/issues), возможно, про него мы уже знаем и
чиним. Если нет, лучше всего сообщить о нём там. Там же вы можете оставлять свои
пожелания и предложения.

За новостями вы можете следить по
[коммитам](https://github.com/pllano/api-shop/commits/master) в этом репозитории.
[RSS](https://github.com/pllano/api-shop/commits/master.atom).

Лицензия API Shop
-------
The MIT License (MIT). Please see [LICENSE](https://github.com/pllano/api-shop/blob/master/LICENSE) for more information.
