#!/bin/bash
# =============================================================================
# nfs-server.sh \u2014 Servidor NFS compartido para web1 y web2
# Comparte:
#   /srv/nfs/www       \u2192 /var/www/html  en los clientes
#   /srv/nfs/motd      \u2192 /etc/motd
#   /srv/nfs/issue     \u2192 /etc/issue
#   /srv/nfs/apache2   \u2192 /etc/apache2   (configuraci�n Apache compartida)
# =============================================================================

set -e

export DEBIAN_FRONTEND=noninteractive

echo "==> Actualizando paquetes..."
apt-get update -qq
apt-get install -y -qq nfs-kernel-server

# ---------------------------------------------------------------------------
# Crear directorios exportados
# ---------------------------------------------------------------------------
mkdir -p /srv/nfs/www
mkdir -p /srv/nfs/motd
mkdir -p /srv/nfs/issue
mkdir -p /srv/nfs/apache2/sites-available
mkdir -p /srv/nfs/apache2/sites-enabled
mkdir -p /srv/nfs/apache2/conf-available
mkdir -p /srv/nfs/apache2/conf-enabled
mkdir -p /srv/nfs/apache2/mods-available

# ---------------------------------------------------------------------------
# Desplegar aplicaci�n TecnoRural
# ---------------------------------------------------------------------------
echo "==> Desplegando aplicacion TecnoRural en /srv/nfs/www..."
cp -r /vagrant/tecnorural/*.php /srv/nfs/www/
cp -r /vagrant/tecnorural/includes /srv/nfs/www/
# Copiar imagen del logo si existe
if [ -f /vagrant/tecnorural.png ]; then
    cp /vagrant/tecnorural.png /srv/nfs/www/tecnorural.png
fi
cat > /srv/nfs/www/info.php << 'EOF'
<?php phpinfo(); ?>
EOF

# Permisos correctos para Apache (www-data uid=33)
# Se aplican DESPUES de copiar los ficheros
chown -R 33:33 /srv/nfs/www
chmod -R 755 /srv/nfs/www

# ---------------------------------------------------------------------------
# /etc/motd compartido
# ---------------------------------------------------------------------------
cat > /srv/nfs/motd/motd << 'EOF'

  \u2554\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2557
  \u2551        Servidor Web \u2014 Infraestructura        \u2551
  \u2551     /var/www/html montado via NFS            \u2551
  \u255a\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u255d

EOF

# ---------------------------------------------------------------------------
# /etc/issue compartido
# ---------------------------------------------------------------------------
cat > /srv/nfs/issue/issue << 'EOF'
Servidor Web de la infraestructura balanceada
NFS: 192.168.10.5
EOF

# ---------------------------------------------------------------------------
# Configuraci�n Apache2 compartida (000-default.conf)
# ---------------------------------------------------------------------------
cat > /srv/nfs/apache2/sites-available/000-default.conf << 'EOF'
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html
    AddDefaultCharset UTF-8

    <Directory /var/www/html>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

# Habilitar site (enlace simb�lico en sites-enabled)
ln -sf /srv/nfs/apache2/sites-available/000-default.conf \
       /srv/nfs/apache2/sites-enabled/000-default.conf || true

# ---------------------------------------------------------------------------
# Exportaciones NFS
# Permitir solo a los servidores web (web1: .11, web2: .12)
# ---------------------------------------------------------------------------
cat > /etc/exports << 'EOF'
/srv/nfs/www         192.168.10.11(rw,sync,no_subtree_check,no_root_squash) 192.168.10.12(rw,sync,no_subtree_check,no_root_squash)
/srv/nfs/motd        192.168.10.11(rw,sync,no_subtree_check,no_root_squash) 192.168.10.12(rw,sync,no_subtree_check,no_root_squash)
/srv/nfs/issue       192.168.10.11(rw,sync,no_subtree_check,no_root_squash) 192.168.10.12(rw,sync,no_subtree_check,no_root_squash)
/srv/nfs/apache2     192.168.10.11(rw,sync,no_subtree_check,no_root_squash) 192.168.10.12(rw,sync,no_subtree_check,no_root_squash)
EOF

# ---------------------------------------------------------------------------
# Arrancar y habilitar NFS
# ---------------------------------------------------------------------------
systemctl enable nfs-kernel-server
exportfs -ra
systemctl restart nfs-kernel-server

echo "==> NFS Server listo. Exportaciones activas:"
exportfs -v