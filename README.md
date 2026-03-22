# TecnoRural - Proyecto de Servicios en Red

![TecnoRural Logo](tecnorural.png)

**Autor:** Ricardo Frutos Bravo  
**Empresa:** TecnoRural S.L. - Cadena de tiendas de informatica en Extremadura  
**Sede central:** Merida, Badajoz

---

## Descripcion del proyecto

Infraestructura de red completa para TecnoRural S.L., desplegada con Vagrant + VirtualBox sobre Debian 12 (bookworm). Incluye balanceo de carga, almacenamiento NFS compartido, base de datos MariaDB y una aplicacion web PHP con Bootstrap 5.

---

## Apertura de la primera tienda - Merida 2026

![Apertura primera tienda TecnoRural Merida](tienda.png)

En enero de 2026 Ricardo abrio su primera tienda en **Merida, Extremadura**. Desde primera hora de la manana se formo una cola de vecinos esperando para entrar, lo que demostro que la idea de Ricardo tenia mucho sentido: en la zona no habia ninguna tienda de informatica cercana y la gente llevaba anos necesitando este servicio.

La tienda de Merida fue la primera de la cadena en ofrecer tambien servicio tecnico presencial, con un mostrador de recepcion de equipos para reparacion. El dia de la apertura se vendieron mas de 40 equipos y se abrieron las primeras incidencias tecnicas en el sistema.

Esta primera tienda en Merida se convirtio rapidamente en la mas importante de la cadena y fue la razon por la que Ricardo decidio instalar aqui la sede central de TecnoRural.

---

## La Torre TecnoRural - Sede Central en Merida

![Torre TecnoRural - Sede central en Merida](torre_tecnorural.png)

En 2026, con el exito de la primera tienda y la expansion de la cadena por toda Extremadura, Ricardo tomo la decision de establecer la **sede central de TecnoRural** en un edificio emblematico del centro de Merida. El letrero verde de TECNO RURAL en lo alto de la torre se convirtio rapidamente en un simbolo reconocible de la ciudad.

El edificio alberga en su planta baja la tienda mas grande de la cadena, con todos los productos expuestos y el mostrador de atencion tecnica. En las plantas superiores se encuentran las oficinas de administracion, la sala de servidores donde esta alojada toda la infraestructura informatica de la empresa, y una sala de reuniones desde la que se coordina la logistica de las demas tiendas.

Desde la Torre TecnoRural en Merida se gestiona:

- Los pedidos a proveedores para todas las tiendas de la cadena
- Las incidencias tecnicas que llegan a traves de la aplicacion web
- La contabilidad y administracion central
- La ruta semanal de reparto de stock a las tiendas de los pueblos de alrededor
- El equipo de tecnicos que dan soporte a toda Extremadura

La sede en Merida fue posible gracias al crecimiento rapido de la empresa y a la confianza que los vecinos de Extremadura depositaron en TecnoRural desde el primer dia.

---

### Maquinas virtuales

| VM           | Box                | RAM   | Red 1                        | Red 2                      |
|--------------|--------------------|-------|------------------------------|----------------------------|
| balanceador  | debian/bookworm64  | 512MB | public_network (puente DHCP) | lan-interna 192.168.10.1   |
| nfs-server   | debian/bookworm64  | 512MB | lan-interna 192.168.10.5     | -                          |
| web1         | debian/bookworm64  | 512MB | lan-interna 192.168.10.11    | lan-datos 192.168.20.11    |
| web2         | debian/bookworm64  | 512MB | lan-interna 192.168.10.12    | lan-datos 192.168.20.12    |
| sgbd         | debian/bookworm64  | 1024MB| lan-datos 192.168.20.100     | -                          |

---

## Arranque

### Orden recomendado

```bash
vagrant up nfs-server
vagrant up web1 web2
vagrant up sgbd
vagrant up balanceador
```

### Verificar que funciona

```bash
# Ver la IP puente del balanceador
vagrant ssh balanceador -c "ip addr show | grep inet"

# Comprobar que los backends responden
vagrant ssh balanceador -c "curl http://192.168.10.11/"
vagrant ssh balanceador -c "curl http://192.168.10.12/"

# Ver estado de HAProxy
echo "show stat" | vagrant ssh balanceador -c "sudo socat stdio /run/haproxy/admin.sock" | cut -d',' -f1,2,18
```

Panel de estadisticas HAProxy: `http://<IP_PUENTE>:8080/stats` (admin / admin)

---

## Desplegar cambios en la app PHP

