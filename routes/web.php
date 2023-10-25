<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/auth/google/youtube/callback', [MainController::class, 'update']);

Route::post('/livestream/start/title/{title}/description/{desc}', 'LivestreamController@start');
Route::post('/livestream/stop/title/{title}/description/{desc}', 'LivestreamController@end');
Route::get('/livestream/start/title/{title}/description/{desc}', 'LivestreamController@start');
Route::get('/livestream/stop/title/{title}/description/{desc}', 'LivestreamController@end');