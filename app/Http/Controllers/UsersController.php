<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\NuevaPassword;

class UsersController extends Controller
{
   
    /*En esta funcion hacemos el log in de los usuarios:
     -primero: Recibimos los datos por el body, lo decodificamos de json
     -segundo: usamos el validator para comprobar lo recibido
     -tercero: una vez comprobado, buscamos el email y la contraseña, y si estas coinsiden
     -cuarto: si es asi, nos creamos un nuevo token, guardado en la tabla para que se pueda usar durante un tiempo(en nustro caso no tenemos que se caduque, asi que sirve hasta que se ha ga otro login)
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
                    $respuesta["api_token"] = $token;
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
    /*Funcion encargada de hacer kas token:
     -primero: nos creamos un numero de 6 cifras
     -segundo: usamos la codificacion md5 ya que no queremos que se introduzcan caracteres especiales,
    ademas de que queremos que el numeor sea mas complicado de repetirse por lo que al ponerlo por una codificacion aumentamos la posibilidad de resultados*/
    private function crearToken($trabajador){

        $tokenAux = $trabajador->email;//Aprovechamos que el email y el id son unicos para crearnos una token unica
        $posiblesNumeros = [0,1,2,3,4,5,6,7,8,9];//Array de numeros 
        for ($i=0; $i < 6; $i++) {//Hscemos este for 6 veces para selecionar 6 numeros random
            $tokenAux .= $posiblesNumeros[array_rand($posiblesNumeros)];//Lo añadimos a un string, array rand para numero random
        }
    return md5($tokenAux);//Encriptamos con md5 el token para no tener problams en los json o rutas 
    }
    /*Esta funcion es la encargada de comporbar los permisos tras el primer middelware.
     -primero: nos creamos la variable permisos: 1 = RRHH, 2 = Directivo, 0 = fallo con los permisos
     -segundo: primero nos buscamos un empleado con esta api para asegurarse de que no ha habido fallos (esto es un poco inecesario ya que se comprueba esto em el MID)
     -tercero: comprobamos su puesto y asignamos los permisos corresponddientes */
    private function permisos($datos){//Nos comprobamois los permisoso para saber si es Directivo o RRHH, hacemos una funcion ya que esto se repite en varias funciones
        $permisos = 0;
        try {
            $empleado = User::where('api_token',$datos->api_token)->first();//Buscamos un empleado con esta apiToken

            if($empleado->puesto == "Directivo"){//Si es directivo, le damos todos los permisos, sino le damos solo 1 (no hace falta comporbar si es RRHH ya que si no es uno tiene que se el otro)
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
    /*En esta funcion realicamos el regustro de nuevos trabajadores:
     -primero: usando un validator, comprobamos el estado de los datos que se nos han pasado para crear el nuevo empleado
     -segundo: tras comprobar si el validator esta correcto, guardmos todos los datos creando un usuario nuevo */
    public function registro(Request $req){
        $respuesta = ["status" => 1,"msg" => ""];//Usamos esto para comunicarnos con el otro lado del servidor

        $datos = $req->getContent(); //Nos recibimos los datos por el body
        $datos = json_decode($datos);
        
        $validator = Validator::make(json_decode($req->getContent(),true),[//Este es el validator, dodne comprobamos la validez de los datos introducidos en un json
            'name' => "required",//Obligatorio
            'email' => "required|unique:users|email:rfc,dns",//Obligatorio, unico en la tabla de usuarios, cumple una estructura especifica (email:rfc, dns)
            'biografia' => "required",//Obligatorio
            'password' => "required|regex:/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}/ ",//Obligatorio, 8 cifras mayusculas minusculas y numeros obligatorios
            'puesto' => "required",//Obligatorio
            'salario'=> "required",//Obligatorio
            
            
            ]);
            //Comporbamos el estado del validador
            if($validator->fails()){
                $respuesta['msg'] = "Ha habido un fallo con los datos introducidos";
                $respuesta['status'] = 0;    
                
            }else{//Si no ha habido ningun fallo, hacemops la crecion del objeto y lo guardamos en la base de datos
                try {
                    $user = new User();
                    $user->name = $datos->name;
                    $user->email = $datos->email;
                    $user->biografia = $datos->biografia;
                    $user->password = $datos->password;
                    $user->puesto = $datos->puesto;
                    $user->salario= $datos->salario;
                    $user->save();
                    $respuesta['msg'] = "Se ha registrado el nuevo usuario, con nombre: ".$datos->name;//Nos devolbemos un mensaje para saber quien se ha guardado (util para comprobar)
                    $respuesta['status'] = 1;  
                } catch (\Exception $e) {
                    $respuesta['msg'] = $e->getMessage();
                    $respuesta['status'] = 0; 
                }
            }
        return response()->json($respuesta);
    }
    /*En esta funcion nos mostramos una lista de empleados, solo accesible por DIrectivos y RRHH (directivos tamben tiene acceso a lista de RRHH):
     -primero: nos comprobamos los permisos de la persona que ha accedido
     -segundo: si es RRHH(p = 1), imprimimos una lista de los empleados, si es Directivo(p = 2) tambien imprimimos los empleados de RRHH */
    public function listaEmpleados(Request $req){
        $respuesta = ["status" => 1,"msg" => ""];//Usamos esto para comunicarnos con el otro lado del servidor

        $datos = $req->getContent(); //Nos recibimos los datos por el body
        $datos = json_decode($datos); //Decodificamos el json para poder ver los distintos componentes

        $permisos = $this->permisos($datos);//Comprobamos los permisos

        switch ($permisos) {//Switch con los permisos
            case '1'://SI el permiso es solo 1
                try {
                    $empleados = User::select("name", "puesto", "salario")->where('puesto',"Empleado")->get();//Hacemos un objeto cos todos los usuarios que son empleados
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
                    $empleados = User::select("name", "puesto", "salario")->where('puesto',"Empleado")->orWhere('puesto', 'RRHH')->get();//Hacemos un objeto con todos los usuarios qeu son empleados u RRHH
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
    }/*En esta funcion damos los detalles de un empleado recibiendo apiToken y el id de usuario:
      -primero: validamos los datos que se nos dan desde el json (nos tiene que pasar el id de alguien)
      -segundo: comprobamos los los permisos
      -tercero: buscamos los datos de este empleado(solo los datos que queremos recibir)
      -cuarto: comprobamos el puesto de este empleado : si es directivo no lo podemos ver, si es RRHH comprobamos los permisis, si es empleado se muestran los datos */
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
                $permisos = $this->permisos($datos);//Comprobamos los permisos   
                try {
                    $empleado = User::select("name","email", "puesto", "biografia", "salario" )->where("id", $datos->id)->first();//Buscamos a la persona que compla el where, pero solo los datos del select
                    if($empleado){
                        if($empleado->puesto == "Directivo"){//Comprobamos si el puesto de este empleado es DIrectivo, de ser asi no se tiene permisos
                            $respuesta['status'] = 0;
                            $respuesta['msg'] = "No se puede hacer esta accion ya que no se pueden ver los detalles de los directivos" ;
                        }else if($empleado->puesto == "RRHH"){//Si el puesto de este empleado es RRHH, comprobamos si teiene los permosos para aceder aqui
                            if($permisos > 1){
                                $respuesta = $empleado;
                                $respuesta['status'] = 1;
                                $respuesta['msg'] = "Se han listado los datos del empleado de RRHH";
                            }else{
                                $respuesta['status'] = 0;
                                $respuesta['msg'] = "No se tiene permiso para acceder a la informacion de este empleado";
                            }
                        }else{//Si no es uno de estos, entonces se entiende que es un empleado, por lo que se dan los datos (ya que el middelware comprueba que solo acceda qui un DI o RRHH)
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
    /*En esta funcion recibimos el apitoken de la persona logeada y le devolbemos toda su informacion
     -primero: comprobamos que se ha enviado el apitoken
     -segundo: comprobamos si el apitoken existe
     -tercero: si es asi se imprimen los datos de este empleado */
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
                    $empleado = User::where("api_token",$datos->api_token)->first();//Buscmaos el empleado con este apitoken
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
    /*En esta funcion recbimos una apitoken, y un id a modificar para midificar la informacion de este usuario:
      -primero: comprobamos que el usuario exista
      -segundo: comprobamos los permisos 
      -tercero: entramos en un switch dependiendo de el puesto del usuario que queremos midificar:
        -Si el puesto es directivo: comprobamos si la persoma accediendo es este miso, de no ser asi no hay permisios
        -Si es recursos humanos comprobamos los permisos, si es 2 (Directivo) se puede mofica, de nos se asi no
        -SI no es uno de estos se dan permisos ya que se asume que es un empleado
      -cuarto: si modificar es true, entonces validamos los datos que se nos han aportado (json)
      -quinto: si los datos esta correctos, modificamos dichos datos que se nos han aportado y guardamos denuevo en la base de datos*/
    public function modificarDatos(Request $req){
        $respuesta = ["status" => 1,"msg" => ""];//Usamos esto para comunicarnos con el otro lado del servidor
        $modificar = false;
        $datos = $req->getContent(); //Nos recibimos los datos por el body
        $datos = json_decode($datos);
        try {
            $empleado = User::where("id",$datos->id)->first();//*Al usar furst recibimos un objeto no un array asociado por lo que si esta vacio sabemos que no existe en la BBDD
            $permiso = $this->permisos($datos); //Comprobamos los permisos
            if($empleado){//Comprobamos que le empleado exista  
                switch ($empleado->puesto) {//Switfch usando el puesto del empleado el cual queremos modificar
                    case 'Directivo'://Si es directivo comprobamos si es el mismo quien quere cambiar sus datos, sis es asi se le permite
                        $changer = User::where("api_token",$datos->api_token)->first();//Podemos mejorar esto mirarndo a la apitoken de ambis sin tener que buscar el id
                        if($changer->id == $empleado->id){//*Cambiar medianto uso del apitokeb si los dos coinsiden es que s lamisma persona
                            $modificar = true;//Se consigue permiso si es uno mismo
                        }else{
                            $respuesta['status'] = 0;
                            $respuesta['msg'] = "No se pueden modificar estos datos";
                        }
                        break;
                    case 'RRHH'://Si es un empleado de recursos humanos comprobamos los permisis de "changer"
                        if($permiso > 1){//Si es mayor que uno (2) sabemos que es un directio por lo que se le da permiso
                            $modificar = true;
                        }else{
                            $respuesta['status'] = 0;
                            $respuesta['msg'] = "No se pueden modificar estos datos";
                        }
                        break; 
                    default://Si no es uno de se entiende que es un empleado por lo que se le da permiso
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
        if($modificar){//Validamos los datos que queremos modificar ya que estos tiene queseguir una estructura especifica 
            $validator = Validator::make(json_decode($req->getContent(),true),[
                'name' => "String",
                'email'=> "unique:users|email:rfc,dns",
                'biografia' => "",
                'password' => "regex:/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}/",//8 cifras mayusculas minusculas y numeros obligatorios
                'puesto' => "in:Profesional,Particular,Administrador",
                'salario'=> "integer",
    
               
            ]);
                //Comporbamos el estado del validador
            if($validator->fails()){
                $respuesta['msg'] = "Ha habido un fallo con los datos introducidos";
                $respuesta['status'] = 0;     
            }else{
                try {
                    if(isset($datos->name)){//Primero comprobamos si le hemos pasado este dato por el json, si es asi lo guarda en el dato del objeto
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
                    $respuesta['msg'] = "Se ha modificado los datos del usuario con id: ".$empleado->id;//Imprimimos el id de usuario para en las pruebas sabert que ha funcionado bien
                    $respuesta['status'] = 1; 
                } catch (\Exception $e) {
                    $respuesta['msg'] = $e->getMessage();
                    $respuesta['status'] = 0;
                }   
            }
        }
        return response()->json($respuesta);
    }
    /*En esta funcion recuperamos la contraseña de un usuario mediante su email, y hacemos una nueva que se envia a este email introducido
     -primero: comprobamos si el usuario existe
     -segundo: hacemos una nueva contraseña usando la funcion de hacer contraseña
     -tercero: guardamos la contraseña en al base de datos */
    public function recuperarPass(Request $req){
        $respuesta = ["status" => 1,"msg" => ""];//Usamos esto para comunicarnos con el otro lado del servidor
        $datos = $req->getContent(); //Nos recibimos los datos por el body
        $datos = json_decode($datos);
        try {
           $empleado = User::where("email",$datos->email)->first();//Buscamos al usuario (usamos first para que nos devuelba un objeto no un array)
           if($empleado){
               $pass = $this->automaticPass();//Nos creamos una contraseña nueva
               Mail::to($empleado->email)->send(new NuevaPassword("Cambio de contraseña","Nueva Contraseña", "La contraseña del usuario ha sido cambiada a: ".$pass));//Enviamos un mail
               $empleado->password = $pass;//Cambiamos la contraseña
               $empleado->save();//Guardamos los cambios
               $respuesta['msg'] = "La contraseña del usuario ha sido cambiada ";
               $respuesta['status'] = 1; 
                  
           }else{
                $respuesta['msg'] = "El usuario no existe";
                $respuesta['status'] = 0; 
           }
        } catch (\Exception $e) {
            $respuesta['msg'] = $e->getMessage();
            $respuesta['status'] = 0;
        }
        return response()->json($respuesta);
    }
    /*Creacion de la contraseña automatica */
    private function automaticPass(){
        $controlEx = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}/";
        $password = "";
        do {
            $option = rand(1,3);
            switch ($option) {
                case '1'://Numbers
                    $aux = rand(48,57);
                    $password .= chr($aux);
                    break;
                case '2'://Upper case
                    $aux = rand(65,90);
                    $password .=chr($aux);
                    break;
                case '3'://Lower case
                    $aux = rand(97,122);
                    $password .= chr($aux);
                    break;    
                default://No necesitamos caracteres especiales, pero si hay algu fallo en el switch lo sabremos is estan estos presentes
                    $aux = rand(33,46);
                    $password .= chr($aux);
                    break;
            }              
        } while((preg_match($controlEx, $password) === 0));// Comprobamos is la contraseña construida cumple con los requisitos
    return $password;
    }
}

 
