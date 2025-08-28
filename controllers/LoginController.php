<?php

namespace Controllers;

use MVC\Router;
use Model\Usuario;
use Classes\Email;

class LoginController
{
    public static function login(Router $router)
    {
        $alertas = [];
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $auth = new Usuario($_POST);
            $alertas = $auth->validarLogin();
            if(empty($alertas)) {
                //Comprobar que el usuario exista
                $usuario = Usuario::where('email',$auth->email);
                if($usuario){
                    //Verificar que el usuario este activo
                    if($usuario->comprobarPasswordAndVerificado($auth->password)){
                        // Autenticar el usuario
                        session_start();
                        $_SESSION['id'] = $usuario->id;
                        $_SESSION['nombre'] = $usuario->nombre . " " . $usuario->apellido;
                        $_SESSION['email'] = $usuario->email;
                        $_SESSION['login'] = true;
                        //Redireccionar al usuario
                        if($usuario->admin==="1"){
                            $_SESSION['admin'] = $usuario->admin ?? null;
                            header('Location: /admin');
                        }else{
                            header('Location: /cita');
                        }
                    }
                }else{
                    Usuario::setAlerta('error','El Usuario no existe');
                }
            }
        }
        $alertas = Usuario::getAlertas();
        $router->render('auth/login',[
            'alertas' => $alertas,
        ]);
    }
    public static function logout()
    {
        @session_start();
        $_SESSION = [];
        header('Location: /');
    }
    public static function olvide(Router $router)
    {
        $alertas = [];
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $auth = new Usuario($_POST);
            $alertas = $auth->validarEmail();
            if(empty($alertas)) {
                $usuario = Usuario::where('email',$auth->email);
                if($usuario && $usuario->confirmado === '1'){
                    // Generar un Token único
                    $usuario->crearToken();
                    $usuario->guardar();
                    //Enviar email con el link para recuperar contraseña
                    $email = new Email($usuario->nombre, $usuario->email, $usuario->token);
                    $email->enviarInstrucciones();
                    // Alerta de Exito
                    Usuario::setAlerta('exito','Te hemos enviado un link para recuperar tu contraseña');

                }else{
                    Usuario::setAlerta('error','El Usuario no existe o no esta confirmado');
                }
            }
        }
        $alertas = Usuario::getAlertas();
        $router->render('auth/olvide-password', [
            'alertas' => $alertas,
        ]);
    }
    public static function recuperar(Router $router)
    {
        $alertas = [];
        $error = false;
        $token = s($_GET['token']);
        //Buscar el usuario por Token
        $usuario = Usuario::where('token',$token);
        if(empty($usuario)){
            Usuario::setAlerta('error','El Token no es válido');
            $error = true;
        }
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            // leer el nuevo password y guardarlo
            $password = new Usuario($_POST);
            $alertas = $password->validarPassword();
            if(empty($alertas)) {
                $usuario->password = null;
                $usuario->password = $password->password;
                $usuario->hashPassword();
                $usuario->token = null;
                $resultado = $usuario->guardar();
                if($resultado){
                    header('Location: /');
                }
            }
        }
        $alertas = Usuario::getAlertas();
        $router->render('auth/recuperar-password', [
            'alertas' => $alertas,
            'error' => $error,
        ]);
    }
    public static function crear(Router $router)
    {
        $usuario = new Usuario;
        $alertas = [];
        //Alertas Vacias
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario->sincronizar($_POST);
            $alertas = $usuario->validarNuevaCuenta();
            // Revisar que alertas este vacio
            if(empty($alertas)) {
                // Verificar que el usuario no exista
                $resultado = $usuario->existeUsuario();

                if($resultado -> num_rows) {
                    $alertas = Usuario::getAlertas();    
                } else {
                    // Hashear el Password
                    $usuario->hashPassword();
                    // Generar un Token único
                    $usuario->crearToken();
                    // Enviar el email
                    $email = new Email($usuario->nombre, $usuario->email, $usuario->token);
                    $email->enviarConfirmacion();
                    // Crear el usuario
                    $resultado = $usuario->guardar();
                    if($resultado){
                        header('Location: /mensaje');
                    }
                    
                }
            }
        }
        $router->render('auth/crear-cuenta', [
            'usuario' => $usuario,
            'alertas' => $alertas,
        ]);
    }
    public static function mensaje(Router $router){
        $router->render('auth/mensaje');
    }
    public static function confirmar(Router $router){
        $alertas = [];
        $token = s($_GET['token']);
        $usuario = Usuario::where('token',$token);
        if(empty($usuario)){
            // Mostrar mensaje de error
            Usuario::setAlerta('error','Token no válido o expirado. Por favor, solicite una nueva confirmación.');
        } else{
            //Modificar a usuario confirmado
            $usuario->confirmado = '1';
            $usuario->token = 'NULL';
            $usuario->guardar();
            Usuario::setAlerta('exito','Cuenta confirmada correctamente. Ahora puede iniciar sesión.');
        }
        //Obtener Alertas para mostrarlas en la vista
        $alertas = Usuario::getAlertas();
        //Renderizar la vista con las alertas
        $router->render('auth/confirmar-cuenta', [
            'alertas' => $alertas
        ]);
    }
}
                                        