<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\feeds\FeedsController;
use App\Http\Controllers\PokemonController;


Route::get('/feeds', [FeedsController::class, 'updateFromApi']);
Route::prefix('pokemons')->group(function () {
    Route::get('/', [PokemonController::class, 'index']);
    Route::get('/{poke_id}', [PokemonController::class, 'show']);
    // Route::post('/import', [PokemonImportController::class, 'run']);
});
