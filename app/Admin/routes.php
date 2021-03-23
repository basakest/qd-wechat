<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Dcat\Admin\Admin;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {
    $router->get('/', 'HomeController@index');
    $router->get('/test', 'TestController@index');
    $router->post('/insert', 'TestController@insertToDatabase');
    $router->get('/create', 'TestController@createWechatMenu');
    $router->resource('users', 'UserController');
    $router->get('/menus', 'MenuController@index');
    $router->get('/menu', 'MenuController@menu');
    $router->post('/test', 'TestController@createWechatMenu');
});
