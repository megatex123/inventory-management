<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
Route::get('/customer/public/{token}', 'CustomersController@publicShow');
Route::post('/customer/public/{token}', 'CustomersController@publicUpdate');

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

Route::post('/customer/{id}/generate-update-link', 'CustomersController@generateUpdateLink');
Route::put('/customer/{id}/approve', 'CustomersController@updateApprove');
Route::get('/customer/{id}', 'CustomersController@show');
Route::patch('/customer/{id}', 'CustomersController@update');

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
Route::put('/order/{id}/approve', 'OrderController@updateApprove');
Route::get('/order/edit/{id}', 'OrderController@edit')->name('order.edit');
Route::post('/order/update/{id}', 'OrderController@updateOrderDetails')->name('order.update');
Route::get('/order/get/{id}', 'OrderController@getOrderWithDetails');
Route::get('/order/edit-data/{id}', 'OrderController@getOrderEditData');
Route::get('/order/with-details/{id}', 'OrderController@getOrderWithDetails');
Route::post('/order/update/{id}', 'OrderController@updateOrderDetails');
Route::get('/orders/statistics', 'OrderController@getStatistics');

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
Route::get('/meetings', 'MeetingController@index');
Route::get('/meetings/{meeting}', 'MeetingController@show');
Route::post('/meetings', 'MeetingController@store');
Route::put('/meetings/{meeting}', 'MeetingController@update');
Route::delete('/meetings/{meeting}', 'MeetingController@destroy');

/*
|--------------------------------------------------------------------------
| MEETING DETAILS
|--------------------------------------------------------------------------
*/
Route::get('/meeting-details', 'MeetingDetailsController@index');
Route::get('/meeting-details/{id}', 'MeetingDetailsController@show');
Route::post('/meeting-details', 'MeetingDetailsController@store');
Route::put('/meeting-details/{id}', 'MeetingDetailsController@update');
Route::delete('/meeting-details/{id}', 'MeetingDetailsController@destroy');

/*
|--------------------------------------------------------------------------
| SERVE DATA ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('serve-data')->group(function () {
    Route::get('/', 'ServeDataController@index');
    Route::post('/', 'ServeDataController@store');
    Route::get('/statistics', 'ServeDataController@statistics');
    Route::get('/search', 'ServeDataController@search');
    Route::get('/customer/{customerId}', 'ServeDataController@byCustomer');
    Route::get('/order/{orderId}', 'ServeDataController@byOrder');
    Route::get('/export', 'ServeDataController@exportToCSV');

    Route::prefix('{id}')->group(function () {
        Route::get('/', 'ServeDataController@show');
        Route::put('/', 'ServeDataController@update');
        Route::patch('/', 'ServeDataController@update');
        Route::delete('/', 'ServeDataController@destroy');
        Route::post('/restore', 'ServeDataController@restore');
    });
});

/*
|--------------------------------------------------------------------------
| CARE DATA ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('care-data')->group(function () {
    Route::get('/', 'CareDataController@index');
    Route::post('/', 'CareDataController@store');
    Route::get('/statistics', 'CareDataController@statistics');
    Route::get('/search', 'CareDataController@search');
    Route::get('/customer/{customerId}', 'CareDataController@byCustomer');
    Route::get('/order/{orderId}', 'CareDataController@byOrder');
    Route::get('/export', 'CareDataController@exportToCSV');

    Route::prefix('{id}')->group(function () {
        Route::get('/', 'CareDataController@show');
        Route::put('/', 'CareDataController@update');
        Route::patch('/', 'CareDataController@update');
        Route::delete('/', 'CareDataController@destroy');
        Route::post('/restore', 'CareDataController@restore');
    });
});

Route::prefix('care-warranty')->group(function () {
    // Basic CRUD routes
    Route::get('/', 'CareWarrantyController@index');
    Route::post('/', 'CareWarrantyController@store');
    Route::get('/statistics', 'CareWarrantyController@statistics');
    Route::get('/{id}', 'CareWarrantyController@show');
    Route::put('/{id}', 'CareWarrantyController@update');
    Route::delete('/{id}', 'CareWarrantyController@destroy');
    Route::get('/next-id', 'CareWarrantyController@getNextId');
});

Route::get('/product-warranty/by-category', 'ProductWarrantyController@getByCategory');
Route::get('/product-warranty/available', 'ProductWarrantyController@getAvailableWarranties');
Route::prefix('product-warranty')->group(function () {
    // Basic CRUD routes
    Route::get('/', 'ProductWarrantyController@index');
    Route::post('/', 'ProductWarrantyController@store');
    Route::get('/statistics', 'ProductWarrantyController@statistics');
    Route::get('/export', 'ProductWarrantyController@export');
    Route::post('/bulk-delete', 'ProductWarrantyController@bulkDelete');
    Route::get('/generate-serial', 'ProductWarrantyController@generateSerialNo');
    Route::post('/check-serial', 'ProductWarrantyController@checkSerialNo');
    Route::get('/{id}', 'ProductWarrantyController@show');
    Route::put('/{id}', 'ProductWarrantyController@update');
    Route::delete('/{id}', 'ProductWarrantyController@destroy');
});

/*
|--------------------------------------------------------------------------
| SERVE PCE ROUTES (Corrected - no duplicate routes)
|--------------------------------------------------------------------------
*/
Route::prefix('serve-pce')->group(function () {  // <-- This should be 'serve-pces'
    Route::get('/', 'ServePceController@index');
    Route::post('/', 'ServePceController@store');
    Route::get('/statistics', 'ServePceController@statistics');
    Route::get('/search', 'ServePceController@search');

    Route::prefix('{id}')->group(function () {
        Route::get('/', 'ServePceController@show');
        Route::put('/', 'ServePceController@update');
        Route::patch('/', 'ServePceController@update');
        Route::delete('/', 'ServePceController@destroy');
    });
});

