<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/** @var object $router **/

$router->get('/', 'AuthController::login');

$router->get('/login', 'AuthController::login');
$router->post('/login', 'AuthController::login');
$router->get('/register', 'AuthController::register');
$router->post('/register', 'AuthController::register');
$router->post('/logout', 'AuthController::logout');

$router->post('/api/register', 'ApiController::register');
$router->post('/api/login', 'ApiController::login');
$router->post('/api/refresh', 'ApiController::refresh');
$router->post('/api/logout', 'ApiController::logout');
$router->options('/api/register', 'ApiController::preflight');
$router->options('/api/login', 'ApiController::preflight');
$router->options('/api/refresh', 'ApiController::preflight');
$router->options('/api/logout', 'ApiController::preflight');
$router->options('/api/products', 'ApiController::preflight');
$router->options('/api/products/{id}', 'ApiController::preflight')->where_number('id');
$router->get('/api/products', 'ApiController::products');
$router->post('/api/products', 'ApiController::create_product');
$router->put('/api/products/{id}', 'ApiController::update_product')->where_number('id');
$router->patch('/api/products/{id}', 'ApiController::update_product')->where_number('id');
$router->delete('/api/products/{id}', 'ApiController::delete_product')->where_number('id');

$router->group(['middleware' => 'auth'], function ($router) {
	$router->get('/create-migration/{migration_class}', 'MigrationController::create_migration');
	$router->get('/migrate', 'MigrationController::migrate');
	$router->get('/rollback', 'MigrationController::rollback');
	$router->get('/rollback-all', 'MigrationController::rollback_all');
	$router->get('/refresh', 'MigrationController::refresh');
	$router->get('/status', 'MigrationController::status');
});

$router->group(['middleware' => 'auth'], function ($router) {
	$router->get('/products', 'ProductsController::index');
	$router->get('/products/create', 'ProductsController::create');
	$router->post('/products/create', 'ProductsController::store');
	$router->get('/products/edit/{id}', 'ProductsController::edit')->where_number('id');
	$router->post('/products/edit/{id}', 'ProductsController::update')->where_number('id');
	$router->post('/products/delete/{id}', 'ProductsController::delete')->where_number('id');
});