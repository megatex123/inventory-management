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
Route::get('/suppliers/all', 'SuppliersController@all');
Route::get('/suppliers/filter-options', 'SuppliersController@filterOptions');
Route::apiResource('/suppliers', 'SuppliersController');
Route::get('/categories/all', 'CategoriesController@all');
Route::get('/categories/filter-options', 'CategoriesController@filterOptions');
Route::apiResource('/categories', 'CategoriesController');
Route::get('/sub-categories/all', 'SubCategoriesController@all');
Route::get('/sub-categories/filter-options', 'SubCategoriesController@filterOptions');
Route::apiResource('/sub-categories', 'SubCategoriesController');
Route::get('/craft/filter-options', 'CraftController@filterOptions');
Route::apiResource('/craft', 'CraftController');
Route::get('/care/all', 'CaresController@all');
Route::get('/care/filter-options', 'CaresController@filterOptions');
Route::apiResource('/care', 'CaresController');
Route::apiResource('/serves', 'ServesController');
Route::get('/product/all', 'ProductsController@all');
Route::get('/product/filter-options', 'ProductsController@filterOptions');
Route::apiResource('/product', 'ProductsController');
Route::apiResource('/expens', 'ExpensesController');
Route::get('/customer/all', 'CustomersController@all');
Route::apiResource('/customer', 'CustomersController');
Route::get('/brand/filter-options', 'BrandController@filterOptions');
Route::apiResource('/brand', 'BrandController');

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
Route::get('/orders/today', 'OrderController@today');
Route::get('/orders/all', 'OrderController@allOrders');
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
| CRAFT INSPECTION (Studio Inspection)
|--------------------------------------------------------------------------
*/
Route::get('/craft-inspections/statistics', 'CraftInspectionController@statistics');

Route::prefix('order/{orderId}/inspection/{round}')->group(function () {
    Route::get('/', 'CraftInspectionController@show');
    Route::post('/items', 'CraftInspectionController@storeItem');
    Route::post('/items/{itemId}', 'CraftInspectionController@updateItem');
    Route::delete('/items/{itemId}', 'CraftInspectionController@destroyItem');
    Route::post('/complete', 'CraftInspectionController@complete');
});

/*
|--------------------------------------------------------------------------
| PERFORMANCE TESTING (Phase 1: Assembly & Boot)
|--------------------------------------------------------------------------
*/
Route::prefix('order/{orderId}/performance-test/{round}')->group(function () {
    Route::get('/', 'PerformanceTestController@show');
    Route::post('/', 'PerformanceTestController@update');
    Route::post('/items/{itemId}', 'PerformanceTestController@updateItem');
    Route::post('/cpu-results', 'PerformanceTestController@updateCpuResults');
    Route::post('/gpu-results', 'PerformanceTestController@updateGpuResults');
    Route::post('/system-stability-results', 'PerformanceTestController@updateSystemStabilityResults');
    Route::post('/memory-results', 'PerformanceTestController@updateMemoryResults');
    Route::post('/storage-results', 'PerformanceTestController@updateStorageResults');
    Route::post('/cooling-performance-results', 'PerformanceTestController@updateCoolingPerformanceResults');
    Route::post('/cooling-system-results', 'PerformanceTestController@updateCoolingSystemResults');
    Route::post('/display-results', 'PerformanceTestController@updateDisplayResults');
    Route::post('/network-results', 'PerformanceTestController@updateNetworkResults');
    Route::post('/usb-results', 'PerformanceTestController@updateUsbResults');
    Route::post('/usb-ports', 'PerformanceTestController@storeUsbPort');
    Route::post('/usb-ports/{itemId}', 'PerformanceTestController@updateUsbPort');
    Route::delete('/usb-ports/{itemId}', 'PerformanceTestController@destroyUsbPort');
    Route::post('/complete', 'PerformanceTestController@complete');
});

