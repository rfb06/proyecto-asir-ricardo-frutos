#!/bin/bash
# =============================================================================
# sgbd.sh — Servidor de base de datos (MariaDB)
# Solo accesible desde la LAN Datos (192.168.20.0/24)
# =============================================================================

set -e

export DEBIAN_FRONTEND=noninteractive

echo "==> Actualizando paquetes..."
apt-get update -qq
apt-get install -y -qq mariadb-server

# ---------------------------------------------------------------------------
# Configurar MariaDB para escuchar en la LAN Datos
# ---------------------------------------------------------------------------
cat > /etc/mysql/mariadb.conf.d/99-lan-datos.cnf << 'EOF'
[mysqld]
# Escuchar en la LAN Datos (no en 127.0.0.1 solo)
bind-address = 0.0.0.0

# Ajustes básicos de rendimiento
max_connections      = 100
innodb_buffer_pool_size = 256M
query_cache_type     = 1
query_cache_size     = 32M
EOF

# ---------------------------------------------------------------------------
# Arrancar MariaDB y aplicar configuración segura básica
# ---------------------------------------------------------------------------
systemctl enable mariadb
systemctl start mariadb

# Configuración inicial de seguridad
mysql -u root << 'SQL'
DELETE FROM mysql.user WHERE User='';
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';
FLUSH PRIVILEGES;
SQL

# Importar esquema completo de TecnoRural
echo "==> Importando esquema TecnoRural..."
mysql -u root < /vagrant/tecnorural.sql

echo "==> Importando tablas de autenticacion..."
mysql -u root < /vagrant/auth.sql

echo "==> SGBD listo"
echo "    - MariaDB escuchando en 192.168.20.100:3306"
echo "    - Base de datos: tecnorural"
echo "    - Usuario: webuser / WebPass2024!"
echo "    - Acceso permitido desde 192.168.20.11 y 192.168.20.12"
