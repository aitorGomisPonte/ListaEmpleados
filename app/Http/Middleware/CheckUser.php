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

        $datos = $request->getContent(); //Nos recibimos los datos por el body
        $datos = json_decode($datos); //Decodificamos el json para poder ver los distintos componentes
        
       try {
          $token = $datos->api_token;
          if($token){
              $user = User::Where($token,"api_token")->first();
              if($user){
                  switch ($user->puesto) {
                      case 'Directivo'||"RRHH":
                          $permiso = true;
                          break;  
                      default:
                          $permiso = false;
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
        }
        return response()->json($respuesta);
       
    }
}