/*
|--------------------------------------------------------------------------
| ONSITE HANDOVER (QuiviCraft)
|--------------------------------------------------------------------------
*/
Route::prefix('order/{orderId}/onsite-handover/{round}')->group(function () {
    Route::get('/', 'OnsiteHandoverController@show');
    Route::post('/report-info', 'OnsiteHandoverController@updateReportInfo');
    Route::post('/customer-info', 'OnsiteHandoverController@updateCustomerInfo');
    Route::post('/build-info', 'OnsiteHandoverController@updateBuildInfo');
    Route::post('/studio-docs', 'OnsiteHandoverController@updateStudioDocs');
    Route::post('/arrival', 'OnsiteHandoverController@updateArrival');
    Route::post('/transportation', 'OnsiteHandoverController@updateTransportation');
    Route::post('/assembly', 'OnsiteHandoverController@updateAssembly');
    Route::post('/post-build-hardware', 'OnsiteHandoverController@updatePostBuildHardware');
    Route::post('/post-build-software', 'OnsiteHandoverController@updatePostBuildSoftware');
    Route::post('/customer-acceptance', 'OnsiteHandoverController@updateCustomerAcceptance');
    Route::post('/acknowledgement', 'OnsiteHandoverController@updateAcknowledgement');
});

/*
|--------------------------------------------------------------------------
| ONSITE HANDOVER (Studio)
|--------------------------------------------------------------------------
*/
Route::prefix('order/{orderId}/onsite-handover-studio/{round}')->group(function () {
    Route::get('/', 'OnsiteHandoverStudioController@show');
    Route::post('/report-info', 'OnsiteHandoverStudioController@updateReportInfo');
    Route::post('/build-info', 'OnsiteHandoverStudioController@updateBuildInfo');
    Route::post('/studio-docs', 'OnsiteHandoverStudioController@updateStudioDocs');
    Route::post('/arrival', 'OnsiteHandoverStudioController@updateArrival');
    Route::post('/post-transport', 'OnsiteHandoverStudioController@updatePostTransport');
    Route::post('/post-handover', 'OnsiteHandoverStudioController@updatePostHandover');
    Route::post('/customer-acceptance', 'OnsiteHandoverStudioController@updateCustomerAcceptance');
});

/*
|--------------------------------------------------------------------------
| CUSTOMER PROGRESS MANAGEMENT
|--------------------------------------------------------------------------
*/
Route::prefix('customer-progress')->group(function () {
    Route::get('/', 'CustomerProgressController@index');
    Route::post('/', 'CustomerProgressController@store');
    Route::get('/statistics', 'CustomerProgressController@statistics');

    Route::prefix('{id}')->group(function () {
        Route::get('/', 'CustomerProgressController@show');
        Route::post('/', 'CustomerProgressController@update');
        Route::delete('/', 'CustomerProgressController@destroy');
        Route::get('/download', 'CustomerProgressController@download');
    });
});

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
Route::get('/meetings/all', 'MeetingController@all');
Route::get('/meetings/statistics', 'MeetingController@statistics');
Route::get('/meetings/filter-options', 'MeetingController@filterOptions');
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
Route::get('/meeting-details/statistics', 'MeetingDetailsController@statistics');
Route::get('/meeting-details/{id}', 'MeetingDetailsController@show');
Route::post('/meeting-details', 'MeetingDetailsController@store');
Route::put('/meeting-details/{id}', 'MeetingDetailsController@update');
Route::delete('/meeting-details/{id}', 'MeetingDetailsController@destroy');

/*
|--------------------------------------------------------------------------
| UAT MEETING
|--------------------------------------------------------------------------
*/
Route::get('/uat-meeting', 'UatMeetingController@index');
Route::get('/uat-meeting/{id}', 'UatMeetingController@show');
Route::post('/uat-meeting', 'UatMeetingController@store');
Route::put('/uat-meeting/{id}', 'UatMeetingController@update');
Route::delete('/uat-meeting/{id}', 'UatMeetingController@destroy');

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
    Route::get('/next-id', 'CareWarrantyController@getNextId');
    Route::get('/all', 'CareWarrantyController@all');
    Route::get('/{id}', 'CareWarrantyController@show');
    Route::put('/{id}', 'CareWarrantyController@update');
    Route::delete('/{id}', 'CareWarrantyController@destroy');
});

