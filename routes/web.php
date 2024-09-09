<?php

use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'Alnovate\Payway\Http\Controllers', 'middleware' => ['core', 'web']], function () {
    Route::get('payway/payment/success', ['as' => 'payway.payment.success', 'uses' => 'PaywayController@getSuccess', ]);
    Route::post('payway/generate/hash', 'PaywayController@generateHash');
});

Route::group(['namespace' => 'Alnovate\Payway\Http\Controllers', 'middleware' => ['core']], function () {
    Route::post('payway/payment/callback', ['as' => 'payway.payment.callback', 'uses' => 'PaywayController@getCallback', ]);
});
