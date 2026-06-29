<?php

use Illuminate\Support\Facades\Route;

Route::prefix('whatsapp-bot')->group(function () {
    Route::post('webhook/{token}', 'Api\WebhookController@receive')->name('whatsapp-bot.webhook');
});
