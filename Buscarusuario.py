from ldap3 import Server, Connection, ALL

# Configuración
SERVIDOR = "tecnorural.local"
USUARIO_AD = "TECNORURAL\\administrador"
PASSWORD = "Abc123"

usuario = input("Usuario a buscar: ")

# Conexión al dominio
server = Server(SERVIDOR, get_info=ALL)
conn = Connection(SERVIDOR, user=USUARIO_AD, password=PASSWORD, auto_bind=True)

# Buscar usuario
conn.search(
    search_base='DC=tecnorural,DC=local',
    search_filter=f'(sAMAccountName={usuario})',
    attributes=['cn', 'description']
)

if conn.entries:
    user = conn.entries[0]
    print(f"Nombre: {user.cn}")
    print(f"PC: {user.description}")
else:
    print("Usuario no encontrado")

conn.unbind()
