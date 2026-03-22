#!/bin/bash
# =============================================================================
# balanceador.sh — Balanceador web con HAProxy
# Único nodo accesible desde el exterior (adaptador puente)
# Balancea entre web1 (192.168.10.11) y web2 (192.168.10.12)
# =============================================================================

set -e

export DEBIAN_FRONTEND=noninteractive

echo "==> Actualizando paquetes..."
apt-get update -qq
apt-get install -y -qq haproxy

# ---------------------------------------------------------------------------
# Configuración HAProxy
# ---------------------------------------------------------------------------
cat > /etc/haproxy/haproxy.cfg << 'EOF'
global
    log /dev/log    local0
    log /dev/log    local1 notice
    chroot /var/lib/haproxy
    stats socket /run/haproxy/admin.sock mode 660 level admin expose-fd listeners
    stats timeout 30s
    user haproxy
    group haproxy
    daemon

defaults
    log     global
    mode    http
    option  httplog
    option  dontlognull
    timeout connect  5000ms
    timeout client   50000ms
    timeout server   50000ms
    errorfile 400 /etc/haproxy/errors/400.http
    errorfile 403 /etc/haproxy/errors/403.http
    errorfile 408 /etc/haproxy/errors/408.http
    errorfile 500 /etc/haproxy/errors/500.http
    errorfile 502 /etc/haproxy/errors/502.http
    errorfile 503 /etc/haproxy/errors/503.http
    errorfile 504 /etc/haproxy/errors/504.http

# ---------------------------------------------------------------------------
# Frontend: escucha en el puerto 80 (adaptador puente)
# ---------------------------------------------------------------------------
frontend http_front
    bind *:80
    default_backend http_back

# ---------------------------------------------------------------------------
# Backend: balanceo roundrobin entre web1 y web2
# ---------------------------------------------------------------------------
backend http_back
    balance     roundrobin
    option      httpchk GET /index.php
    http-check  expect status 200
    server      web1 192.168.10.11:80 check inter 3s rise 2 fall 3
    server      web2 192.168.10.12:80 check inter 3s rise 2 fall 3

# ---------------------------------------------------------------------------
# Página de estadísticas HAProxy (accesible en :8080/stats)
# ---------------------------------------------------------------------------
listen stats
    bind *:8080
    stats enable
    stats uri /stats
    stats refresh 10s
    stats show-legends
    stats show-node
    stats auth admin:admin
EOF

# ---------------------------------------------------------------------------
# Habilitar y arrancar HAProxy
# ---------------------------------------------------------------------------
systemctl enable haproxy
systemctl restart haproxy

echo "==> Balanceador listo"
echo "    - HAProxy escuchando en :80 (roundrobin → web1, web2)"
echo "    - Estadísticas en http://<IP_PUENTE>:8080/stats (admin/admin)"
