<?php

use App\Http\Controllers\API\GroupReviewController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ApiController;
use App\Http\Controllers\API\GroupController;
use App\Http\Controllers\API\EventController;
use App\Http\Controllers\API\PromotionController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\NotificationController;

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
    Route::get('privacy-policy', [ApiController::class, 'privacyPolicy']);
    Route::get('get-promotions', [ApiController::class, 'promotions']);
    Route::get('get-events', [ApiController::class, 'getEvents']);
    Route::get('get-groups', [ApiController::class, 'getGroups']);
    Route::post('forget-password', [ApiController::class, 'forgetPassword']);
    Route::post('confirmation-code', [ApiController::class, 'confirmationCode']);
    Route::get('categories', [ApiController::class, 'categories']);
    # Организация
    Route::get('organizations', [ApiController::class, 'organizations']);

    Route::group(['middleware' => 'auth:sanctum'], function(){
        # Категория
        Route::post('categories', [CategoryController::class, 'addCategory']);
        Route::post('categories/{id}/update', [CategoryController::class, 'updateCategory']);

        # Акции
        Route::get('promotions', [PromotionController::class, 'promotions']);
        Route::get('promotion/{id}', [PromotionController::class, 'getPromotionById']);
        Route::get('promotion/{id}/images', [PromotionController::class, 'getPromotionImagesById']);
        Route::get('promotions/filters', [PromotionController::class, 'getPromotionFilters']);

        # Группы
        Route::get('groups', [GroupController::class, 'groups']);
        Route::get('group/{id}', [GroupController::class, 'getGroupById']);
        Route::post('group/store', [GroupController::class, 'store']);
        Route::match(['put', 'patch'], 'group/{id}/update', [GroupController::class, 'update']);
        Route::delete('group/{id}/delete', [GroupController::class, 'delete']);
        Route::post('group/subscribe', [GroupController::class, 'subscribe']);
        Route::post('group/unsubscribe', [GroupController::class, 'unsubscribe']);
        # Group Reviews
        Route::get('group/{id}/reviews', [GroupReviewController::class, 'getGroupReviews']);
        Route::post('group/review/store', [GroupReviewController::class, 'groupReviewStore']);
        Route::match(['put', 'patch'], 'group/review/{id}/update', [GroupReviewController::class, 'groupReviewUpdate']);
        Route::delete('group/review/{id}/delete', [GroupReviewController::class, 'groupReviewDelete']);

        # Ивенты
        Route::get('events', [EventController::class, 'events']);
        Route::get('event/{id}', [EventController::class, 'getEventById']);
        Route::post('event/store', [EventController::class, 'store']);
        Route::match(['put', 'patch'], 'event/{id}/update', [EventController::class, 'update']);
        Route::delete('event/{id}/delete', [EventController::class, 'delete']);
        Route::post('event/subscribe', [EventController::class, 'subscribe']);
        Route::post('event/unsubscribe', [EventController::class, 'unsubscribe']);
        Route::get('subscribed-events', [EventController::class, 'subscribedEvents']);
        Route::get('get-my-events', [EventController::class, 'getMyEvents']);
        Route::post('confirm-subscribe', [EventController::class, 'confirmSubscribe']);
        Route::get('{event_id}/get-requests-for-subscribe', [EventController::class, 'getRequestsForSubscribe']);
        Route::get('get-list-of-events-where-sent-requests', [EventController::class, 'getListOfEventsWhereSentRequests']);
        Route::get('history-of-events', [EventController::class, 'historyOfEvents']);

        # Настройки профиля
        Route::get('get-profile', [UserController::class, 'getProfile']);
        Route::post('profile/store', [UserController::class, 'storeProfile']);
        Route::delete('profile/delete', [UserController::class, 'deleteProfile']);
        Route::post('change-password', [UserController::class, 'changePassword']);
        Route::delete('delete/avatar', [UserController::class, 'deleteAvatar']);

        # Загрузка файлов
        Route::post('image/upload', [UserController::class, 'uploadImage']);

        # Notification
        Route::get('get-notification-types', [NotificationController::class, 'getNotificationTypes']);
        Route::get('notification/{type}', [NotificationController::class, 'getNotification']);
        Route::get('notifications/count', [NotificationController::class, 'getNotificationCount']);
        Route::post('notification/read', [NotificationController::class, 'updateNotification']);
    });
});

