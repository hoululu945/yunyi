<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
use Hyperf\HttpServer\Router\Router;
Router::addServer('ws', function () {
    Router::get('/', 'App\Controller\WebSocketController');
});
Router::addRoute(['GET', 'POST', 'HEAD'], '/', 'App\Controller\IndexController@index');

Router::get('/favicon.ico', function () {
    return '';
});

Router::addRoute(['GET', 'POST', 'HEAD'], '/test', 'App\Controller\World@indexs');
Router::addGroup(
    '/v1', function () {
//    Router::get('/test2', [\App\Controller\IndexController::class, 'index']);
    Router::get('/test3', [\App\Controller\World::class, 'indexs']);

},
    ['middleware' => [\App\Middleware\Auth\CheckToken::class]]
);

