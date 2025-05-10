#!/bin/bash
# Script para diagnosticar y corregir problemas con la base de datos MySQL

echo "==== Comprobando el estado del contenedor MySQL ===="
docker ps | grep mysql-db

echo -e "\n==== Verificando logs de MySQL ===="
docker logs mysql-db | tail -n 20

echo -e "\n==== Verificando que la base de datos existe ===="
docker exec -it mysql-db mysql -uroot -pmipassword -e "SHOW DATABASES;"

echo -e "\n==== Verificando que la tabla existe ===="
docker exec -it mysql-db mysql -uroot -pmipassword -e "USE mibasededatos; SHOW TABLES;"

echo -e "\n==== Verificando que hay datos en la tabla usuarios ===="
docker exec -it mysql-db mysql -uroot -pmipassword -e "USE mibasededatos; SELECT COUNT(*) FROM usuarios;"

echo -e "\n==== Mostrando los datos de la tabla usuarios ===="
docker exec -it mysql-db mysql -uroot -pmipassword -e "USE mibasededatos; SELECT * FROM usuarios;"

echo -e "\n==== Comprobando permisos de usuario ===="
docker exec -it mysql-db mysql -uroot -pmipassword -e "SELECT user, host FROM mysql.user;"
docker exec -it mysql-db mysql -uroot -pmipassword -e "SHOW GRANTS FOR 'root'@'%';"

echo -e "\n==== Reinicializando tabla y datos ===="
docker exec -it mysql-db mysql -uroot -pmipassword -e "USE mibasededatos; DROP TABLE IF EXISTS usuarios;"
docker exec -it mysql-db mysql -uroot -pmipassword -e "USE mibasededatos; CREATE TABLE usuarios (id INT AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(100) NOT NULL, email VARCHAR(100) NOT NULL, fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP);"
docker exec -it mysql-db mysql -uroot -pmipassword -e "USE mibasededatos; INSERT INTO usuarios (nombre, email) VALUES ('Usuario 1', 'usuario1@ejemplo.com'), ('Usuario 2', 'usuario2@ejemplo.com'), ('Usuario 3', 'usuario3@ejemplo.com'), ('Ricardo Gieco', 'ricardo@ejemplo.com'), ('Ana Rodríguez', 'ana@ejemplo.com');"

echo -e "\n==== Verificando datos después de reinicializar ===="
docker exec -it mysql-db mysql -uroot -pmipassword -e "USE mibasededatos; SELECT * FROM usuarios;"

echo -e "\n==== Verificando configuración de PHP para conexión a MySQL ===="
docker exec -it mi-app-web cat /var/www/html/index.php | grep -A10 'host'