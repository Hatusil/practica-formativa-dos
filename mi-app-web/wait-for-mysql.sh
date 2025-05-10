#!/bin/bash
# Script para esperar a que MySQL esté disponible

set -e

host="$1"
shift
cmd="$@"

until mysql -h "$host" -u root -pmipassword -e "SELECT 1"; do
  >&2 echo "MySQL no está disponible todavía - esperando..."
  sleep 2
done

>&2 echo "MySQL está disponible - ejecutando comando"
exec $cmd