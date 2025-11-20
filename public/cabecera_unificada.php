<?php
// public/cabecera_unificada.php - Sistema unificado de cabecera
require_once '../includes/init.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?? 'Tercer Cielo' ?> - Tienda Online</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicon/apple-touch-icon.png">
    <link rel="manifest" href="../assets/favicon/site.webmanifest">
    <!-- Estilos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Roboto:wght@300;400&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #007bff;
            --secondary: #0056b3;
            --light: #f8f9fa;
            --dark: #212529;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: #f5f5f5;
        }

        .navbar-brand {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 1.8rem;
        }

        /* Animaciones globales */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-in {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>
<body>
    <!-- El navbar se maneja en navbar.php -->
    <?php include 'navbar.php'; ?>

    <!-- CARRITO LATERAL -->
    <?php include 'carrito_lateral.php'; ?>