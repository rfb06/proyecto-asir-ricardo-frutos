# -*- mode: ruby -*-
# vi: set ft=ruby :

# =============================================================================
# Arquitectura:
#   Balanceador Web  (puente al exterior)
#        |
#   LAN Interna (192.168.10.0/24)
#        |
#   web1 + web2  (Apache2 + PHP, comparten /var/www/html via NFS)
#        |
#   LAN Datos (192.168.20.0/24)
#        |
#      SGBD (MariaDB)
# =============================================================================

Vagrant.configure("2") do |config|

  # Carpeta compartida con los ficheros de la aplicación web
  # (disponible en todas las VMs como /vagrant)
  # Vagrant la monta automáticamente desde el directorio del Vagrantfile

  # ---------------------------------------------------------------------------
  # NFS Server (comparte /var/www/html, /etc/motd, /etc/issue y configs Apache)
  # Actúa como servidor NFS en la LAN Interna
  # ---------------------------------------------------------------------------
  config.vm.define "nfs-server" do |nfs|
    nfs.vm.box = "debian/bookworm64"
    nfs.vm.hostname = "nfs-server"

    # LAN Interna
    nfs.vm.network "private_network", ip: "192.168.10.5",
      virtualbox__intnet: "lan-interna"

    nfs.vm.provider "virtualbox" do |vb|
      vb.name   = "nfs-server"
      vb.memory = 512
      vb.cpus   = 1
    end

    nfs.vm.provision "shell", path: "scripts/nfs-server.sh"
  end

  # ---------------------------------------------------------------------------
  # Servidor Web 1
  # ---------------------------------------------------------------------------
  config.vm.define "web1" do |web1|
    web1.vm.box = "debian/bookworm64"
    web1.vm.hostname = "web1"

    # LAN Interna
    web1.vm.network "private_network", ip: "192.168.10.11",
      virtualbox__intnet: "lan-interna"

    # LAN Datos
    web1.vm.network "private_network", ip: "192.168.20.11",
      virtualbox__intnet: "lan-datos"

    web1.vm.provider "virtualbox" do |vb|
      vb.name   = "web1"
      vb.memory = 512
      vb.cpus   = 1
    end

    web1.vm.provision "shell", path: "scripts/web.sh"
  end

  # ---------------------------------------------------------------------------
  # Servidor Web 2
  # ---------------------------------------------------------------------------
  config.vm.define "web2" do |web2|
    web2.vm.box = "debian/bookworm64"
    web2.vm.hostname = "web2"

    # LAN Interna
    web2.vm.network "private_network", ip: "192.168.10.12",
      virtualbox__intnet: "lan-interna"

    # LAN Datos
    web2.vm.network "private_network", ip: "192.168.20.12",
      virtualbox__intnet: "lan-datos"

    web2.vm.provider "virtualbox" do |vb|
      vb.name   = "web2"
      vb.memory = 512
      vb.cpus   = 1
    end

    web2.vm.provision "shell", path: "scripts/web.sh"
  end

  # ---------------------------------------------------------------------------
  # SGBD (MariaDB) — solo en LAN Datos
  # ---------------------------------------------------------------------------
  config.vm.define "sgbd" do |sgbd|
    sgbd.vm.box = "debian/bookworm64"
    sgbd.vm.hostname = "sgbd"

    # LAN Datos
    sgbd.vm.network "private_network", ip: "192.168.20.100",
      virtualbox__intnet: "lan-datos"

    sgbd.vm.provider "virtualbox" do |vb|
      vb.name   = "sgbd"
      vb.memory = 1024
      vb.cpus   = 1
    end

    sgbd.vm.provision "shell", path: "scripts/sgbd.sh"
  end

  # ---------------------------------------------------------------------------
  # Balanceador Web — ÚNICO con adaptador puente (accesible desde fuera)
  # ---------------------------------------------------------------------------
  config.vm.define "balanceador" do |bal|
    bal.vm.box = "debian/bookworm64"
    bal.vm.hostname = "balanceador"

    # Adaptador puente — acceso externo
    bal.vm.network "public_network"

    # LAN Interna (para llegar a los backends)
    bal.vm.network "private_network", ip: "192.168.10.1",
      virtualbox__intnet: "lan-interna"

    bal.vm.provider "virtualbox" do |vb|
      vb.name   = "balanceador"
      vb.memory = 512
      vb.cpus   = 1
    end

    bal.vm.provision "shell", path: "scripts/balanceador.sh"
  end

end