Como `/var/www/html` esta montado por NFS, los cambios en `nfs-server` se aplican inmediatamente en web1 y web2:

```bash
vagrant ssh nfs-server
sudo cp /vagrant/tecnorural/*.php /srv/nfs/www/
sudo cp -r /vagrant/tecnorural/includes /srv/nfs/www/
sudo chown -R 33:33 /srv/nfs/www/
```

---



## Problemas habituales

| Error                        | Causa                              | Solucion                                              |
|------------------------------|------------------------------------|-------------------------------------------------------|
| 503 Service Unavailable      | Backends DOWN en HAProxy           | `systemctl restart apache2` en web1 y web2            |
| 500 Permission denied        | www-data no puede leer /var/www    | `sudo chown -R 33:33 /srv/nfs/www/` en nfs-server     |
| script not found             | Ficheros PHP no copiados al NFS    | `sudo cp /vagrant/tecnorural/*.php /srv/nfs/www/`     |
| NFS no monta al arrancar     | nfs-server no estaba listo antes   | `vagrant ssh web1` + `sudo mount -a`                  |
| Bucle de redireccion         | Guard PHP con sesion activa        | Limpiar cookies o revisar guards en los PHP           |
| /vagrant vacio en nfs-server | Vagrant no monta /vagrant sin NAT  | Copiar ficheros manualmente via `vagrant ssh`          |

---

## Referencias y tutoriales usados

### PHP + MySQL CRUD

Estas guias me ayudaron a entender como hacer el inventario y la gestion de incidencias con PHP y MySQL:

- **From Zero to CRUD: PHP + MySQL in 30 Minutes**  
  https://medium.com/@annxsa/from-zero-to-crud-php-mysql-in-30-minutes-6c5102f9da58  
  Guia practica para principiantes que explica como crear una app CRUD completa con PHP y MySQL usando prepared statements desde el principio.

- **The Easiest Way to Build a CRUD App with PHP and MySQL**  
  https://medium.com/@biswajitpanda973/mastering-to-do-list-crud-application-with-core-php-and-mysql-22b85304eca6  
  Tutorial paso a paso con una app de lista de tareas. Explica bien la diferencia entre `$conn->prepare()` y `$conn->query()` y cuando usar cada uno.

- **Building a Simple CRUD Application with PHP and MySQL**  
  https://medium.com/@wwwebadvisor/building-a-simple-crud-application-with-php-and-mysql-bcc45f0c0b16  
  Muestra como estructurar los ficheros del proyecto (db.php, create.php, index.php) y como usar `bind_param` para insertar datos de forma segura.

- **PHP/MySQL CRUD App - Part 1**  
  https://johnwolfe820.medium.com/building-a-php-mysql-crud-app-part-1-74f356e66476  
  Serie de articulos que explica la conexion a MySQL con MAMP y como organizar el proyecto desde cero.

- **PHP CRUD operations with MySQL and Bootstrap**  
  https://medium.com/@ldudaraliyanage/php-crud-operations-with-mysql-and-html-bootstrap-2022-d4aca5569b6a  
  Combina Bootstrap 5 con PHP y MySQL. Muy util para ver como integrar el diseno con la logica del servidor.

- **Dashboard Tutorial II: HTML, CSS, PHP and Heroku**  
  https://medium.com/data-science/dashboard-tutorial-ii-html-css-php-and-heroku-e8bdb7f26783  
  Tutorial sobre como hacer un dashboard con PHP. Sirve de referencia para la estructura del panel de supervisor y el dashboard de tecnicos.

---

### PHP Sessions y Login

Para el sistema de login con roles (usuario / supervisor Ricardo) use estas guias:

- **How to Build a PHP Login Form Using Sessions**  
  https://medium.com/@jpmorris/how-to-build-a-php-login-form-using-sessions-c7fb6d8ecebe  
  Explica el flujo completo de autenticacion con PHP sessions: crear la sesion, proteger paginas con guards, y hacer el logout con `session_destroy()`.

- **Authentication with session in PHP**  
  https://nimaxz.medium.com/authentication-with-session-in-php-bfb6ea639e53  
  Ejemplo sencillo de como funciona `$_SESSION` para mantener al usuario logueado entre paginas.

- **Creating a Simple Login System with PHP**  
  https://altelma.medium.com/creating-a-simple-login-system-with-php-7f00456e3afb  
  Muestra como usar `password_verify()` para comprobar contrasenas hasheadas con bcrypt, que es lo que uso en este proyecto.

