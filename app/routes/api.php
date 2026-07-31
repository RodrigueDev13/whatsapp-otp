<?php

use App\Http\Controllers\Api\OtpController;
use Illuminate\Support\Facades\Route;

Route::post('/otp/send', [OtpController::class, 'send'])
    ->middleware(['api.key', 'throttle:30,1']);
