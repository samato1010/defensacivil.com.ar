# Agente organizador de archivos por extension.
#
# Recorre TODAS las carpetas y subcarpetas dentro de DIRVE GUARDA TEMPORAL
# y mueve todos los archivos a UNA SOLA carpeta por extension en la raiz.
#
# Ejemplo (resultado final en la raiz):
#   DIRVE GUARDA TEMPORAL/PDF/archivo.pdf
#   DIRVE GUARDA TEMPORAL/JPG/foto.jpg
#   DIRVE GUARDA TEMPORAL/XLSX/datos.xlsx
#
# Instrucciones:
#   1. Instalar Python 3 si no lo tenes
#   2. Abrir CMD o PowerShell
#   3. Ejecutar: python organizar_por_extension.py
#   4. Revisar el archivo LOG generado en la misma carpeta

import os
import shutil
from datetime import datetime

CARPETA_RAIZ = r"C:\Users\Sebastian\Downloads\DIRVE GUARDA TEMPORAL"


def obtener_extension(nombre_archivo):
    _, ext = os.path.splitext(nombre_archivo)
    if ext:
        return ext[1:].upper()
    return "SIN_EXTENSION"


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

    # Recolectar todos los archivos de todas las subcarpetas
    archivos_encontrados = []
    for raiz, dirs, archivos in os.walk(CARPETA_RAIZ):
        for nombre in archivos:
            ruta_completa = os.path.join(raiz, nombre)
            archivos_encontrados.append((ruta_completa, nombre, raiz))

    print(f"Archivos encontrados: {len(archivos_encontrados)}")
    print()

    for ruta_completa, nombre, carpeta_origen in archivos_encontrados:
        extension = obtener_extension(nombre)
        carpeta_destino = os.path.join(CARPETA_RAIZ, extension)

        # Si ya esta en la carpeta correcta de la raiz, saltar
        if carpeta_origen == carpeta_destino:
            continue

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

            ruta_relativa = os.path.relpath(carpeta_origen, CARPETA_RAIZ)
            if ruta_relativa == ".":
                ruta_relativa = "RAIZ"

            shutil.move(ruta_completa, destino)
            total_movidos += 1
            log.append(f"  OK: [{ruta_relativa}] {nombre} -> {extension}/")
            print(f"  {nombre} -> {extension}/")

        except Exception as e:
            total_errores += 1
            log.append(f"  ERROR: {nombre} - {str(e)}")
            print(f"  ERROR: {nombre} - {str(e)}")

    # Eliminar carpetas vacias que quedaron
    carpetas_eliminadas = 0
    for raiz, dirs, archivos in os.walk(CARPETA_RAIZ, topdown=False):
        if raiz == CARPETA_RAIZ:
            continue
        try:
            if not os.listdir(raiz):
                os.rmdir(raiz)
                carpetas_eliminadas += 1
        except Exception:
            pass

    # Resumen
    print()
    print("=" * 50)
    print(f"TOTAL: {total_movidos} archivos organizados")
    print(f"ERRORES: {total_errores}")
    print(f"CARPETAS VACIAS ELIMINADAS: {carpetas_eliminadas}")
    print("=" * 50)

    # Guardar log
    fecha = datetime.now().strftime("%Y%m%d_%H%M%S")
    nombre_log = f"log_organizacion_{fecha}.txt"
    ruta_log = os.path.join(CARPETA_RAIZ, nombre_log)

    contenido_log = "AGENTE ORGANIZADOR POR EXTENSION\n"
    contenido_log += f"Fecha: {datetime.now().strftime('%d/%m/%Y %H:%M:%S')}\n"
    contenido_log += f"Carpeta: {CARPETA_RAIZ}\n"
    contenido_log += f"Total movidos: {total_movidos}\n"
    contenido_log += f"Total errores: {total_errores}\n"
    contenido_log += f"Carpetas vacias eliminadas: {carpetas_eliminadas}\n"
    contenido_log += "=" * 50
    contenido_log += "\n".join(log)

    with open(ruta_log, "w", encoding="utf-8") as f:
        f.write(contenido_log)

    print(f"\nLog guardado en: {ruta_log}")


if __name__ == "__main__":
    main()
