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
            'email' => "required|unique:users|email:rfc,dns",
            'biografia' => "required",
            'password' => "required|regex:/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}/ ",
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
                    $empleados = User::select("name", "puesto", "salario")->where('puesto',"Empleado")->get();
                    $respuesta = $empleados;
                    $respuesta['status'] = 1;
                    $respuesta['msg'] = "Se han listado los empleados " ;
                } catch (\Exception $e) {
                    $respuesta['msg'] = $e->getMessage();
                    $respuesta['status'] = 0;
                }
                break;
            case '2':
                try {
                    $empleados = User::select("name", "puesto", "salario")->where('puesto',"Empleado")->orWhere('puesto', 'RRHH')->get();
                    $respuesta = $empleados;
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
    }/*En esta funcion damos los detalles de un empleado recibiendo apiToken y el id de usuario*/
    public function detallesEmpleado(Request $req){
        $respuesta = ["status" => 1,"msg" => ""];//Usamos esto para comunicarnos con el otro lado del servidor

        $datos = $req->getContent(); //Nos recibimos los datos por el body
        $datos = json_decode($datos); //Decodificamos el json para poder ver los distintos componentes

        $validator = Validator::make(json_decode($req->getContent(),true),[
            'id' => "required"
            ]);
            //Comporbamos el estado del validador
            if($validator->fails()){
                $respuesta['msg'] = "Ha habido un fallo con los datos introducidos";
                $respuesta['status'] = 0;         
            }else{
                $permisos = $this->permisos($datos);  
                try {
                    $empleado = User::select("name","email", "puesto", "biografia", "salario" )->where("id", $datos->id)->first();
                    if($empleado){
                        if($empleado->puesto == "Directivo"){
                            $respuesta['status'] = 0;
                            $respuesta['msg'] = "No se puede hacer esta accion ya que no se pueden ver los detalles de los directivos" ;
                        }else if($empleado->puesto == "RRHH"){
                            if($permisos > 1){
                                $respuesta = $empleado;
                                $respuesta['status'] = 1;
                                $respuesta['msg'] = "Se han listado los datos del empleado de RRHH";
                            }else{
                                $respuesta['status'] = 0;
                                $respuesta['msg'] = "No se tiene permiso para acceder a la informacion de este empleado";
                            }
                        }else{
                            $respuesta = $empleado;
                            $respuesta['status'] = 1;
                            $respuesta['msg'] = "Se han listado los datos del empleado";
                        }
                    }else{
                        $respuesta['status'] = 0;
                        $respuesta['msg'] = "El id de empleado no existe, ".$datos->id;
                    }
                } catch (\Exception $e) {
                    $respuesta['msg'] = $e->getMessage();
                    $respuesta['status'] = 0; 
                 }
            }
            return response()->json($respuesta);     
    }
    public function verPerfil(Request $req){
        $respuesta = ["status" => 1,"msg" => ""];//Usamos esto para comunicarnos con el otro lado del servidor

        $datos = $req->getContent(); //Nos recibimos los datos por el body
        $datos = json_decode($datos);
        $validator = Validator::make(json_decode($req->getContent(),true),[
            'api_token' => "required",
           
            ]);
            //Comporbamos el estado del validador
            if($validator->fails()){
                $respuesta['msg'] = "Ha habido un fallo con los datos introducidos";
                $respuesta['status'] = 0;     
            }else{
                try {
                    $empleado = User::where("api_token",$datos->api_token)->first();
                    if($empleado){
                        $respuesta = $empleado;
                        $respuesta['status'] = 1;
                        $respuesta['msg'] = "Se han listado los datos del empleado";
                    }else{
                        $respuesta['status'] = 0;
                        $respuesta['msg'] = "El empleado no existe";
                    }
                } catch (\Exception $e) {
                    $respuesta['msg'] = $e->getMessage();
                    $respuesta['status'] = 0; 
                }
            }
        return response()->json($respuesta); 
    }
    public function modificarDatos(Request $req){
        $respuesta = ["status" => 1,"msg" => ""];//Usamos esto para comunicarnos con el otro lado del servidor
        $modificar = false;
        $datos = $req->getContent(); //Nos recibimos los datos por el body
        $datos = json_decode($datos);
        try {
            $empleado = User::where("id",$datos->id)->first();
            $permiso = $this->permisos($datos); 
            if($empleado){  
                switch ($empleado->puesto) {
                    case 'Directivo':
                        $changer = User::where("api_token",$datos->api_token)->first();
                        if($changer->id == $empleado->id){
                            $modificar = true;
                        }else{
                            $respuesta['status'] = 0;
                            $respuesta['msg'] = "No se pueden modificar estos datos";
                        }
                        break;
                    case 'RRHH':
                        if($permiso > 1){
                            $modificar = true;
                        }else{
                            $respuesta['status'] = 0;
                            $respuesta['msg'] = "No se pueden modificar estos datos";
                        }
                        break; 
                    default:
                        $modificar = true; 
                        break;
                }    
            }else{
                $respuesta['status'] = 0;
                $respuesta['msg'] = "El empleado no existe";
            }
        } catch (\Exception $e) {
            $respuesta['msg'] = $e->getMessage();
            $respuesta['status'] = 0;
        }
        if($modificar){
            $validator = Validator::make(json_decode($req->getContent(),true),[
                'name' => "String",
                'email'=> "unique:users|email:rfc,dns",
                'biografia' => "",
                'password' => "regex:/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}/",//regex:(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$
                'puesto' => "",
                'salario'=> "integer",
    
               
            ]);
                //Comporbamos el estado del validador
            if($validator->fails()){
                $respuesta['msg'] = "Ha habido un fallo con los datos introducidos";
                $respuesta['status'] = 0;     
            }else{
                try {
                    if(isset($datos->name)){
                        $empleado->name = $datos->name;
                    }
                    if(isset($datos->email)){
                        $empleado->email = $datos->email;
                    }
                    if(isset($datos->password)){
                        $empleado->password = $datos->password;
                    }
                    if(isset($datos->biografia)){
                        $empleado->biografia = $datos->biografia;
                    }
                    if(isset($datos->puesto)){
                        $empleado->puesto = $datos->puesto;
                    }
                    if(isset($datos->salario)){
                        $empleado->salario = $datos->salario;
                    }
                    $empleado->save();
                    $respuesta['msg'] = "Se ha modificado los datos del usuario con id: ".$empleado->id;
                    $respuesta['status'] = 1; 
                } catch (\Exception $e) {
                    $respuesta['msg'] = $e->getMessage();
                    $respuesta['status'] = 0;
                }   
            }
        }
        return response()->json($respuesta);
    }
}

 
