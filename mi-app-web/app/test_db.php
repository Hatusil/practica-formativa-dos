<?php
// Script simple para probar la conexión a la base de datos

// Configuración de la conexión
$host = 'mysql-db';
$user = 'root';
$password = 'mipassword';
$database = 'mibasededatos';

// Información del sistema
echo "<h2>Información del sistema:</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Servidor: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Sistema operativo: " . PHP_OS . "<br>";
echo "Extensiones cargadas: <pre>" . print_r(get_loaded_extensions(), true) . "</pre>";

// Prueba de DNS
echo "<h2>Prueba de resolución DNS:</h2>";
echo "Resolviendo $host... ";
$dns = gethostbyname($host);
echo "$dns<br>";

// Prueba todas las posibles combinaciones de host
$hosts = ['mysql-db', 'mysql', 'localhost', '127.0.0.1'];
$users = ['root', 'usuario'];
$passwords = ['mipassword', ''];

echo "<h2>Intentando todas las combinaciones posibles:</h2>";
echo "<table border='1'>
<tr>
    <th>Host</th>
    <th>Usuario</th>
    <th>Contraseña</th>
    <th>Resultado</th>
</tr>";

foreach ($hosts as $h) {
    foreach ($users as $u) {
        foreach ($passwords as $p) {
            echo "<tr>
                <td>$h</td>
                <td>$u</td>
                <td>" . ($p ? "[contraseña]" : "[vacía]") . "</td>";
            
            echo "<td>";
            try {
                $conn = @new mysqli($h, $u, $p);
                if ($conn->connect_error) {
                    echo "<span style='color:red'>ERROR: " . $conn->connect_error . "</span>";
                } else {
                    echo "<span style='color:green'>CONEXIÓN EXITOSA!</span><br>";
                    // Intentar seleccionar la base de datos
                    $db_select = $conn->select_db($database);
                    if ($db_select) {
                        echo "<span style='color:green'>Base de datos '$database' seleccionada correctamente.</span>";
                    } else {
                        echo "<span style='color:orange'>No se pudo seleccionar la base de datos '$database'.</span>";
                    }
                    $conn->close();
                }
            } catch (Exception $e) {
                echo "<span style='color:red'>EXCEPCIÓN: " . $e->getMessage() . "</span>";
            }
            echo "</td></tr>";
        }
    }
}
echo "</table>";

// Prueba con timeout
echo "<h2>Prueba con timeout:</h2>";
try {
    echo "Intentando conectar a $host con timeout de 10 segundos...<br>";
    $socket = @fsockopen($host, 3306, $errno, $errstr, 10);
    if (!$socket) {
        echo "<span style='color:red'>ERROR: $errstr ($errno)</span>";
    } else {
        echo "<span style='color:green'>Puerto 3306 abierto en $host!</span>";
        fclose($socket);
    }
} catch (Exception $e) {
    echo "<span style='color:red'>EXCEPCIÓN: " . $e->getMessage() . "</span>";
}
?>