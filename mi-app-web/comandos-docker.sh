#!/bin/bash
# Comandos para construir, ejecutar y compartir la imagen Docker

# 1. Construir la imagen con tag apropiado (reemplaza "tuusuario" con tu nombre de usuario de Docker Hub)
docker build -t tuusuario/mi-app-web:1.0 .

# 2. Verificar que la imagen se creó correctamente
docker images | grep mi-app-web

# 3. Iniciar sesión en Docker Hub
docker login

# 4. Subir la imagen a Docker Hub
docker push tuusuario/mi-app-web:1.0

# 5. Ejecutar la aplicación utilizando tu imagen
docker-compose -f docker-compose.yml up -d

# 6. Verificar que los contenedores están funcionando
docker-compose ps

# 7. Para detener todos los contenedores
docker-compose down

# Comandos adicionales útiles:

# Ver logs en tiempo real
docker-compose logs -f

# Entrar al contenedor PHP
docker exec -it php-app bash

# Entrar al contenedor MySQL
docker exec -it mysql-db mysql -u root -pmipassword mibasededatos

# Eliminar todos los contenedores parados
docker container prune

# Eliminar todas las imágenes sin usar
docker image prune -a