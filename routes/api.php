<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\MeetingDetailsController;

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/
Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function () {

    Route::post('login', 'AuthController@login');
    Route::post('signup', 'AuthController@signup');
    Route::post('logout', 'AuthController@logout');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('me', 'AuthController@me');

});

/*
|--------------------------------------------------------------------------
| PUBLIC CUSTOMER UPDATE (NO LOGIN – HASH LINK)
|--------------------------------------------------------------------------
*/
Route::get('/customer/public/{token}', [CustomersController::class, 'publicShow']);
Route::post('/customer/public/{token}', [CustomersController::class, 'publicUpdate']);

/*
|--------------------------------------------------------------------------
| ADMIN / SYSTEM ROUTES
|--------------------------------------------------------------------------
*/
Route::apiResource('/employee', 'EmployeesController');
Route::apiResource('/suppliers', 'SuppliersController');
Route::apiResource('/categories', 'CategoriesController');
Route::apiResource('/sub-categories', 'SubCategoriesController');
Route::apiResource('/craft', 'CraftController');
Route::apiResource('/care', 'CaresController');
Route::apiResource('/serves', 'ServesController');
Route::apiResource('/product', 'ProductsController');
Route::apiResource('/expens', 'ExpensesController');
Route::apiResource('/customer', 'CustomersController');

Route::post('/customer/{id}/generate-update-link', [CustomersController::class, 'generateUpdateLink']);
Route::put('/customer/{id}/approve', [CustomersController::class, 'updateApprove']);
Route::get('/customer/{id}', [CustomersController::class, 'show']);
Route::patch('/customer/{id}', [CustomersController::class, 'update']);

/*
|--------------------------------------------------------------------------
| SALARY / POS / CART
|--------------------------------------------------------------------------
*/
Route::post('/salary/paid/{id}', 'SalariesController@paid');
Route::get('/salary', 'SalariesController@salary');
Route::get('/salaryview/{id}', 'SalariesController@salaryview');

Route::get('/getproductcategoy/{id}', 'PosController@catProduct');
Route::post('/cart/add-category', 'PosController@addCategoryToCart');
Route::post('/stock/update/{id}', 'ProductsController@stockupdate');

Route::get('/addCart/{id}', 'CartController@addcart');
Route::get('/carts/get', 'CartController@getCart');
Route::get('cart/remove/{id}', 'CartController@cartRemove');
Route::get('/cart/cartInc/{id}', 'CartController@cartInc');
Route::get('/cart/cartDec/{id}', 'CartController@cartDec');
Route::post('/cart/add-category', 'CartController@addCategoryToCart');

/*
|--------------------------------------------------------------------------
| ORDERS
|--------------------------------------------------------------------------
*/
Route::get('/vats', 'ExtraController@vats');
Route::post('/orderdone', 'PosController@orderdone');

Route::get('/orders', 'OrderController@getorders');
Route::get('/orders/details/{id}', 'OrderController@details');
Route::get('/orders/orderdetails/{id}', 'OrderController@orderdetails');
Route::put('/order/{id}/categories', 'OrderController@updatecraft');
Route::put('/order/{id}/serve', 'OrderController@updateserve');
Route::put('/order/{id}/care', 'OrderController@updatecare');
Route::put('/order/{id}/approve', 'OrderController@updateApprove');

/*
|--------------------------------------------------------------------------
| ADMIN DASHBOARD
|--------------------------------------------------------------------------
*/
Route::get('/today/sell', 'PosController@todaySell');
Route::get('/today/income', 'PosController@todayincome');
Route::get('/today/due', 'PosController@todaydue');
Route::get('/today/exp', 'PosController@todayexp');
Route::get('/today/stock', 'PosController@todaystock');

/*
|--------------------------------------------------------------------------
| MEETING
|--------------------------------------------------------------------------
*/
Route::get('/meetings', [MeetingController::class, 'index']);
Route::get('/meetings/{meeting}', [MeetingController::class, 'show']);
Route::post('/meetings', [MeetingController::class, 'store']);
Route::put('/meetings/{meeting}', [MeetingController::class, 'update']);
Route::delete('/meetings/{meeting}', [MeetingController::class, 'destroy']);

/*
|--------------------------------------------------------------------------
| MEETING DETAILS
|--------------------------------------------------------------------------
*/
Route::get('/meeting-details', [MeetingDetailsController::class, 'index']);
Route::get('/meeting-details/{meeting}', [MeetingDetailsController::class, 'show']);
Route::post('/meeting-details', [MeetingDetailsController::class, 'store']);
Route::put('/meeting-details/{meeting}', [MeetingDetailsController::class, 'update']);
Route::delete('/meeting-details/{meeting}', [MeetingDetailsController::class, 'destroy']);
