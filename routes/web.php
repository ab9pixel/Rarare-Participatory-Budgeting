<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});


$router->group(['prefix'=>'api'],function() use ($router){
    $router->get('list/{count}/{user_id}/{type}/{isHome}','ParticipatoryBudgetController@list');
    $router->get('find/{id}','ParticipatoryBudgetController@find');
    $router->get('delete/{id}','ParticipatoryBudgetController@delete');

    $router->post('/save','ParticipatoryBudgetController@save');
    $router->post('/like','ParticipatoryBudgetController@like');
    $router->post('/comment','ParticipatoryBudgetController@comment');
    $router->post('/user/option','ParticipatoryBudgetController@user_option');
});