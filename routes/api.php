<?php

use App\Http\Controllers\Auth\AuthSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\Auth\UserSocialAuthController;

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
// By Kheder Youssef
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