/*
|--------------------------------------------------------------------------
| MASTER SKU / INVENTORY ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('master-sku')->group(function () {
    Route::get('/', 'MasterSkuController@index');
    Route::post('/', 'MasterSkuController@store');
    Route::get('/statistics', 'MasterSkuController@statistics');
    Route::get('/search', 'MasterSkuController@search');

    Route::prefix('{id}')->group(function () {
        Route::get('/', 'MasterSkuController@show');
        Route::get('/edit', 'MasterSkuController@edit');
        Route::put('/', 'MasterSkuController@update');
        Route::patch('/', 'MasterSkuController@update');
        Route::delete('/', 'MasterSkuController@destroy');
        Route::patch('/status', 'MasterSkuController@updateStatus');
    });
});

Route::prefix('inv-care')->group(function () {
    Route::get('/', 'InvCareController@index');
    Route::post('/', 'InvCareController@store');
    Route::get('/statistics', 'InvCareController@statistics');
    Route::get('/search', 'InvCareController@search');

    Route::prefix('{id}')->group(function () {
        Route::get('/', 'InvCareController@show');
        Route::get('/edit', 'InvCareController@edit');
        Route::put('/', 'InvCareController@update');
        Route::patch('/', 'InvCareController@update');
        Route::delete('/', 'InvCareController@destroy');
    });
});

Route::prefix('inventory-movements')->group(function () {
    Route::get('/', 'InventoryMovementController@index');
    Route::post('/', 'InventoryMovementController@store');
    Route::get('/statistics', 'InventoryMovementController@statistics');

    Route::prefix('{id}')->group(function () {
        Route::get('/', 'InventoryMovementController@show');
        Route::put('/', 'InventoryMovementController@update');
        Route::patch('/', 'InventoryMovementController@update');
        Route::delete('/', 'InventoryMovementController@destroy');
    });
});

Route::prefix('destinations')->group(function () {
    Route::get('/', 'DestinationController@index');
    Route::post('/', 'DestinationController@store');
    Route::put('/{id}', 'DestinationController@update');
    Route::delete('/{id}', 'DestinationController@destroy');
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
    Route::get('/order/{orderId}', 'ServePceController@getByOrder');

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
    Route::get('/order/{orderId}', 'ServeMpsController@getByOrder'); // GET /api/serve-mps/order/{orderId}

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
    Route::get('/statistics', 'ServeBekController@statistics');
    Route::get('/{id}', 'ServeBekController@show');
    Route::put('/{id}', 'ServeBekController@update');
    Route::delete('/{id}', 'ServeBekController@destroy');

    // Custom routes
    Route::post('/{id}/restore', 'ServeBekController@restore');
    Route::get('/serve-data/{serveDataId}', 'ServeBekController@getByServeDataId');
    Route::get('/order/{orderId}', 'ServeBekController@getByOrder');
    Route::post('/{id}/claim', 'ServeBekController@makeClaim');
});

/*
|--------------------------------------------------------------------------
| QUIVIMERCH ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('merch-items')->group(function () {
    Route::get('/', 'MerchItemController@index');
    Route::post('/', 'MerchItemController@store');
    Route::get('/statistics', 'MerchItemController@statistics');
    Route::get('/search', 'MerchItemController@search');

    Route::prefix('{id}')->group(function () {
        Route::get('/', 'MerchItemController@show');
        Route::get('/edit', 'MerchItemController@edit');
        Route::put('/', 'MerchItemController@update');
        Route::patch('/', 'MerchItemController@update');
        Route::delete('/', 'MerchItemController@destroy');
    });
});

Route::prefix('inv-merch')->group(function () {
    Route::get('/', 'InvMerchController@index');
    Route::post('/', 'InvMerchController@store');
    Route::get('/statistics', 'InvMerchController@statistics');
    Route::get('/search', 'InvMerchController@search');

    Route::prefix('{id}')->group(function () {
        Route::get('/', 'InvMerchController@show');
        Route::get('/edit', 'InvMerchController@edit');
        Route::put('/', 'InvMerchController@update');
        Route::patch('/', 'InvMerchController@update');
        Route::delete('/', 'InvMerchController@destroy');
    });
});

Route::prefix('merch-orders')->group(function () {
    Route::get('/', 'MerchOrderController@index');
    Route::post('/', 'MerchOrderController@store');
    Route::get('/statistics', 'MerchOrderController@statistics');
    Route::get('/search', 'MerchOrderController@search');

    Route::prefix('{id}')->group(function () {
        Route::get('/', 'MerchOrderController@show');
        Route::get('/edit', 'MerchOrderController@edit');
        Route::put('/', 'MerchOrderController@update');
        Route::patch('/', 'MerchOrderController@update');
        Route::delete('/', 'MerchOrderController@destroy');
    });
});

/*
|--------------------------------------------------------------------------
| QUIVIPLUS ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('plus-services')->group(function () {
    Route::get('/', 'PlusServiceController@index');
    Route::post('/', 'PlusServiceController@store');
    Route::get('/statistics', 'PlusServiceController@statistics');
    Route::get('/search', 'PlusServiceController@search');

    Route::prefix('{id}')->group(function () {
        Route::get('/', 'PlusServiceController@show');
        Route::get('/edit', 'PlusServiceController@edit');
        Route::put('/', 'PlusServiceController@update');
        Route::patch('/', 'PlusServiceController@update');
        Route::delete('/', 'PlusServiceController@destroy');
    });
});

Route::prefix('plus-orders')->group(function () {
    Route::get('/', 'PlusOrderController@index');
    Route::post('/', 'PlusOrderController@store');
    Route::get('/statistics', 'PlusOrderController@statistics');
    Route::get('/search', 'PlusOrderController@search');

    Route::prefix('{id}')->group(function () {
        Route::get('/', 'PlusOrderController@show');
        Route::get('/edit', 'PlusOrderController@edit');
        Route::put('/', 'PlusOrderController@update');
        Route::patch('/', 'PlusOrderController@update');
        Route::delete('/', 'PlusOrderController@destroy');
    });
});

/*
|--------------------------------------------------------------------------
| QUIVITHREAD ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('thread-bom')->group(function () {
    Route::get('/', 'ThreadBomController@index');
    Route::post('/', 'ThreadBomController@store');
    Route::get('/statistics', 'ThreadBomController@statistics');
    Route::get('/search', 'ThreadBomController@search');
    Route::get('/resolve', 'ThreadBomController@resolve');

    Route::prefix('{id}')->group(function () {
        Route::get('/', 'ThreadBomController@show');
        Route::get('/edit', 'ThreadBomController@edit');
        Route::put('/', 'ThreadBomController@update');
        Route::patch('/', 'ThreadBomController@update');
        Route::delete('/', 'ThreadBomController@destroy');
    });
});

Route::prefix('inv-thread')->group(function () {
    Route::get('/', 'InvThreadController@index');
    Route::post('/', 'InvThreadController@store');
    Route::get('/statistics', 'InvThreadController@statistics');
    Route::get('/search', 'InvThreadController@search');

    Route::prefix('{id}')->group(function () {
        Route::get('/', 'InvThreadController@show');
        Route::get('/edit', 'InvThreadController@edit');
        Route::put('/', 'InvThreadController@update');
        Route::patch('/', 'InvThreadController@update');
        Route::delete('/', 'InvThreadController@destroy');
    });
});

Route::prefix('thread-orders')->group(function () {
    Route::get('/', 'ThreadOrderController@index');
    Route::post('/', 'ThreadOrderController@store');
    Route::get('/statistics', 'ThreadOrderController@statistics');
    Route::get('/search', 'ThreadOrderController@search');

    Route::prefix('{id}')->group(function () {
        Route::get('/', 'ThreadOrderController@show');
        Route::get('/edit', 'ThreadOrderController@edit');
        Route::put('/', 'ThreadOrderController@update');
        Route::patch('/', 'ThreadOrderController@update');
        Route::delete('/', 'ThreadOrderController@destroy');
    });
});

/*
|--------------------------------------------------------------------------
| REFUND ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('refunds')->group(function () {
    Route::get('/', 'RefundController@index');
    Route::post('/', 'RefundController@store');
    Route::get('/statistics', 'RefundController@statistics');
    Route::get('/order-options', 'RefundController@orderOptions');

    Route::prefix('{id}')->group(function () {
        Route::get('/', 'RefundController@show');
        Route::get('/edit', 'RefundController@edit');
        Route::put('/', 'RefundController@update');
        Route::patch('/', 'RefundController@update');
        Route::delete('/', 'RefundController@destroy');
    });
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
