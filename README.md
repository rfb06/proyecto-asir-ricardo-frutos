# TecnoRural - Proyecto de Servicios en Red

![TecnoRural Logo](tecnorural.png)

# TecnoRural — Infraestructura


---

## Índice

- [Resumen](#resumen)
- [Descripción de la Empresa](#descripción-de-la-empresa)
- [Infraestructura](#infraestructura)
- [Windows Admin Center (WAC)](#windows-admin-center-wac)
- [Windows Deployment Services (WDS)](#windows-deployment-services-wds)
- [Active Directory](#active-directory)
- [Inventariado — GLPI + FusionInventory](#inventariado--glpi--fusioninventory)
- [Acceso Remoto — MeshCentral](#acceso-remoto--meshcentral)
- [Seguridad](#seguridad)
- [Resolución de Problemas](#resolución-de-problemas)
- [Glosario](#glosario)
- [Propuestas de Mejora](#propuestas-de-mejora)

---

## Resumen

TecnoRural es una empresa tecnológica especializada en soluciones IT para cooperativas agrarias, explotaciones ganaderas y organismos rurales. Esta documentación describe la infraestructura tecnológica interna, articulada en cuatro bloques principales:

| Bloque | Herramienta | DNS interno |
|---|---|---|
| Administración centralizada | Windows Admin Center (WAC) | `192.168.56.254` |
| Despliegue automatizado de SO | Windows Deployment Services (WDS) | `192.168.56.254` |
| Inventariado de activos IT | GLPI + FusionInventory | `incidencias.tecnorural.es` |
| Acceso remoto y soporte | MeshCentral | `meshcentral.tecnorural.es` |

---

## Descripción de la Empresa

**Sector:** Tecnología para el sector rural y agrícola  
**Empleados:** 30–40  
**Presencia:** Oficina central + sedes rurales en Extremadura (Badajoz y Cáceres)

---

## Infraestructura

### Requisitos de Hardware

- RAM: 16 GB mínimo
- CPU: 8 núcleos a 2,5 GHz mínimo

### Software

| Capa | Software | Versión |
|---|---|---|
| SO Servidor | Windows Server | 2022 Standard / Datacenter |
| SO Clientes | Windows 10 Pro | Última versión |
| SO GLPI / MeshCentral | Debian | 12 Bookworm |
| Administración | Windows Admin Center | v2 preview |
| Inventariado | GLPI | GLPI 11 |
| Acceso Remoto | MeshCentral | Última (Node.js) |
| Despliegue SO | WDS + MDT + WinPE |  |

### Virtualización

Los servicios GLPI y MeshCentral corren en **máquinas virtuales Vagrant** dentro de la red interna. Ambas son inaccesibles directamente desde el exterior.

### Exposición Web (Landing Page)

La landing page pública alojada en Cáceres se expone mediante:

- **Ngrok** — reverse proxy que crea un túnel seguro sin necesidad de IP pública estática.
---

## Windows Admin Center (WAC)

WAC centraliza la administración de toda la infraestructura Windows desde una **interfaz web unificada**, sin necesidad de RDP ni RSAT.

- **Modo de despliegue:** Gateway Mode sobre Windows Server 2022
- **Puerto:** 443 (HTTPS)
- **Autenticación:** Kerberos / NTLM con credenciales de dominio
- **Acceso restringido a:** grupo `Administrators` (red `192.168.56.0/24`)

### Funcionalidades principales

- Monitorización en tiempo real de CPU, RAM, disco y red de todos los nodos.
- Gestión de servicios, visor de eventos y ejecución de PowerShell remoto.
- Administración de Active Directory (usuarios, grupos, OUs, contraseñas).
- Gestión de almacenamiento, volúmenes y recursos compartidos SMB.

---

## Windows Deployment Services (WDS)

WDS permite el **despliegue automatizado de Windows por red (PXE)** sin medios físicos.

**Servidor:** `192.168.56.254`

### Flujo de autoaprovisionamiento

```
1. Equipo nuevo encendido en la red
2. Solicitud DHCP + petición PXE broadcast
3. DHCP asigna IP → indica dirección del servidor WDS
4. WDS entrega fichero de arranque (bootmgfw.efi / pxeboot.n12)
5. Equipo carga WinPE desde la red
6. WinPE lanza instalador con unattend.xml (desatendido)
7. Windows se instala y se une al dominio TecnoRural.local
8. Equipo reinicia completamente configurado ✓
```

### Imágenes de arranque

| Imagen | Arquitectura | Uso |
|---|---|---|
| `boot.wim` (WinPE 11) | x64 UEFI | Equipos modernos (uso principal) |
| `boot_legacy.wim` (WinPE 10) | x64 BIOS | Equipos con BIOS Legacy |
| `boot_diag.wim` | x64 | WinPE de diagnóstico |

### Imágenes de instalación

| Grupo WDS | Sistema Operativo | Destinatarios |
|---|---|---|
| `TecnoRural_W10` | Windows 10 LTSC 2021 x64 | Equipos de campo y sedes rurales |

### Cuenta de servicio WDS (`UsuarioAprovision`)

Cuenta de dominio con **mínimo privilegio** para unir equipos al dominio:

- Escritura únicamente sobre la OU `Computers`
- Límite de 100 uniones al dominio por día
- Sin login interactivo ni acceso a recursos compartidos
- Contraseña sin caducidad (cuenta de servicio)

### Integración con MDT

MDT extiende WDS con automatización avanzada:

- Instalación automática de aplicaciones post-despliegue (FusionInventory, MeshAgent, antivirus, ofimática).
- Scripts PostInstall (renombrado de equipo, impresoras de red, mapeo de unidades).
- Selección dinámica de OU en AD según modelo del equipo.

---

## Active Directory

**Dominio:** `TecnoRural.es`  
**Servidor:** `192.168.56.254` (DC principal + DHCP + DNS + WDS + WAC)


### Grupos de seguridad

| Grupo | Permisos principales |
|---|---|
| `Infraestructura_TecnoRural` | Acceso total WAC, AD, servidores |
| `Helpdesk_TecnoRural` | WAC lectura, MeshCentral operador |
| `Administracion_TecnoRural` | Recursos compartidos Administración |
| `Desarrollo_TecnoRural` | Repositorios, servidores dev |
| `Directivos_TecnoRural` | Perfiles móviles, recursos dirección |
| `Soporte_TecnoRural` | MeshCentral operador, VPN campo |
| `GLPI_Lectores` | Solo lectura en GLPI |
| `GLPI_Gestores` | Gestión completa GLPI |

**Roles funcionales:** `Administrador` · `Programador` · `Tecnico` · `Supervisor`

---

## Inventariado — GLPI + FusionInventory

**URL:** `http://incidencias.tecnorural.es/glpi`  
**SO servidor:** Debian 12 Bookworm (VM Vagrant)

### Stack del servidor GLPI

| Componente | Versión | Función |
|---|---|---|
| Apache | 2 | Servidor web |
| PHP | 8 | Runtime GLPI |
| MariaDB | 10 | Base de datos |
| GLPI | 11 | Gestión de activos y tickets |
| Plugin FusionInventory | 6.x | Recepción de inventarios |

### Datos recopilados por FusionInventory

Hardware (fabricante, modelo, serie, BIOS), CPU, RAM, almacenamiento, red (MAC, IP), SO, software instalado, actualizaciones Windows, usuarios logueados y periféricos.

### Scripts de aprovisionamiento (ejecutados en cada inicio de sesión)

```bat
:: Script 1 — Instala el agente MeshCentral si no está presente
@echo off
if exist "C:\Program Files\Mesh Agent\MeshAgent.exe" exit /b 0
\\Tecnorural\Agentes\agente.exe -fullinstall
exit /b 0

:: Script 2 — Instala/actualiza el agente GLPI
msiexec /i \\Tecnorural\Agentes\GLPI-Agent-1.17-x64.msi /quiet ^
  SERVER=http://incidencias.tecnorural.es/front/inventory.php ^
  RUNNOW=1 EXECMODE=Service TASKS=Inventory
```

### Flujo de autoaprovisionamiento completo

```
PXE → WDS despliega Windows → se une a OU Computers
→ GPO instala FusionInventory → primer inventariado enviado a GLPI
→ GLPI crea el activo automáticamente
→ Técnico verifica, asigna usuario y mueve a OU definitiva
→ Equipo recibe GPOs de departamento y queda operativo ✓
```

---

## Acceso Remoto — MeshCentral

**URL:** `https://meshcentral.tecnorural.es`  
**SO servidor:** Debian 12 Bookworm (VM Vagrant)

### Stack del servidor MeshCentral

| Componente | Versión | Función |
|---|---|---|
| Node.js | 20 LTS | Runtime MeshCentral |
| MeshCentral | Última estable | Servidor de gestión remota |
| NeDB | Integrado | BD interna (dispositivos, usuarios, logs) |
| Nginx | 1.24 | Reverse proxy HTTPS + TLS |

### Funcionalidades para soporte técnico

- **Escritorio remoto** — Control completo o solo visualización desde el navegador, con consentimiento configurable, ajuste de calidad y transferencia de portapapeles.
- **Consola remota** — Acceso a CMD, PowerShell (Windows) o bash (Linux) directamente en el navegador.
- **Transferencia de ficheros** — Gestor bidireccional para subir/descargar ficheros y explorar el sistema de archivos remoto.
- **Wake-on-LAN** — Encendido remoto de equipos apagados registrados en la plataforma.
- **Auditoría de sesiones** — Registro completo (técnico, equipo, fecha/hora, tipo de acceso) con grabación de sesión opcional. Necesario para cumplimiento **RGPD**.

### Flujo de soporte remoto

```
1. Trabajador abre incidencia en GLPI
2. Técnico recibe notificación y accede a MeshCentral
3. Localiza el equipo (convención: TR-DEPT-NNN)
4. Inicia sesión remota (con aviso de consentimiento si está configurado)
5. Resuelve la incidencia
6. Añade notas de resolución en el ticket GLPI
7. Sesión queda registrada en log de auditoría ✓
```

---

## Seguridad

### Principios aplicados

- **Mínimo privilegio** — Cada cuenta y servicio accede únicamente a lo imprescindible.
- **Centralización** — Active Directory como único punto de gestión de identidades.
- **Auditoría continua** — Todas las operaciones en WAC, GLPI y MeshCentral quedan registradas.

### Reglas de red principales

| Origen | Destino | Puerto | Descripción |
|---|---|---|---|
| Todos los equipos del dominio | `incidencias.tecnorural.es` | TCP 80 | Portal GLPI |
| Agentes MeshCentral | `meshcentral.tecnorural.es` | TCP 443 | Canal agente-servidor (TLS 1.3) |
| Equipos del dominio | `192.168.56.254` | UDP 67/68 | DHCP |
| Equipos PXE | `192.168.56.254` | UDP 4011 | WDS ProxyDHCP |

---

## Resolución de Problemas

| Síntoma | Causa probable | Solución |
|---|---|---|
| Equipo no arranca por PXE | PXE desactivado en BIOS o opciones DHCP 066/067 incorrectas | Verificar opciones DHCP y boot order en BIOS |
| WDS no une equipo al dominio | Contraseña `UsuarioAprovision` expirada o sin permisos en OU | Revisar contraseña en AD y permisos en OU `Computers` |
| FusionInventory no reporta a GLPI | Servicio detenido o URL incorrecta | Reiniciar servicio; verificar URL en `fusioninventory-agent.cfg` |
| Agente MeshCentral offline | Servicio detenido o firewall bloqueando TCP 443 | `services.msc` → Mesh Agent → Reiniciar; revisar firewall |
| WAC inaccesible | Servicio `ServerManagementGateway` detenido o certificado caducado | Iniciar servicio; renovar certificado en WAC Settings |
| GPO no se aplica tras despliegue | Equipo aún en OU `Computers` o retraso AD | `gpupdate /force`; verificar OU con `Get-ADComputer` |
| GLPI no importa usuarios de AD | Contraseña cuenta GLPI expirada o filtro LDAP incorrecto | Verificar credenciales y test de conexión LDAP en GLPI |

---

## Glosario

| Término | Definición |
|---|---|
| **GLPI Inventory Agent** | Agente de inventariado que recopila hardware/software y lo envía a GLPI. |
| **GLPI** | Gestionnaire Libre de Parc Informatique. Plataforma web para gestión de activos IT y tickets. |
| **MDT** | Microsoft Deployment Toolkit. Extiende WDS con automatización avanzada de despliegues. |
| **MeshCentral** | Plataforma de acceso remoto open-source para gestionar equipos Windows/Linux desde el navegador. |
| **PXE** | Pre-boot eXecution Environment. Estándar para arrancar un equipo desde la red. |
| **WAC** | Windows Admin Center. Panel web unificado para administrar infraestructuras Windows. |
| **WDS** | Windows Deployment Services. Rol de Windows Server para despliegue de SO por red. |
| **WIM** | Windows Imaging Format. Formato de imagen de disco para instalaciones de Windows. |
| **WinPE** | Windows Preinstallation Environment. Versión mínima de Windows para el proceso de instalación PXE. |

---

## Propuestas de Mejora

- **DC secundario** — Eliminar el punto único de fallo del directorio activo con un segundo controlador de dominio.
- **CA interna (AD CS)** — Sustituir certificados autofirmados por certificados de CA interna para eliminar advertencias en navegadores.
- **Backups automatizados** — Copias nocturnas de las BBDD de GLPI y MeshCentral hacia el servidor de ficheros del dominio.
- **Integración GLPI ↔ MeshCentral** — Vincular automáticamente cada sesión de acceso remoto con su ticket de incidencia en GLPI.
- **Monitorización** — Añadir Zabbix o Prometheus con alertas automáticas por correo o Teams.
- **Captura de imágenes WDS** — Implementar proceso de captura WIM desde equipo de referencia para mantener imágenes actualizadas.

---
