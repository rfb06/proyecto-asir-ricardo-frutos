import tkinter as tk
from tkinter import messagebox
import webbrowser

BASE_URL = "http://192.168.56.10/front/ticket.form.php?id="

def abrir_ticket():
    ticket_id = entrada.get().strip()

    if not ticket_id:
        messagebox.showerror("Error", "Introduce un ID de incidencia.")
        return

    if not ticket_id.isdigit():
        messagebox.showerror("Error", "El ID debe ser numérico.")
        return

    webbrowser.open(BASE_URL + ticket_id)

# Ventana
root = tk.Tk()
root.title("Abridor de Incidencias")
root.geometry("400x150")
root.resizable(False, False)

tk.Label(
    root,
    text="ID de incidencia",
    font=("Arial", 12)
).pack(pady=10)

entrada = tk.Entry(root, font=("Arial", 12), width=20)
entrada.pack()
entrada.focus()

tk.Button(
    root,
    text="Abrir incidencia",
    font=("Arial", 11),
    command=abrir_ticket
).pack(pady=15)

# Permite pulsar Enter
entrada.bind("<Return>", lambda event: abrir_ticket())

root.mainloop()