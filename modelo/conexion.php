<?php

class conexion{
    public static function conectar(){
        $nombreServidor = "localhost";
        $usuariosServidor = "root";

        // nombre del a base de datos
        $baseDatos = "";

        $password = "";

        try {
            $conexion = new PDO('mysql:host='.$nombreServidor.';dbname='.$baseDatos.';',$usuariosServidor,$password);
            $conexion->exec("set names utf8");
        } catch (Exception $e) {
            $conexion = $e;
           
        }

        return $conexion;

    }
}