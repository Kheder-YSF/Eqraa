<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\AuthSettings;
use App\Http\Controllers\ChallengeController;
use App\Http\Controllers\Auth\UserAuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
// By Kheder Youssef 💜
Route::prefix('auth/')->group(function ()  {
   Route::controller(UserAuthController::class)->group(function () {
          Route::post('sign-up','signUp');
          Route::post('sign-in','signIn');
          Route::middleware('auth:sanctum')->post('sign-out','signOut');
   });
    Route::controller(AuthSettings::class)->group(function () {
          Route::post('forgot-password','forgotPassword');
          Route::post('check-password-reset-code','checkPasswordResetCode');
          Route::post('password-reset','passwordReset');
          Route::post('email-verify','emailVerify');
          Route::post('resend-email-verification-code','resendEmailVerificationCode');
 });
});
Route::prefix('books')->middleware(['auth:sanctum','verified'])->controller(BookController::class)->group(function () {
       Route::post('/','store');
       Route::get('/','index');
       Route::get('/{id}','show');
       Route::delete('/{id}','destroy');
       Route::put('/{id}','update'); 
       Route::get('/{id}/download','download');
       Route::post('/{id}/add-to-favorite','addToFavorite');      
       Route::post('/{id}/rate','rate');    
});
Route::prefix('user')->middleware(['auth:sanctum','verified'])->controller(UserController::class)->group(function () {
       Route::get('/favorite','favorites');         
});

Route::prefix('challenges')->middleware(['auth:sanctum','verified'])->controller(ChallengeController::class)->group(function () {
       Route::post('/','store');
       Route::get('/','index');
       Route::get('/{id}','show');
       Route::delete('/{id}','destroy');
       Route::put('/{id}','update'); 
       Route::post('/{id}/join','joinChallenge');
       Route::post('/{id}/resign','resignChallenge');
});