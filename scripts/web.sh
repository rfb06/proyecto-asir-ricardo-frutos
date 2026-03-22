#!/bin/bash
# =============================================================================
# web.sh — Servidores web (web1 y web2)
# Instala Apache2 + PHP y monta los recursos compartidos via NFS
# =============================================================================

set -e

export DEBIAN_FRONTEND=noninteractive
NFS_SERVER="192.168.10.5"

echo "==> Actualizando paquetes..."
apt-get update -qq
apt-get install -y -qq \
    apache2 \
    php \
    php-mysql \
    php-curl \
    php-mbstring \
    php-xml \
    libapache2-mod-php \
    nfs-common

# ---------------------------------------------------------------------------
# Habilitar módulos Apache necesarios
# ---------------------------------------------------------------------------
a2enmod rewrite
a2enmod headers
a2enmod php*  2>/dev/null || true

# ---------------------------------------------------------------------------
# Esperar a que el servidor NFS esté disponible
# ---------------------------------------------------------------------------
echo "==> Esperando al servidor NFS ($NFS_SERVER)..."
for i in $(seq 1 30); do
    if rpcinfo -p "$NFS_SERVER" &>/dev/null; then
        echo "    NFS server encontrado en intento $i"
        break
    fi
    echo "    Intento $i/30 — esperando 5s..."
    sleep 5
done

# ---------------------------------------------------------------------------
# Parar Apache antes de tocar cualquier directorio que use
# ---------------------------------------------------------------------------
systemctl stop apache2

# ---------------------------------------------------------------------------
# Montar /var/www/html desde NFS
# Vaciamos el directorio en lugar de moverlo (evita "device busy")
# ---------------------------------------------------------------------------
echo "==> Montando /var/www/html desde NFS..."
rm -rf /var/www/html/*
mount -t nfs "$NFS_SERVER":/srv/nfs/www /var/www/html

# ---------------------------------------------------------------------------
# Montar /etc/motd desde NFS
# ---------------------------------------------------------------------------
echo "==> Montando /etc/motd desde NFS..."
mkdir -p /mnt/nfs-motd
mount -t nfs "$NFS_SERVER":/srv/nfs/motd /mnt/nfs-motd
rm -f /etc/motd
ln -sf /mnt/nfs-motd/motd /etc/motd

# ---------------------------------------------------------------------------
# Montar /etc/issue desde NFS
# ---------------------------------------------------------------------------
echo "==> Montando /etc/issue desde NFS..."
mkdir -p /mnt/nfs-issue
mount -t nfs "$NFS_SERVER":/srv/nfs/issue /mnt/nfs-issue
rm -f /etc/issue
ln -sf /mnt/nfs-issue/issue /etc/issue

# ---------------------------------------------------------------------------
# Montar configuración Apache desde NFS
# Eliminamos los directorios originales directamente (sin backup)
# ---------------------------------------------------------------------------
echo "==> Montando configuración Apache desde NFS..."
mkdir -p /mnt/nfs-apache2
mount -t nfs "$NFS_SERVER":/srv/nfs/apache2 /mnt/nfs-apache2

rm -rf /etc/apache2/sites-available
rm -rf /etc/apache2/sites-enabled
ln -s /mnt/nfs-apache2/sites-available /etc/apache2/sites-available
ln -s /mnt/nfs-apache2/sites-enabled   /etc/apache2/sites-enabled

# ---------------------------------------------------------------------------
# Persistir los montajes NFS en /etc/fstab
# ---------------------------------------------------------------------------
echo "==> Configurando /etc/fstab para montajes NFS persistentes..."

# Eliminar entradas anteriores si las hubiera
sed -i '/192.168.10.5:/d' /etc/fstab

cat >> /etc/fstab << EOF

# Montajes NFS compartidos
$NFS_SERVER:/srv/nfs/www      /var/www/html     nfs  defaults,_netdev,nofail  0 0
$NFS_SERVER:/srv/nfs/motd     /mnt/nfs-motd     nfs  defaults,_netdev,nofail  0 0
$NFS_SERVER:/srv/nfs/issue    /mnt/nfs-issue    nfs  defaults,_netdev,nofail  0 0
$NFS_SERVER:/srv/nfs/apache2  /mnt/nfs-apache2  nfs  defaults,_netdev,nofail  0 0
EOF

# ---------------------------------------------------------------------------
# Arrancar Apache
# ---------------------------------------------------------------------------
systemctl enable apache2
systemctl start apache2

echo "==> Servidor web listo: $(hostname)"
echo "    - Apache2 + PHP instalados"
echo "    - /var/www/html montado desde NFS"
echo "    - /etc/motd y /etc/issue montados desde NFS"
echo "    - Configuración Apache montada desde NFS"
