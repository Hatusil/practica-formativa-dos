# Mi Aplicación Web con Docker

Esta aplicación web simple muestra una lista de usuarios desde una base de datos MySQL, utilizando Docker, Nginx y PHP. Es parte del Seminario de Actualización DevOps del 3°A, realizado por Ricardo Gieco.

## Disponible en Docker Hub

Esta aplicación está disponible como imagen en Docker Hub:
```
hatusil/app-web:1.0
```

URL: https://hub.docker.com/r/hatusil/app-web

## Capturas de pantalla

![Captura de pantalla de la aplicación](./capturas/vista-app.png)

## Estructura del Proyecto

```
mi-app-web/
├── default.conf         # Configuración de Nginx
├── docker-compose.yml   # Configuración de desarrollo
├── docker-compose-deploy.yml  # Configuración para uso de la imagen publicada
├── Dockerfile           # Configuración para la imagen de PHP
├── app/
│   ├── index.php        # Página principal de la aplicación
│   └── test_db.php      # Script de diagnóstico de conexión
├── init.sql             # Script SQL para inicializar la base de datos
└── wait-for-mysql.sh    # Script para esperar a MySQL
```

## Requisitos

- Docker Engine
- Docker Compose

## Cómo usar esta imagen desde Docker Hub

### Método 1: Usando docker-compose-deploy.yml

1. Descarga los archivos necesarios:
   - docker-compose-deploy.yml
   - default.conf
   - init.sql

2. Ejecuta la aplicación con Docker Compose:
   ```bash
   docker-compose -f docker-compose-deploy.yml up -d
   ```

3. Accede a la aplicación en tu navegador:
   ```
   http://localhost:8080
   ```

### Método 2: Usando Docker Run

1. Crea una red de Docker:
   ```bash
   docker network create mi-red-app
   ```

2. Inicia un contenedor MySQL:
   ```bash
   docker run --name mysql-db \
     --network mi-red-app \
     -e MYSQL_ROOT_PASSWORD=mipassword \
     -e MYSQL_DATABASE=mibasededatos \
     -d mysql:8.0
   ```

3. Inicializa la base de datos:
   ```bash
   # Crea un archivo init.sql con el contenido del script SQL
   docker cp init.sql mysql-db:/init.sql
   docker exec -i mysql-db sh -c 'mysql -u root -pmipassword mibasededatos < /init.sql'
   ```

4. Inicia el contenedor PHP con la imagen:
   ```bash
   docker run --name php-app \
     --network mi-red-app \
     -d ricardo-gieco/app-web:1.0
   ```

5. Inicia el servidor web Nginx:
   ```bash
   # Crea un archivo default.conf con la configuración de Nginx
   docker run --name webserver \
     --network mi-red-app \
     -p 8080:80 \
     -v $(pwd)/default.conf:/etc/nginx/conf.d/default.conf \
     -d nginx:alpine
   ```

6. Accede a la aplicación en tu navegador:
   ```
   http://localhost:8080
   ```

## Instrucciones para desarrollo local

1. Clonar este repositorio:
   ```bash
   git clone https://github.com/Hatusil/practica-formativa-dos
   ```

2. Construir y levantar los contenedores:
   ```bash
   docker-compose up -d
   ```

3. Acceder a la aplicación web:
   - Abrir en el navegador: http://localhost:8080

4. Puertos utilizados:
   - Aplicación web: 8080
   - MySQL: 3306

## Para desarrolladores: Cómo construir y publicar tu propia imagen

1. Clona este repositorio

2. Construye la imagen:
   ```bash
   docker build -t hatusil/app-web:1.0 .
   ```

3. Inicia sesión en Docker Hub:
   ```bash
   docker login
   ```

4. Sube la imagen:
   ```bash
   docker push hatusil/app-web:1.0
   ```

## Problemas encontrados y soluciones

### Problema 1: Conexión entre contenedores
Inicialmente, los contenedores no podían comunicarse entre sí.

**Solución**: Creé una red Docker personalizada y aseguré que ambos contenedores estuvieran conectados a esta red:
```bash
docker network create mi-red-app
```

### Problema 2: Persistencia de datos MySQL
Al reiniciar el contenedor MySQL, los datos se perdían.

**Solución**: Implementé un volumen Docker para persistir los datos:
```yaml
volumes:
  mysql_data:/var/lib/mysql
```

### Problema 3: Inicialización de la base de datos
Era necesario ejecutar scripts SQL manualmente después de crear el contenedor.

**Solución**: Utilicé un script de inicialización (init.sql) y lo monté en el directorio `/docker-entrypoint-initdb.d/` del contenedor MySQL, lo que ejecuta automáticamente el script al iniciar el contenedor por primera vez.

### Problema 4: "Connection refused" al intentar conectar PHP con MySQL

#### Pasos para solucionar

1. **Reiniciar los contenedores completamente**
   ```bash
   # Detener y eliminar todos los contenedores
   docker-compose down

   # Eliminar volúmenes (opcional - ¡atención! se borrarán los datos guardados)
   docker-compose down -v

   # Reconstruir las imágenes
   docker-compose build --no-cache

   # Iniciar de nuevo los contenedores
   docker-compose up -d
   ```

2. **Verificar que los contenedores estén funcionando**
   ```bash
   docker-compose ps
   ```

3. **Comprobar los logs de cada contenedor**
   ```bash
   # Ver logs de MySQL
   docker-compose logs mysql

   # Ver logs de PHP
   docker-compose logs php

   # Ver logs de Nginx
   docker-compose logs nginx
   ```

4. **Probar la conexión manualmente**
   ```bash
   # Acceder al contenedor de PHP
   docker exec -it php-app bash

   # Dentro del contenedor, probar conexión a MySQL
   ping mysql-db
   mysql -h mysql-db -u root -pmipassword -e "SHOW DATABASES;"
   ```

5. **Verificar la red de Docker**
   ```bash
   # Listar las redes
   docker network ls

   # Inspeccionar la red de la aplicación
   docker network inspect mi-red-app
   ```

## Comandos Docker útiles

```bash
# Ver contenedores en ejecución
docker ps

# Ver los logs de un contenedor
docker logs nombre-contenedor

# Crear una red Docker
docker network create mi-red-app

# Construir la imagen de la aplicación
docker build -t hatusil/app-web:1.0 .

# Subir la imagen a Docker Hub
docker login
docker push hatusil/app-web:1.0

# Detener todos los contenedores
docker-compose down

# Detener todos los contenedores y eliminar volúmenes
docker-compose down -v
```

## Solución de problemas

Si experimentas problemas con la aplicación:

1. Asegúrate de que todos los contenedores estén funcionando:
   ```bash
   docker-compose ps
   ```

2. Verifica los logs:
   ```bash
   docker-compose logs mysql
   docker-compose logs php
   docker-compose logs nginx
   ```

3. Accede a la herramienta de diagnóstico:
   ```
   http://localhost:8080/test_db.php
   ```

4. Para reiniciar la aplicación:
   ```bash
   docker-compose down
   docker-compose up -d
   ```

## Imágenes utilizadas

- PHP 8.1 con Apache / PHP-FPM
- MySQL 8.0
- Nginx (para la versión con PHP-FPM)

## Licencia

Este proyecto está disponible bajo la licencia MIT.

## Autor

Ricardo Gieco - 3°A - Seminario de Actualización DevOps