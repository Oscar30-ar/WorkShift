<?php

include_once "vista/modulos/1cabesera.php";

if (isset($_GET["ruta"])) {

    $listaRutas = array("");
    if (isset($_GET["ruta"]) && in_array($_GET["ruta"], $listaRutas)) {
        include_once "vista/modulos/" . $_GET["ruta"] . ".php";
    }else{

    }

} else {
    
}

include_once "vista/modulos/zpie.php";
