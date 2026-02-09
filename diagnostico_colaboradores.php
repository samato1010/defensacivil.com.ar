<?php
// Diagnóstico de colaboradores_admin.php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>Diagnóstico de Colaboradores Admin</h1>";

echo "<h2>1. Verificando archivos necesarios</h2>";

// config.php
$config_path = __DIR__ . '/config.php';
echo "config.php: ";
if (file_exists($config_path)) {
    echo "✅ Existe<br>";
    require_once $config_path;
    echo "✅ Se cargó correctamente<br>";
} else {
    echo "❌ NO EXISTE en: $config_path<br>";
}

// security.php
$security_path = __DIR__ . '/security.php';
echo "<br>security.php: ";
if (file_exists($security_path)) {
    echo "✅ Existe<br>";
    require_once $security_path;
    echo "✅ Se cargó correctamente<br>";
} else {
    echo "❌ NO EXISTE en: $security_path<br>";
    echo "<strong>SOLUCIÓN:</strong> Necesitás instalar la Mejora 05 (CSRF Protection) primero<br>";
}

echo "<h2>2. Verificando constantes</h2>";

echo "COLAB_PRIVATE_JSON: ";
if (defined('COLAB_PRIVATE_JSON')) {
    echo "✅ Definida: " . COLAB_PRIVATE_JSON . "<br>";
    if (file_exists(COLAB_PRIVATE_JSON)) {
        echo "✅ El archivo existe<br>";
    } else {
        echo "❌ El archivo NO existe<br>";
    }
} else {
    echo "❌ NO definida (usar ruta por defecto)<br>";
}

echo "<br>COLAB_PUBLIC_JSON: ";
if (defined('COLAB_PUBLIC_JSON')) {
    echo "✅ Definida: " . COLAB_PUBLIC_JSON . "<br>";
    if (file_exists(COLAB_PUBLIC_JSON)) {
        echo "✅ El archivo existe<br>";
    } else {
        echo "⚠️ El archivo NO existe (se creará al aprobar colaboradores)<br>";
    }
} else {
    echo "❌ NO definida<br>";
}

echo "<h2>3. Verificando funciones necesarias</h2>";

$funciones_necesarias = [
    'require_admin',
    'csrf_token',
    'csrf_check',
    'json_read',
    'json_write_atomic',
    'log_security_event',
    'current_username',
    'sanitize_text'
];

foreach ($funciones_necesarias as $func) {
    echo "$func(): ";
    if (function_exists($func)) {
        echo "✅<br>";
    } else {
        echo "❌ NO EXISTE<br>";
    }
}

echo "<h2>4. Verificando sesión</h2>";

if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✅ Sesión activa<br>";
    echo "Usuario actual: ";
    if (function_exists('current_username')) {
        echo current_username() . "<br>";
    } else {
        echo "❌ No se puede obtener (función no existe)<br>";
    }
} else {
    echo "❌ No hay sesión activa<br>";
}

echo "<h2>5. Verificando permisos de archivos</h2>";

$storage_dir = __DIR__ . '/_storage';
echo "Directorio _storage: ";
if (is_dir($storage_dir)) {
    echo "✅ Existe<br>";
    if (is_writable($storage_dir)) {
        echo "✅ Es escribible<br>";
    } else {
        echo "❌ NO es escribible (permisos incorrectos)<br>";
    }
} else {
    echo "❌ NO existe<br>";
    echo "<strong>SOLUCIÓN:</strong> Crear /admin/_storage/ con permisos 755<br>";
}

echo "<h2>Resumen</h2>";
echo "<p>Si todo sale ✅, el archivo debería funcionar.</p>";
echo "<p>Si hay errores ❌, seguí las soluciones indicadas.</p>";

echo "<hr>";
echo "<a href='/admin/'>← Volver al admin</a>";
?>
