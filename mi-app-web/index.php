<?php
$host = 'mysql-db';        // Nombre del contenedor MySQL según docker-compose.yml
$user = 'root';
$password = 'mipassword';
$database = 'mibasededatos';

// Intentar conectar con reintento para dar tiempo a MySQL a iniciar
$maxRetries = 10;
$retry = 0;
$conn = null;

while ($retry < $maxRetries) {
    try {
        $conn = new mysqli($host, $user, $password, $database);
        
        // Si no hay error, salimos del bucle
        if (!$conn->connect_error) {
            break;
        }
        
        // Si hay error, esperamos antes de reintentar
        $retry++;
        sleep(3); // Espera 3 segundos antes de reintentar
        
    } catch (Exception $e) {
        $retry++;
        sleep(3);
    }
}

// Verificar conexión después de intentos
if ($conn === null || $conn->connect_error) {
    die("<div class='error'>Conexión fallida después de $maxRetries intentos: " . 
        ($conn ? $conn->connect_error : "No se pudo establecer conexión") . 
        "<br>Verifique que los contenedores estén funcionando correctamente.</div>");
}

// Consulta
$sql = "SELECT * FROM usuarios";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Aplicación Web</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
        }
        .container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
        }
        header {
            background-color: #4CAF50;
            color: white;
            text-align: center;
            padding: 1em;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .success-message {
            background-color: #dff0d8;
            color: #3c763d;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #d6e9c6;
        }
        .error {
            background-color: #f2dede;
            color: #a94442;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #ebccd1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            text-align: left;
            padding: 12px;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        footer {
            text-align: center;
            margin-top: 30px;
            padding: 10px;
            background-color: #333;
            color: white;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Mi Aplicación Web</h1>
        </header>
        
        <div class="success-message">
            <h2>Conexión exitosa a MySQL desde PHP!</h2>
        </div>
        
        <?php
        if ($result && $result->num_rows > 0) {
            echo "<h2>Lista de Usuarios</h2>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Nombre</th><th>Email</th></tr>";
            
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row["id"] . "</td>";
                echo "<td>" . $row["nombre"] . "</td>";
                echo "<td>" . $row["email"] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<div class='error'>No se encontraron registros en la tabla usuarios</div>";
        }
        
        $conn->close();
        ?>
        
        <footer>
            &copy; <?php echo date("Y"); ?> Mi Aplicación Web - Creada con Docker, Nginx, PHP y MySQL
        </footer>
    </div>
</body>
</html>