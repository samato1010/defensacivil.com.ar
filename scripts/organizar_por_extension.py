"""
Agente organizador de archivos por extension.

Recorre todas las carpetas dentro de:
  C:\Users\Sebastian\Downloads\DIRVE GUARDA TEMPORAL

Y organiza los archivos en subcarpetas con el nombre de su extension.

Ejemplo:
  archivo.pdf  -> /PDF/archivo.pdf
  foto.jpg     -> /JPG/foto.jpg
  datos.xlsx   -> /XLSX/datos.xlsx

Instrucciones:
  1. Instalar Python 3 si no lo tenes
  2. Abrir CMD o PowerShell
  3. Ejecutar: python organizar_por_extension.py
  4. Revisar el archivo LOG generado en la misma carpeta
"""

import os
import shutil
from datetime import datetime

CARPETA_RAIZ = r"C:\Users\Sebastian\Downloads\DIRVE GUARDA TEMPORAL"


def obtener_extension(nombre_archivo):
    """Devuelve la extension en mayusculas sin el punto, o 'SIN_EXTENSION'."""
    _, ext = os.path.splitext(nombre_archivo)
    if ext:
        return ext[1:].upper()
    return "SIN_EXTENSION"


def organizar_carpeta(carpeta, log):
    """Organiza los archivos de una carpeta en subcarpetas por extension."""
    movidos = 0
    errores = 0

    archivos = [f for f in os.listdir(carpeta)
                if os.path.isfile(os.path.join(carpeta, f))]

    for nombre in archivos:
        origen = os.path.join(carpeta, nombre)
        extension = obtener_extension(nombre)
        carpeta_destino = os.path.join(carpeta, extension)

        try:
            if not os.path.exists(carpeta_destino):
                os.makedirs(carpeta_destino)

            destino = os.path.join(carpeta_destino, nombre)

            # Si ya existe un archivo con el mismo nombre, renombrar
            if os.path.exists(destino):
                base, ext = os.path.splitext(nombre)
                contador = 1
                while os.path.exists(destino):
                    nuevo_nombre = f"{base} ({contador}){ext}"
                    destino = os.path.join(carpeta_destino, nuevo_nombre)
                    contador += 1

            shutil.move(origen, destino)
            movidos += 1
            log.append(f"  OK: {nombre} -> {extension}/")

        except Exception as e:
            errores += 1
            log.append(f"  ERROR: {nombre} - {str(e)}")

    return movidos, errores


def main():
    if not os.path.exists(CARPETA_RAIZ):
        print(f"ERROR: No se encontro la carpeta: {CARPETA_RAIZ}")
        return

    print("=" * 50)
    print("AGENTE ORGANIZADOR POR EXTENSION")
    print("=" * 50)
    print(f"Carpeta raiz: {CARPETA_RAIZ}")
    print()

    log = []
    total_movidos = 0
    total_errores = 0
    carpetas_procesadas = 0

    # Recorrer carpeta raiz y todas las subcarpetas de primer nivel
    carpetas = [CARPETA_RAIZ]
    for item in os.listdir(CARPETA_RAIZ):
        ruta = os.path.join(CARPETA_RAIZ, item)
        if os.path.isdir(ruta):
            carpetas.append(ruta)

    for carpeta in carpetas:
        archivos = [f for f in os.listdir(carpeta)
                    if os.path.isfile(os.path.join(carpeta, f))]

        if not archivos:
            continue

        carpetas_procesadas += 1
        nombre_carpeta = os.path.basename(carpeta) or "RAIZ"
        log.append(f"\n[{nombre_carpeta}] - {len(archivos)} archivos")

        movidos, errores = organizar_carpeta(carpeta, log)
        total_movidos += movidos
        total_errores += errores

        print(f"  {nombre_carpeta}: {movidos} movidos, {errores} errores")

    # Resumen
    print()
    print("=" * 50)
    print(f"TOTAL: {total_movidos} archivos organizados")
    print(f"ERRORES: {total_errores}")
    print(f"CARPETAS PROCESADAS: {carpetas_procesadas}")
    print("=" * 50)

    # Guardar log
    fecha = datetime.now().strftime("%Y%m%d_%H%M%S")
    nombre_log = f"log_organizacion_{fecha}.txt"
    ruta_log = os.path.join(CARPETA_RAIZ, nombre_log)

    contenido_log = f"AGENTE ORGANIZADOR POR EXTENSION\n"
    contenido_log += f"Fecha: {datetime.now().strftime('%d/%m/%Y %H:%M:%S')}\n"
    contenido_log += f"Carpeta: {CARPETA_RAIZ}\n"
    contenido_log += f"Total movidos: {total_movidos}\n"
    contenido_log += f"Total errores: {total_errores}\n"
    contenido_log += "=" * 50
    contenido_log += "\n".join(log)

    with open(ruta_log, "w", encoding="utf-8") as f:
        f.write(contenido_log)

    print(f"\nLog guardado en: {ruta_log}")


if __name__ == "__main__":
    main()
