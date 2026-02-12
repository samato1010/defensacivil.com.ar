/**
 * Script para mover archivos de Google Drive sin uso en mas de 2 anios
 * a una carpeta llamada "GUARDA TEMPORAL".
 *
 * Instrucciones:
 *   1. Abrir https://script.google.com
 *   2. Crear un nuevo proyecto
 *   3. Pegar este codigo
 *   4. Ejecutar la funcion "moverArchivosViejos"
 *   5. Autorizar los permisos cuando se solicite
 */

function moverArchivosViejos() {
  var nombreCarpeta = "GUARDA TEMPORAL";
  var diasLimite = 730; // 2 anios = 730 dias

  // Fecha limite: hoy menos 2 anios
  var fechaLimite = new Date();
  fechaLimite.setDate(fechaLimite.getDate() - diasLimite);

  // Buscar o crear la carpeta destino
  var carpetaDestino = obtenerOcrearCarpeta(nombreCarpeta);

  // Buscar archivos que no fueron modificados en mas de 2 anios
  // modifiedDate < 'YYYY-MM-DD' busca archivos sin modificar desde esa fecha
  var fechaFormateada = Utilities.formatDate(fechaLimite, Session.getScriptTimeZone(), "yyyy-MM-dd");
  var consulta = "modifiedDate < '" + fechaFormateada + "' and trashed = false and mimeType != 'application/vnd.google-apps.folder'";

  var archivos = DriveApp.searchFiles(consulta);
  var contador = 0;
  var movidos = [];
  var errores = [];

  Logger.log("=== INICIO DEL PROCESO ===");
  Logger.log("Fecha limite: " + fechaFormateada);
  Logger.log("Carpeta destino: " + carpetaDestino.getUrl());
  Logger.log("");

  while (archivos.hasNext()) {
    var archivo = archivos.next();

    try {
      var nombre = archivo.getName();
      var ultimaModificacion = archivo.getLastUpdated();
      var carpetasActuales = archivo.getParents();

      // No mover si ya esta en GUARDA TEMPORAL
      var yaEnDestino = false;
      while (carpetasActuales.hasNext()) {
        if (carpetasActuales.next().getId() === carpetaDestino.getId()) {
          yaEnDestino = true;
          break;
        }
      }

      if (yaEnDestino) continue;

      // Mover: agregar a destino y quitar de carpetas anteriores
      carpetasActuales = archivo.getParents();
      while (carpetasActuales.hasNext()) {
        carpetasActuales.next().removeFile(archivo);
      }
      carpetaDestino.addFile(archivo);

      contador++;
      movidos.push(nombre + " (mod: " + ultimaModificacion.toLocaleDateString() + ")");
      Logger.log("Movido: " + nombre);

    } catch (e) {
      errores.push(archivo.getName() + " - Error: " + e.message);
      Logger.log("ERROR con: " + archivo.getName() + " - " + e.message);
    }
  }

  // Resumen
  Logger.log("");
  Logger.log("=== RESUMEN ===");
  Logger.log("Archivos movidos: " + contador);
  Logger.log("Errores: " + errores.length);

  // Enviar resumen por email
  enviarResumen(contador, movidos, errores, carpetaDestino.getUrl());
}

function obtenerOcrearCarpeta(nombre) {
  var carpetas = DriveApp.getFoldersByName(nombre);

  if (carpetas.hasNext()) {
    return carpetas.next();
  }

  Logger.log("Creando carpeta: " + nombre);
  return DriveApp.createFolder(nombre);
}

function enviarResumen(total, movidos, errores, urlCarpeta) {
  var email = Session.getActiveUser().getEmail();
  var asunto = "Google Drive - Guarda Temporal: " + total + " archivos movidos";

  var cuerpo = "Se movieron " + total + " archivos a GUARDA TEMPORAL.\n\n";
  cuerpo += "Carpeta: " + urlCarpeta + "\n\n";

  if (movidos.length > 0) {
    cuerpo += "--- ARCHIVOS MOVIDOS ---\n";
    for (var i = 0; i < movidos.length; i++) {
      cuerpo += "  - " + movidos[i] + "\n";
    }
  }

  if (errores.length > 0) {
    cuerpo += "\n--- ERRORES ---\n";
    for (var j = 0; j < errores.length; j++) {
      cuerpo += "  - " + errores[j] + "\n";
    }
  }

  cuerpo += "\nEste email fue generado automaticamente por el script de Guarda Temporal.";

  try {
    MailApp.sendEmail(email, asunto, cuerpo);
    Logger.log("Resumen enviado a: " + email);
  } catch (e) {
    Logger.log("No se pudo enviar el email: " + e.message);
  }
}

/**
 * (OPCIONAL) Programar ejecucion automatica mensual.
 * Ejecutar esta funcion UNA SOLA VEZ para activar el trigger.
 */
function programarEjecucionMensual() {
  // Eliminar triggers anteriores de este script
  var triggers = ScriptApp.getProjectTriggers();
  for (var i = 0; i < triggers.length; i++) {
    ScriptApp.deleteTrigger(triggers[i]);
  }

  // Crear trigger mensual (dia 1 de cada mes, entre 1am y 2am)
  ScriptApp.newTrigger("moverArchivosViejos")
    .timeBased()
    .onMonthDay(1)
    .atHour(1)
    .create();

  Logger.log("Trigger mensual creado: se ejecutara el dia 1 de cada mes.");
}