/*
|--------------------------------------------------------------------------
| SERVE MPS ROUTES (Updated to match Vue component)
|--------------------------------------------------------------------------
*/
Route::prefix('serve-mps')->group(function () {
    // Main routes matching Vue component
    Route::get('/', 'ServeMpsController@index');          // GET /api/serve-mps
    Route::post('/', 'ServeMpsController@store');         // POST /api/serve-mps
    Route::get('/statistics', 'ServeMpsController@statistics'); // GET /api/serve-mps/statistics

    // Individual item routes
    Route::get('/{id}', 'ServeMpsController@show');       // GET /api/serve-mps/{id}
    Route::put('/{id}', 'ServeMpsController@update');     // PUT /api/serve-mps/{id}
    Route::patch('/{id}', 'ServeMpsController@update');   // PATCH /api/serve-mps/{id}
    Route::delete('/{id}', 'ServeMpsController@destroy'); // DELETE /api/serve-mps/{id}

    // Optional additional routes if needed
    Route::get('/search', 'ServeMpsController@search');   // GET /api/serve-mps/search (optional)
    Route::post('/{id}/restore', 'ServeMpsController@restore'); // POST /api/serve-mps/{id}/restore (optional)
});

/*
|--------------------------------------------------------------------------
| SERVE BEK ROUTES (Updated to match Vue component)
|--------------------------------------------------------------------------
*/
Route::prefix('serve-beks')->group(function () {
    Route::get('/', 'ServeBekController@index');
    Route::post('/', 'ServeBekController@store');
    Route::get('/{id}', 'ServeBekController@show');
    Route::put('/{id}', 'ServeBekController@update');
    Route::delete('/{id}', 'ServeBekController@destroy');

    // Custom routes
    Route::post('/{id}/restore', 'ServeBekController@restore');
    Route::get('/serve-data/{serveDataId}', 'ServeBekController@getByServeDataId');
    Route::post('/{id}/claim', 'ServeBekController@makeClaim');
});

/*
|--------------------------------------------------------------------------
| CARE DATA ROUTES (Removed duplicate, corrected)
|--------------------------------------------------------------------------
*/
// Note: The previous duplicate serve-pce routes were removed
// Only one serve-pce route group exists above

/*
|--------------------------------------------------------------------------
| API TEST ROUTE (Optional - for debugging)
|--------------------------------------------------------------------------
*/
Route::get('/test-connection', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is working',
        'timestamp' => now(),
        'version' => '1.0'
    ]);
});

/*
|--------------------------------------------------------------------------
| FALLBACK ROUTE
|--------------------------------------------------------------------------
*/
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found. Please check the URL.',
        'available_endpoints' => [
            'GET /api/serve-mps',
            'GET /api/serve-mps/statistics',
            'GET /api/serve-mps/{id}',
            'POST /api/serve-mps',
            'PUT /api/serve-mps/{id}',
            'DELETE /api/serve-mps/{id}'
        ]
    ], 404);
});
