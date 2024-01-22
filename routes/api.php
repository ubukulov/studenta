<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::group(['prefix' => 'v1', 'namespace' => 'API'], function(){
    Route::post('login', [ApiController::class, 'authentication']);
    Route::post('register', [ApiController::class, 'register']);
    Route::get('cities', [ApiController::class, 'cities']);
    Route::get('universities', [ApiController::class, 'universities']);
    Route::get('specialities', [ApiController::class, 'specialities']);
    Route::get('interests', [ApiController::class, 'interests']);

    Route::group(['middleware' => 'auth:sanctum'], function(){
        Route::get('organizations', [ApiController::class, 'organizations']);
        Route::get('categories', [ApiController::class, 'categories']);
        Route::get('promotions', [ApiController::class, 'promotions']);
        Route::get('promotion/{id}', [ApiController::class, 'getPromotionById']);
        Route::get('promotion/{id}/images', [ApiController::class, 'getPromotionImagesById']);
    });
});

