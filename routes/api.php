<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WeatherController;


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
Route::get('current_temperature/{id}/', [WeatherController::class, 'getCurrentTemperature']);
Route::get('weather_stations', [WeatherController::class, 'listWeatherStations']);
