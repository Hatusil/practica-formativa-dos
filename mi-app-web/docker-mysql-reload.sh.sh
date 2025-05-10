#!/bin/bash
# Comandos para reiniciar el contenedor MySQL y recargar los datos

echo "==== Deteniendo contenedores ===="
docker-compose down

echo "==== Eliminando volumen de MySQL para reset completo ===="
docker volume rm $(docker volume ls -q | grep mysql_data)

echo "==== Creando y ejecutando contenedores nuevamente ===="
docker-compose up -d

echo "==== Esperando a que MySQL esté listo (10 segundos) ===="
sleep 10

echo "==== Verificando que MySQL está en ejecución ===="
docker ps | grep mysql-db

echo "==== Cargando datos de init.sql manualmente ===="
docker exec -i mysql-db mysql -uroot -pmipassword < init.sql

echo "==== Verificando que la tabla usuarios tiene datos ===="
docker exec -it mysql-db mysql -uroot -pmipassword -e "USE mibasededatos; SELECT * FROM usuarios;"

echo "==== Reiniciando el contenedor PHP para refrescar la conexión ===="
docker restart mi-app-web

echo "==== Contenedores en ejecución ===="
docker-compose ps

echo "==== Proceso completado. Ahora puedes acceder a http://localhost:8080 ===="