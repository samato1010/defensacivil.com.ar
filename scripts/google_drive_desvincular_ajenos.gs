/**
 * Script para desvincular de Mi unidad archivos que NO son de mi propiedad
 * y no fueron modificados en mas de 2 anios.
 *
 * Genera un archivo TXT llamado "links_archivos_desvinculados.txt"
 * con los nombres y URLs de acceso de cada archivo desvinculado.
 *
 * Instrucciones:
 *   1. Abrir https://script.google.com
 *   2. Crear un nuevo proyecto
 *   3. Pegar este codigo
 *   4. Ejecutar la funcion "desvincularArchivosAjenos"
 *   5. Autorizar los permisos cuando se solicite
 */

function desvincularArchivosAjenos() {
  var diasLimite = 730;
  var fechaLimite = new Date();
  fechaLimite.setDate(fechaLimite.getDate() - diasLimite);
  var fechaFormateada = Utilities.formatDate(fechaLimite, Session.getScriptTimeZone(), "yyyy-MM-dd");
  var miEmail = Session.getActiveUser().getEmail();

  var consulta = "modifiedDate < '" + fechaFormateada + "' and trashed = false and mimeType != 'application/vnd.google-apps.folder'";
  var archivos = DriveApp.searchFiles(consulta);
  var contador = 0;
  var lineasTxt = [];
  var errores = [];

  Logger.log("=== DESVINCULAR ARCHIVOS AJENOS ===");
  Logger.log("Fecha limite: " + fechaFormateada);
  Logger.log("Mi email: " + miEmail);
  Logger.log("");

  while (archivos.hasNext()) {
    var archivo = archivos.next();
    try {
      var propietario = archivo.getOwner();
      // Si no tiene propietario o si soy yo el propietario, saltar
      if (!propietario || propietario.getEmail() === miEmail) {
        continue;
      }
      var nombre = archivo.getName();
      var url = archivo.getUrl();
      var emailPropietario = propietario.getEmail();
      var ultimaMod = archivo.getLastUpdated();
      var fechaMod = Utilities.formatDate(ultimaMod, Session.getScriptTimeZone(), "dd/MM/yyyy");

      // Obtener ruta completa del archivo
      var ruta = obtenerRuta(archivo);

      // Guardar info para el TXT antes de desvincular
      lineasTxt.push("Nombre: " + nombre);
      lineasTxt.push("URL: " + url);
      lineasTxt.push("Propietario: " + emailPropietario);
      lineasTxt.push("Ultima modificacion: " + fechaMod);
      lineasTxt.push("Ubicacion original: " + ruta);
      lineasTxt.push("---");

      // Desvincular: quitar de todas mis carpetas
      var carpetas = archivo.getParents();
      while (carpetas.hasNext()) {
        carpetas.next().removeFile(archivo);
      }

      contador++;
      Logger.log("Desvinculado: " + nombre + " (de " + emailPropietario + ")");

    } catch (e) {
      errores.push(archivo.getName() + " - " + e.message);
      Logger.log("ERROR: " + archivo.getName() + " - " + e.message);
    }
  }

  Logger.log("");
  Logger.log("Total desvinculados: " + contador);
  Logger.log("Total errores: " + errores.length);

  // Crear archivo TXT con los links
  if (lineasTxt.length > 0) {
    var contenidoTxt = "ARCHIVOS DESVINCULADOS DE MI UNIDAD\n";
    contenidoTxt += "Fecha: " + Utilities.formatDate(new Date(), Session.getScriptTimeZone(), "dd/MM/yyyy HH:mm") + "\n";
    contenidoTxt += "Total: " + contador + " archivos\n";
    contenidoTxt += "========================================\n\n";
    contenidoTxt += lineasTxt.join("\n");

    if (errores.length > 0) {
      contenidoTxt += "\n\n========================================\n";
      contenidoTxt += "ERRORES (" + errores.length + "):\n";
      for (var j = 0; j < errores.length; j++) {
        contenidoTxt += "  - " + errores[j] + "\n";
      }
    }

    var archivoTxt = DriveApp.createFile("links_archivos_desvinculados.txt", contenidoTxt, MimeType.PLAIN_TEXT);
    Logger.log("Archivo TXT creado: " + archivoTxt.getUrl());

    // Enviar resumen por email
    enviarResumenDesvinculados(contador, errores.length, archivoTxt.getUrl());
  } else {
    Logger.log("No se encontraron archivos ajenos para desvincular.");
  }
}

function obtenerRuta(archivo) {
  try {
    var carpetas = archivo.getParents();
    if (!carpetas.hasNext()) {
      return "Mi unidad (raiz)";
    }
    var partes = [];
    var carpeta = carpetas.next();
    partes.unshift(carpeta.getName());
    var padres = carpeta.getParents();
    while (padres.hasNext()) {
      carpeta = padres.next();
      partes.unshift(carpeta.getName());
      padres = carpeta.getParents();
    }
    return partes.join(" / ");
  } catch (e) {
    return "(ruta no disponible)";
  }
}

function enviarResumenDesvinculados(total, totalErrores, urlTxt) {
  var email = Session.getActiveUser().getEmail();
  var asunto = "Drive - Desvinculados: " + total + " archivos ajenos removidos";
  var cuerpo = "Se desvincularon " + total + " archivos ajenos de tu unidad.\n\n";
  cuerpo += "Archivo con links de acceso: " + urlTxt + "\n\n";
  cuerpo += "Los archivos siguen existiendo en el Drive de sus propietarios.\n";
  cuerpo += "Podes acceder a ellos usando los links del archivo TXT.\n";
  if (totalErrores > 0) {
    cuerpo += "\nHubo " + totalErrores + " errores (ver detalles en el TXT).\n";
  }
  try {
    MailApp.sendEmail(email, asunto, cuerpo);
    Logger.log("Resumen enviado a: " + email);
  } catch (e) {
    Logger.log("No se pudo enviar email: " + e.message);
  }
}
