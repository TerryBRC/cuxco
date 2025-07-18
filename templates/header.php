<?php
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cuxco - <?php echo $title ?? ''; ?></title>
    <link id="theme-stylesheet" rel="stylesheet" href="../../assets/css/style.css"> <!-- Hoja de estilo por defecto -->
</head>
<body>
    <header>
        <a href="../clientes/clientes.php">
            <img src="../../assets/images/logo.png" alt="logo.png" class="logo"> 
        </a>
        <h1>CUXCO</h1>  
        <nav>
            <a href="../clientes/clientes_atrasados.php">
                <span></span>
                <span></span>
                <span></span>
                <span></span>
                Clientes Atrasados
            </a>
            <button id="toggle-theme">Modo Oscuro</button> <!-- Botón para cambiar de tema -->
        </nav>
    </header>
    <main>
