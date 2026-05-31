from ldap3 import Server, Connection, ALL
import csv

# Configuración
DC = "dc01.tecnorural.local"
USUARIO = "TECNORURAL\\administrador"
PASSWORD = "Abc123"
BASE_DN = "DC=tecnorural,DC=local"

server = Server(DC, get_info=ALL)
conn = Connection(server, user=USUARIO, password=PASSWORD, auto_bind=True)

# Buscar equipos
conn.search(
    search_base=BASE_DN,
    search_filter="(objectClass=computer)",
    attributes=[
        "cn",
        "operatingSystem",
        "operatingSystemVersion",
        "dNSHostName"
    ]
)

with open("inventario_glpi.csv", "w", newline="", encoding="utf-8") as f:
    writer = csv.writer(f, delimiter=";")

    writer.writerow([
        "Nombre",
        "Hostname",
        "Sistema Operativo",
        "Versión"
    ])

    for equipo in conn.entries:
        writer.writerow([
            str(equipo.cn),
            str(equipo.dNSHostName) if equipo.dNSHostName else "",
            str(equipo.operatingSystem) if equipo.operatingSystem else "",
            str(equipo.operatingSystemVersion) if equipo.operatingSystemVersion else ""
        ])

conn.unbind()

print("Inventario exportado a inventario_glpi.csv")
