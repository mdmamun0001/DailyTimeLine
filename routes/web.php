<?php

/** @var Router $router */

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


use Laravel\Lumen\Routing\Router;




$router->group(['prefix' => 'api', 'middleware' => 'localization'], function () use ($router) {
    // User authentication
    $router->post('register', 'AuthController@register');
    $router->post('login', 'AuthController@login');
    $router->post('email-validate', 'UserController@emailValidation');
    $router->post('socialLogin', 'AuthController@socialLogin');


    // Users
    $router->group(['middleware' => 'auth'],function () use ($router) {
        $router->get('profile', 'UserController@profile');
        $router->get('users/{id}', 'UserController@show');
        $router->get('users', 'UserController@index');
        $router->put('users', 'UserController@update');
        $router->delete('users/{id}', 'UserController@destroy');
        $router->post('logout', 'AuthController@logout');
    });

    // Tasks
    $router->get('tasks/{id}', 'TaskController@show');
    $router->group(['middleware' => 'auth'],function () use ($router) {
        $router->post('tasks', 'TaskController@store');
        $router->get('tasks-by-date/{date}', 'TaskController@taskByDate');
        $router->get('tasks-stats', 'TaskController@progressStats');
        $router->get('tasks', 'TaskController@index');
        $router->get('hasAny-task','TaskController@hasAnyTask');
        $router->put('tasks/{id}', 'TaskController@update');
        $router->delete('tasks/{id}', 'TaskController@destroy');
        $router->post('tasks/{id}/{type}', 'TaskController@taskAssigning');
        $router->put('tasks/{id}/{type}/removeAssignedUser', 'TaskController@removeAssignedUser');
        $router->get('tasks/sharedTaskByUser/{id}', 'TaskController@sharedTask');
        $router->get('tasks/sharedUser/{id}', 'TaskController@sharedUser');

    });

});
