<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class CheckUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $respuesta = ["status" => 1,"msg" => ""];//Usamos esto para comunicarnos con el otro lado del servidor
        $permiso = false;
        $datos = $request->getContent(); //Nos recibimos los datos por el body
        $datos = json_decode($datos); //Decodificamos el json para poder ver los distintos componentes
        
       try {
          $token = $datos->api_token;
          if($token){
              $user = User::Where("api_token",$token)->first();
              if($user){
                  switch ($user->puesto) {
                      case 'Empleado'://Queremos tener un control en el front donde nos aseguramos de que solo se pueda eleguir tres opciones (Empleado, Directivo, RRHH)
                          $permiso = false;
                          break;  
                      default:
                          $permiso = true;
                          break;
                  }
              }else{
                $respuesta['msg'] = "El token no existe";
                $respuesta['status'] = 0;
              }
          }
       } catch (\Exception $e) {
          $respuesta['msg'] = $e->getMessage();
          $respuesta['status'] = 0;
       }
        if($permiso){
            $respuesta['msg'] = "Permisos correctos";
            $respuesta['status'] = 1;      
            return $next($request);
        }else{
            $respuesta['msg'] = "Permisos no correctos";
            $respuesta['status'] = 0;
            return response()->json($respuesta);
        }
    }
}
