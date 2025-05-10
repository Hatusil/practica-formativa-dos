<?php
// Configuración de la aplicación
$config = [
    'db' => [
        'host' => 'mysql-db', // Nombre del contenedor MySQL
        'user' => 'root',
        'password' => 'mipassword',
        'database' => 'mibasededatos',
        'maxRetries' => 10, // Número máximo de intentos de conexión
        'retryDelay' => 3 // Segundos entre intentos
    ],
    'app' => [
        'name' => 'Docker App - Práctica Formativa',
        'author' => 'Ricardo Gieco',
        'course' => 'Seminario de Actualización DevOps - 3°A'
    ]
];

// Función para registrar eventos en un log
function logMessage($message, $type = 'info') {
    $date = date('Y-m-d H:i:s');
    $logMessage = "[$date][$type]: $message" . PHP_EOL;
    
    // Crear directorio de logs si no existe
    if (!is_dir('/var/log/php')) {
        @mkdir('/var/log/php', 0777, true);
    }
    
    // Escribir al archivo de log (con manejo de errores)
    @error_log($logMessage, 3, '/var/log/php/app.log');
    
    // También imprimir en error_log estándar como respaldo
    error_log("$type: $message");
}

// Función para establecer conexión a la base de datos con reintentos
function connectToDatabase($config) {
    $retry = 0;
    $conn = null;
    $error = null;
    
    logMessage("Iniciando conexión a la base de datos {$config['db']['database']} en {$config['db']['host']}");
    
    while ($retry < $config['db']['maxRetries']) {
        try {
            $conn = new mysqli(
                $config['db']['host'],
                $config['db']['user'],
                $config['db']['password'],
                $config['db']['database']
            );
            
            // Si no hay error, salimos del bucle
            if (!$conn->connect_error) {
                logMessage("Conexión exitosa a la base de datos en el intento {$retry}");
                break;
            }
            
            // Si hay error, lo registramos y esperamos antes de reintentar
            $error = $conn->connect_error;
            logMessage("Intento {$retry}: Error de conexión: {$error}", 'error');
            $retry++;
            sleep($config['db']['retryDelay']);
            
        } catch (Exception $e) {
            $error = $e->getMessage();
            logMessage("Excepción en intento {$retry}: {$error}", 'error');
            $retry++;
            sleep($config['db']['retryDelay']);
        }
    }
    
    // Si tras los reintentos no hay conexión, devolvemos el error
    if ($conn === null || $conn->connect_error) {
        logMessage("Conexión fallida después de {$config['db']['maxRetries']} intentos", 'error');
        return ['success' => false, 'error' => $error ?: 'Error desconocido de conexión'];
    }
    
    // Si todo fue bien, devolvemos la conexión
    return ['success' => true, 'connection' => $conn];
}

