<?php

use Illuminate\Support\Facades\Route;
use Kreait\Firebase\Factory;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/firebase-test', function () {
    return "Firebase Connected";
});