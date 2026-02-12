function moverArchivosViejos() {
  var nombreCarpeta = "GUARDA TEMPORAL";
  var diasLimite = 730;
  var fechaLimite = new Date();
  fechaLimite.setDate(fechaLimite.getDate() - diasLimite);
  var carpetaDestino = obtenerOcrearCarpeta(nombreCarpeta);
  var fechaFormateada = Utilities.formatDate(fechaLimite, Session.getScriptTimeZone(), "yyyy-MM-dd");
  var consulta = "modifiedDate < '" + fechaFormateada + "' and trashed = false and mimeType != 'application/vnd.google-apps.folder'";
  var archivos = DriveApp.searchFiles(consulta);
  var contador = 0;
  var movidos = [];
  var errores = [];
  Logger.log("Fecha limite: " + fechaFormateada);
  while (archivos.hasNext()) {
    var archivo = archivos.next();
    try {
      var nombre = archivo.getName();
      var carpetasActuales = archivo.getParents();
      var yaEnDestino = false;
      while (carpetasActuales.hasNext()) {
        if (carpetasActuales.next().getId() === carpetaDestino.getId()) {
          yaEnDestino = true;
          break;
        }
      }
      if (yaEnDestino) { continue; }
      archivo.moveTo(carpetaDestino);
      contador++;
      movidos.push(nombre);
      Logger.log("Movido: " + nombre);
    } catch (e) {
      errores.push(archivo.getName() + " - " + e.message);
      Logger.log("ERROR: " + archivo.getName() + " - " + e.message);
    }
  }
  Logger.log("Total movidos: " + contador);
  Logger.log("Total errores: " + errores.length);
  enviarResumen(contador, movidos, errores, carpetaDestino.getUrl());
}

function obtenerOcrearCarpeta(nombre) {
  var carpetas = DriveApp.getFoldersByName(nombre);
  if (carpetas.hasNext()) {
    return carpetas.next();
  }
  return DriveApp.createFolder(nombre);
}

function enviarResumen(total, movidos, errores, urlCarpeta) {
  var email = Session.getActiveUser().getEmail();
  var asunto = "Drive - Guarda Temporal: " + total + " archivos movidos";
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
  try {
    MailApp.sendEmail(email, asunto, cuerpo);
    Logger.log("Resumen enviado a: " + email);
  } catch (e) {
    Logger.log("No se pudo enviar email: " + e.message);
  }
}
