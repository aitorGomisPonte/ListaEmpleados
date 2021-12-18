<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

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
                    $respuesta['msg'] = "Se ha echo el login, apitoken creada";
                    $respuesta['status'] = 1;
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
    private function permisos($datos){//Nos comprobamois los permisoso para saber si es Directivo o RRHH, hacemos una funcion ya que esto se repite en varias funciones
        $permisos = 0;
        try {
            $empleado = User::where('api_token',$datos->api_token)->first();

            if($empleado->puesto == "Directivo"){
                $permisos = 2;
            }else{
                $permisos = 1;
            }
        } catch (\Exception $e) {
            $respuesta['msg'] = $e->getMessage();
            $respuesta['status'] = 0;
        }
       
        return $permisos;
    }
    public function registro(Request $req){
        $respuesta = ["status" => 1,"msg" => ""];//Usamos esto para comunicarnos con el otro lado del servidor

        $datos = $req->getContent(); //Nos recibimos los datos por el body
        $datos = json_decode($datos);
        
        $validator = Validator::make(json_decode($req->getContent(),true),[
            'name' => "required",
            'email' => "required|unique:users",
            'biografia' => "required",
            'password' => "required ",
            'puesto' => "required",
            'salario'=> "required",
            
            
            ]);
            //Comporbamos el estado del validador
            if($validator->fails()){
                $respuesta['msg'] = "Ha habido un fallo con los datos introducidos";
                $respuesta['status'] = 0;    
                
            }else{
                try {
                    $user = new User();
                    $user->name = $datos->name;
                    $user->email = $datos->email;
                    $user->biografia = $datos->biografia;
                    $user->password = $datos->password;
                    $user->puesto = $datos->puesto;
                    $user->salario= $datos->salario;
                    $user->save();
                    $respuesta['msg'] = "Se ha registrado el nuevo usuario, con nombre: ".$datos->name;
                    $respuesta['status'] = 1;  
                } catch (\Exception $e) {
                    $respuesta['msg'] = $e->getMessage();
                    $respuesta['status'] = 0; 
                }
            }
        return response()->json($respuesta);
    }
    public function listaEmpleados(Request $req){
        $respuesta = ["status" => 1,"msg" => ""];//Usamos esto para comunicarnos con el otro lado del servidor

        $datos = $req->getContent(); //Nos recibimos los datos por el body
        $datos = json_decode($datos); //Decodificamos el json para poder ver los distintos componentes

        $permisos = $this->permisos($datos);

        switch ($permisos) {
            case '1':
                try {
                    $empleados = User::where('puesto',"Empleado")->get();
                    foreach ($empleados as $empleado) {//Por cada video de este curso, nos hacemos una ejecucuion de esta secion de codigo para comprobar si este usuario lo ha visto
                        array_push($respuesta, "Nombre: ".$empleado->name, "Puesto: ".$empleado->puesto,"Salario: ".$empleado->salario); 
                    }
                    $respuesta['status'] = 1;
                    $respuesta['msg'] = "Se han listado los empleados " ;
                } catch (\Exception $e) {
                    $respuesta['msg'] = $e->getMessage();
                    $respuesta['status'] = 0;
                }
                break;
            case '2':
                try {
                    $empleados = User::where('puesto',"Empleado")->orWhere('puesto', 'RRHH')->get();
                    foreach ($empleados as $empleado) {//Por cada video de este curso, nos hacemos una ejecucuion de esta secion de codigo para comprobar si este usuario lo ha visto
                        array_push($respuesta, "Nombre: ".$empleado->name, "Puesto: ".$empleado->puesto,"Salario: ".$empleado->salario); //$respuesta .= $empleado->name. ", ".$empleado->puesto.", ".$empleado->salario."\n";  
                    }
                    $respuesta['status'] = 1;
                    $respuesta['msg'] = "Se han listado los empleados y RRHH " ;
                } catch (\Exception $e) {
                    $respuesta['msg'] = $e->getMessage();
                    $respuesta['status'] = 0;
                }
                break;
            default:
                $respuesta['status'] = 0;
                $respuesta['msg'] = "Se ha producido un fallo con los perimisos" ;
                break;
        }

        return response()->json($respuesta);//Nos devolbemos una respuesta con un mensaje
    }
}

 
