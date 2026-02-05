<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MessageLikeController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\GachaController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('legacy.auth')->group(function () {
    Route::get('/user', [UserController::class, 'me']);
    Route::post('/change-password', [UserController::class, 'changePassword']);
    Route::delete('/user', [UserController::class, 'deleteAccount']);
    Route::post('/user', [UserController::class, 'deleteAccount']);

    // Stalker Image
    Route::post('/stalker-image', [UserController::class, 'uploadStalkerImage']);
    Route::delete('/stalker-image', [UserController::class, 'deleteStalkerImage']);

    // Rooms
    Route::get('/rooms', [RoomController::class, 'index']);
    Route::post('/rooms', [RoomController::class, 'store']);
    Route::get('/rooms/{id}', [RoomController::class, 'show']);
    Route::put('/rooms/{id}', [RoomController::class, 'update']);
    Route::delete('/rooms/{id}', [RoomController::class, 'destroy']);

    // Messages
    Route::post('/messages', [MessageController::class, 'store']);
    Route::delete('/messages/{id}', [MessageController::class, 'destroy']);

    // Message Likes
    Route::post('/message-likes', [MessageLikeController::class, 'toggle']);

    // Chat (AI)
    Route::post('/chat', [ChatController::class, 'chat']);

    // Gacha
    Route::post('/gacha', [GachaController::class, 'execute']);
    Route::get('/gacha-status', [GachaController::class, 'getStatuses']);
    Route::get('/gacha-images', [GachaController::class, 'getImages']);

    // Goals (チャットから目標達成メモ)
    Route::get('/goals', [GoalController::class, 'index']);
    Route::post('/goals', [GoalController::class, 'store']);
    Route::put('/goals', [GoalController::class, 'update']);
    Route::delete('/goals', [GoalController::class, 'destroy']);

    // Stories (未来Story)
    Route::get('/story', [StoryController::class, 'index']);
    Route::get('/story/room-goals', [StoryController::class, 'getRoomGoals']);
    Route::post('/story', [StoryController::class, 'store']);
    Route::post('/story/generate-image', [StoryController::class, 'generateImage']);
    Route::put('/story', [StoryController::class, 'update']);

    // Contact
    Route::post('/contact', [ContactController::class, 'store']);
    Route::delete('/story', [StoryController::class, 'destroy']);

    // Today Topics (今日のテーマランキング)
    Route::get('/today-topics', [RoomController::class, 'todayTopics']);

    // Weekly Topics Ranking (この1週間のテーマランキング)
    Route::get('/weekly-topics-ranking', [RoomController::class, 'weeklyTopicsRanking']);
});
