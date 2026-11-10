<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IndicadorController;

Route::get('/', function () {
    return response()->json([
        'message' => 'conectei'
    ]);
});

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/indicadores', [IndicadorController::class, 'index']);
    Route::put('/indicadores/{codigo_sgs}', [IndicadorController::class, 'update']);
    Route::delete('/indicadores/{codigo_sgs}', [IndicadorController::class, 'destroy']);
    Route::get('/indicadores/opcoes', [IndicadorController::class, 'opcoesIndicadores']);
    Route::get('/indicadores/filtrar', [IndicadorController::class, 'filtrar']);
    Route::post('/indicadores/sincronizar', [IndicadorController::class, 'sincronizar']);
});