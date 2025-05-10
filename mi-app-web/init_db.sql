
-- Crear tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL
);

-- Insertar algunos datos de ejemplo
INSERT INTO usuarios (nombre, email) VALUES
    ('Juan Pérez', 'juan@example.com'),
    ('María García', 'maria@example.com'),
    ('Carlos Rodríguez', 'carlos@example.com'),
    ('Ana Martínez', 'ana@example.com'),
    ('Luis Sánchez', 'luis@example.com');