- **Building a Login System with Core PHP and MySQL**  
  https://medium.com/@biswajitpanda973/login-form-using-php-and-mysql-abc1f35ba4d0  
  Tutorial completo de registro + login. Incluye como guardar el hash de la contrasena con `password_hash()` y como hacer el welcome page tras el login.

- **Mastering Session Management in PHP**  
  https://medium.com/@folasayosamuelolayemi/mastering-session-management-in-php-a-guide-to-storing-and-passing-user-data-between-pages-04f3ea893515  
  Explica bien como pasar datos del usuario entre paginas usando `$_SESSION` y como gestionar la expiracion de sesiones.

- **How To Set Up Authentication With PHP**  
  https://medium.com/@vivianatuake/how-to-set-up-authentication-with-php-81b405af03b7  
  Guia mas completa sobre autenticacion que incluye buenas practicas de seguridad para el sistema de login.

---

### PDO y Prepared Statements

Para la conexion segura a la base de datos use PDO con prepared statements:

- **Preparing for Prepared Statements (PDO guide)**  
  https://medium.com/coding-design/preparing-for-prepared-statements-619292bc4d8a  
  Muy buena guia para entender por que usar PDO en lugar de las funciones `mysql_` antiguas. Explica como PDO separa la query de los datos para evitar SQL injection.

- **How to use Parameterized Queries or Prepared Statements in PHP**  
  https://medium.com/@rashid.khitilov/how-to-use-parameterized-queries-or-prepared-statements-in-php-e20d9790bfd8  
  Ejemplo claro de como usar `bindParam()` y como PDO maneja automaticamente el escape de los valores para proteger contra inyecciones SQL.

- **PDO: PHP Data Object**  
  https://medium.com/@main.guruteja/pdo-php-data-object-c2cad02c9016  
  Explica los metodos basicos de PDO: `query()`, `prepare()`, `execute()`, `fetch()` y `fetchAll()`. Muy util para entender cuando usar cada uno.

---

### Bootstrap 5

Para el diseno de la interfaz use Bootstrap 5 via CDN:

- **Mastering Bootstrap 5 Forms: The Complete Guide**  
  https://medium.com/coinmonks/mastering-bootstrap-5-forms-a-complete-beginner-to-pro-guide-7eb7f3a77172  
  Guia completa de formularios con Bootstrap 5. Explica `form-control`, `form-select`, `form-check` y como hacer validacion visual.

- **CSS Grid Layout - Basic Concepts (MDN)**  
  https://developer.mozilla.org/en-US/docs/Web/CSS/Guides/Grid_layout/Basic_concepts  
  Documentacion oficial de MDN sobre CSS Grid. Util para entender el sistema de columnas que usa Bootstrap por debajo.

- **Bootstrap 5 Official Documentation**  
  https://getbootstrap.com/docs/5.3/  
  Documentacion oficial de Bootstrap 5 con todos los componentes: cards, modals, tables, navbar, badges, alerts, etc.

---

### Vagrant y VirtualBox

- **Vagrant Official Documentation**  
  https://developer.hashicorp.com/vagrant/docs  
  Documentacion oficial para entender como funciona el Vagrantfile, los provisioners, las redes y los synced folders.

- **Vagrant Networking - Private Networks**  
  https://developer.hashicorp.com/vagrant/docs/networking/private_network  
  Explica como configurar `private_network` con `virtualbox__intnet` para crear redes internas entre VMs.

---

### NFS

- **NFS Server on Debian**  
  https://wiki.debian.org/NFSServerSetup  
  Guia oficial de Debian para configurar el servidor NFS, las exportaciones en `/etc/exports` y las opciones `rw,sync,no_root_squash`.

---

## Tecnologias usadas

| Tecnologia       | Version   | Para que se usa                                      |
|------------------|-----------|------------------------------------------------------|
| VirtualBox       | 7.x       | Hipervisor para las VMs                              |
| Vagrant          | 2.x       | Aprovisionamiento automatico de las VMs              |
| Debian Bookworm  | 12        | Sistema operativo de todas las VMs                   |
| HAProxy          | 2.x       | Balanceador de carga con health checks               |
| Apache2          | 2.4       | Servidor web en web1 y web2                          |
| PHP              | 8.2       | Lenguaje del lado servidor para la app web           |
| MariaDB          | 10.11     | Base de datos relacional                             |
| NFS              | 4         | Almacenamiento compartido entre servidores web       |
| Bootstrap        | 5.3.2     | Framework CSS/JS para la interfaz                    |
| PDO              | -         | Capa de abstraccion para consultas a la BD           |
