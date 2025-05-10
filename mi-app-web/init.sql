
-- Script para inicializar la base de datos
-- Este archivo se ejecutará automáticamente cuando se cree el contenedor MySQL

-- Crear la base de datos si no existe
CREATE DATABASE IF NOT EXISTS mibasededatos;

-- Usar la base de datos
USE mibasededatos;

-- Crear tabla de usuarios
DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar datos de ejemplo
INSERT INTO usuarios (nombre, email) VALUES
('Usuario 1', 'usuario1@ejemplo.com'),
('Usuario 2', 'usuario2@ejemplo.com'),
('Usuario 3', 'usuario3@ejemplo.com'),
('Ricardo Gieco', 'ricardo@ejemplo.com'),
('Ana Rodríguez', 'ana@ejemplo.com');

-- Crear un usuario para la aplicación con privilegios limitados
CREATE USER IF NOT EXISTS 'app_user'@'%' IDENTIFIED BY 'app_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON mibasededatos.* TO 'app_user'@'%';
FLUSH PRIVILEGES;

-- Verificar que los datos se insertaron correctamente
SELECT * FROM usuarios;