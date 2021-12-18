<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;

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

Route::middleware('check-user')->group(function(){
Route::prefix('user')->group(function(){
 Route::put('/login',[UsersController::class, 'logIn'])->withoutMiddleware("check-user");
 Route::put('/registro',[UsersController::class, 'registro']);
 Route::get('/listar',[UsersController::class, 'listaEmpleados']);
 Route::get('/detalles',[UsersController::class, 'detallesEmpleado']);
 Route::get('/verPerfil',[UsersController::class, 'verPerfil'])->withoutMiddleware("check-user");
 Route::post('/modificar',[UsersController::class, 'modificarDatos']);

       });
});
