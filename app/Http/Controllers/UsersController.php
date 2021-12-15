<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UsersController extends Controller
{
   
    /*En esta funcion hacemos el log in de los usuarios:
     -primero: Recibimos los datos por el body, lo decodificamos de json
     -segundo: usamos el validator para comprobar lo recibido
     -tercero: una vez comprobado, buscamos el email y la contraseña, y si estas coinsiden
     -cuarto: si es asi, nos creamos un nuevo token, guardado en la tabla para que se pueda usar durante un tiempo
     -quinto: */
    public function logIn(Request $req){

        $respuesta = ["status" => 1,"msg" => ""];//Usamos esto para comunicarnos con el otro lado del servidor

        $datos = $req->getContent(); //Nos recibimos los datos por el body
        $datos = json_decode($datos); //Decodificamos el json para poder ver los distintos componentes
        try {//Encapsulamos las consultasal servidor por si perdemos la conexion
            $trabajador = User::where("email",$datos->email)->first();//Buscamos el trabajador por su email
            if($trabajador){ //Comprobamos que se halla encontrado un trabajador
                if( $trabajador->password == $datos->password){//Si es asi comprobamos la contraseña de este 
                    $token = $this->crearToken($trabajador);//Si todo va bien entonces nos creamos un token usando la funcion de crear token
                    $trabajador->api_token = $token;//Nos guardamos la token en el json 
                    $trabajador->save();//Guardamos el nuevo Json en la tabla

                } else{//Si la contraseña no coincide entonces llegamos aqui
                    $respuesta['msg'] = "La contraseña no coincide con la contraseña del usuario";
                    $respuesta['status'] = 0;
                }
             }else{//Si el email no existe entonces llegamos aqui
                $respuesta['msg'] = "El email no existe o esta mal escrito";
                $respuesta['status'] = 0; 
             }
        } catch (\Exception $e) {//Fallo en el try(posible fallo con la conexion del servidor)
            $respuesta['msg'] = $e->getMessage();
            $respuesta['status'] = 0;
        }       
        return response()->json($respuesta);//Nos devolbemos una respuesta con un mensaje
    }
    /*Funcion encargada de hacer kas token*/
    private function crearToken($trabajador){

        $tokenAux = $trabajador->email;//Aprovechamos que el email y el id son unicos para crearnos una token unica
        $posiblesNumeros = [0,1,2,3,4,5,6,7,8,9];
        for ($i=0; $i < 6; $i++) {
            $tokenAux .= $posiblesNumeros[array_rand($posiblesNumeros)];
        }
        return md5($tokenAux);//Encriptamos con md5 el token para no tener problams en los json o rutas 
    }
}

 
