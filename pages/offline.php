<?php
if (!defined('LATINSHOP')) define('LATINSHOP', true);
$page_title = 'Sin conexión — Latin Shop';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #04080d;
            color: #e8f4fb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
        }
        .offline-wrap { max-width: 440px; }
        .offline-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            display: block;
            opacity: .7;
        }
        h1 { font-size: 1.8rem; margin-bottom: .75rem; color: #e8f4fb; }
        p  { color: #5a8aaa; line-height: 1.7; margin-bottom: 2rem; }
        .btn {
            display: inline-block;
            background: #a855f7;
            color: #fff;
            padding: .75rem 2rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            cursor: pointer;
            border: none;
            font-size: 1rem;
        }
        .btn:hover { opacity: .85; }
    </style>
</head>
<body>
    <div class="offline-wrap">
        <span class="offline-icon">📡</span>
        <h1>Sin conexión</h1>
        <p>No tienes conexión a internet en este momento. Algunas páginas que visitaste anteriormente pueden estar disponibles.</p>
        <button class="btn" onclick="window.location.reload()">Intentar de nuevo</button>
    </div>
</body>
</html>