// Función para ejecutar consultas con manejo de errores
function executeQuery($conn, $sql) {
    try {
        logMessage("Ejecutando consulta: " . substr($sql, 0, 100) . (strlen($sql) > 100 ? '...' : ''));
        
        $result = $conn->query($sql);
        
        if ($result === false) {
            logMessage("Error al ejecutar la consulta: {$conn->error}", 'error');
            return ['success' => false, 'error' => $conn->error];
        }
        
        logMessage("Consulta ejecutada con éxito");
        return ['success' => true, 'result' => $result];
        
    } catch (Exception $e) {
        logMessage("Excepción al ejecutar la consulta: {$e->getMessage()}", 'error');
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Función para mostrar información del sistema
function getSystemInfo() {
    return [
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido',
        'php_version' => PHP_VERSION,
        'hostname' => gethostname(),
        'server_time' => date('Y-m-d H:i:s'),
        'docker_container_id' => shell_exec('cat /etc/hostname') ?: 'Desconocido',
        'mysql_version' => null, // Se completará si hay conexión a MySQL
        'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB'
    ];
}

// Iniciar la ejecución
$systemInfo = getSystemInfo();
$dbConnection = connectToDatabase($config);

// Ejecutar consulta solo si la conexión fue exitosa
$usersData = [];
$dbStats = [];

if ($dbConnection['success']) {
    $conn = $dbConnection['connection'];
    
    // Obtener versión de MySQL para la información del sistema
    $versionQuery = executeQuery($conn, "SELECT VERSION() as version");
    if ($versionQuery['success']) {
        $versionRow = $versionQuery['result']->fetch_assoc();
        $systemInfo['mysql_version'] = $versionRow['version'];
    }
    
    // Obtener estadísticas de la base de datos
    $dbStatsQuery = executeQuery($conn, "
        SELECT 
            table_schema as 'database',
            COUNT(table_name) as tables,
            SUM(table_rows) as rows,
            SUM(data_length + index_length) / 1024 / 1024 as size_mb
        FROM information_schema.tables
        WHERE table_schema = '{$config['db']['database']}'
        GROUP BY table_schema
    ");
    
    if ($dbStatsQuery['success'] && $dbStatsQuery['result']->num_rows > 0) {
        $dbStats = $dbStatsQuery['result']->fetch_assoc();
    }
    
    // Consulta para usuarios
    $usersQuery = executeQuery($conn, "SELECT * FROM usuarios");
    
    if ($usersQuery['success']) {
        $result = $usersQuery['result'];
        
        logMessage("Número de filas en resultado: " . $result->num_rows);
        
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $usersData[] = $row;
                logMessage("Usuario encontrado: " . $row['nombre'] . " (" . $row['email'] . ")");
            }
        } else {
            logMessage("No se encontraron usuarios en la tabla", 'warning');
        }
    } else {
        logMessage("Error en la consulta de usuarios: " . ($usersQuery['error'] ?? 'desconocido'), 'error');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($config['app']['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2C3E50;
            --secondary-color: #1ABC9C;
            --accent-color: #3498DB;
            --danger-color: #E74C3C;
            --success-color: #27AE60;
            --warning-color: #F39C12;
            --light-color: #ECF0F1;
            --dark-color: #34495E;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            background-color: #f8f9fa;
            color: var(--dark-color);
            padding-top: 20px;
        }
        
        .container {
            max-width: 1200px;
            padding: 0 20px;
        }
        
        .app-header {
            background: linear-gradient(135deg, var(--primary-color), var(--dark-color));
            color: white;
            padding: 2rem 0;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .status-card {
            transition: transform 0.3s;
            margin-bottom: 20px;
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        }
        
        .status-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .status-card .card-header {
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .status-success {
            border-left: 5px solid var(--success-color);
        }
        
        .status-error {
            border-left: 5px solid var(--danger-color);
        }
        
        .table-container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .stats-box {
            background-color: var(--light-color);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        }
        
        .stats-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--accent-color);
        }
        
        .stats-label {
            color: var(--dark-color);
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .system-info {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        }
        
        .system-info h3 {
            margin-bottom: 20px;
            color: var(--primary-color);
            border-bottom: 2px solid var(--light-color);
            padding-bottom: 10px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .info-value {
            color: var(--accent-color);
        }
        
        footer {
            background-color: var(--dark-color);
            color: white;
            padding: 20px 0;
            margin-top: 40px;
            border-radius: 8px;
            box-shadow: 0 -2px 5px rgba(0,0,0,0.08);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .stats-number {
                font-size: 1.4rem;
            }
            
            .app-header {
                padding: 1.5rem 0;
            }
            
            .system-info, .table-container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="app-header text-center">
            <h1><?php echo htmlspecialchars($config['app']['name']); ?></h1>
            <p class="lead mb-0">
                Aplicación demo con Docker, PHP y MySQL | 
                <?php echo htmlspecialchars($config['app']['author']); ?>
            </p>
        </header>
        
        <div class="row">
            <!-- Estado de conexión -->
            <div class="col-lg-12 mb-4">
                <?php if ($dbConnection['success']): ?>
                    <div class="card status-card status-success">
                        <div class="card-header bg-success text-white">
                            <i class="fas fa-check-circle"></i> Estado de la conexión
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">Conexión a MySQL establecida correctamente</h5>
                            <p class="card-text">
                                Se ha conectado exitosamente al servidor MySQL ubicado en el contenedor 
                                <code><?php echo htmlspecialchars($config['db']['host']); ?></code>
                                y a la base de datos <code><?php echo htmlspecialchars($config['db']['database']); ?></code>.
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card status-card status-error">
                        <div class="card-header bg-danger text-white">
                            <i class="fas fa-exclamation-triangle"></i> Error de conexión
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">No se pudo establecer conexión con MySQL</h5>
                            <p class="card-text">
                                Error: <?php echo htmlspecialchars($dbConnection['error']); ?>
                            </p>
                            <div class="alert alert-warning">
                                <strong>Sugerencias:</strong>
                                <ul>
                                    <li>Verifique que el contenedor MySQL esté en ejecución: <code>docker ps</code></li>
                                    <li>Compruebe los logs del contenedor: <code>docker logs mysql-db</code></li>
                                    <li>Verifique la red Docker: <code>docker network inspect mi-red-app</code></li>
                                    <li>Asegúrese de que las credenciales sean correctas</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($dbConnection['success'] && !empty($dbStats)): ?>
            <!-- Estadísticas de la base de datos -->
            <div class="col-lg-12 mb-4">
                <h3 class="mb-3">Estadísticas de la base de datos</h3>
                <div class="row">
                    <div class="col-md-4">
                        <div class="stats-box text-center">
                            <div class="stats-number"><?php echo htmlspecialchars($dbStats['tables']); ?></div>
                            <div class="stats-label">Tablas</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-box text-center">
                            <div class="stats-number"><?php echo htmlspecialchars($dbStats['rows']); ?></div>
                            <div class="stats-label">Registros</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-box text-center">
                            <div class="stats-number"><?php echo round($dbStats['size_mb'], 2); ?> MB</div>
                            <div class="stats-label">Tamaño de la base</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Tabla de usuarios -->
            <div class="col-lg-8">
                <div class="table-container">
                    <h3 class="mb-3">Lista de Usuarios</h3>
                    
                    <?php if ($dbConnection['success']): ?>
                        <?php if (count($usersData) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Email</th>
                                            <?php if (isset($usersData[0]['fecha_registro'])): ?>
                                            <th>Fecha de registro</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usersData as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                                <td><?php echo htmlspecialchars($user['nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <?php if (isset($user['fecha_registro'])): ?>
                                                <td><?php echo htmlspecialchars($user['fecha_registro']); ?></td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                No se encontraron registros en la tabla usuarios.
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            No se pueden mostrar los usuarios debido a un error de conexión con la base de datos.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Información del sistema -->
            <div class="col-lg-4">
                <div class="system-info">
                    <h3>Información del Sistema</h3>
                    
                    <div class="info-item">
                        <span class="info-label">Servidor Web:</span>
                        <span class="info-value"><?php echo htmlspecialchars($systemInfo['server_software']); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Versión PHP:</span>
                        <span class="info-value"><?php echo htmlspecialchars($systemInfo['php_version']); ?></span>
                    </div>
                    
                    <?php if ($systemInfo['mysql_version']): ?>
                    <div class="info-item">
                        <span class="info-label">Versión MySQL:</span>
                        <span class="info-value"><?php echo htmlspecialchars($systemInfo['mysql_version']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-item">
                        <span class="info-label">Container ID:</span>
                        <span class="info-value"><?php echo htmlspecialchars($systemInfo['docker_container_id']); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Hostname:</span>
                        <span class="info-value"><?php echo htmlspecialchars($systemInfo['hostname']); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Fecha/Hora:</span>
                        <span class="info-value"><?php echo htmlspecialchars($systemInfo['server_time']); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Memoria utilizada:</span>
                        <span class="info-value"><?php echo htmlspecialchars($systemInfo['memory_usage']); ?></span>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header bg-primary text-white">
                        Entorno Docker
                    </div>
                    <div class="card-body">
                        <p class="mb-2">Esta aplicación se ejecuta en contenedores Docker:</p>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                PHP + Apache
                                <span class="badge bg-success rounded-pill">Activo</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                MySQL
                                <span class="badge <?php echo $dbConnection['success'] ? 'bg-success' : 'bg-danger'; ?> rounded-pill">
                                    <?php echo $dbConnection['success'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <footer class="text-center py-4">
            <div class="container">
                <p class="mb-0">
                    <?php echo htmlspecialchars($config['app']['name']); ?> &copy; <?php echo date("Y"); ?> | 
                    <?php echo htmlspecialchars($config['app']['course']); ?>
                </p>
                <small class="text-muted">
                    Creado con Docker, PHP <?php echo PHP_VERSION; ?> y MySQL 
                    <?php echo $systemInfo['mysql_version'] ?: 'N/A'; ?>
                </small>
            </div>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
<?php
// Cerrar la conexión si existe
if ($dbConnection['success']) {
    $dbConnection['connection']->close();
}
?>