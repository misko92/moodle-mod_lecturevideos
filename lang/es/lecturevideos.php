<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * lecturevideos.php (Spanish)
 *
 * @package   mod_lecturevideos
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['actions'] = 'Acciones';
$string['addvideo'] = 'Agregar video';
$string['allowseek'] = 'Permitir adelantar a partes aún no vistas';
$string['back'] = 'Volver';
$string['captionfiles'] = 'Subtítulos';
$string['captionfiles_help'] = 'Archivos de subtítulos WebVTT (.vtt), uno por idioma. Nombre cada archivo según su idioma: **en.vtt** para inglés, **es.vtt** para español (o es_mx.vtt).

Los estudiantes cuyo idioma de Moodle es el español ven los subtítulos en español desde el principio. Cualquiera puede activar, desactivar o cambiar el idioma de los subtítulos con el botón CC del reproductor.';
$string['complete'] = 'Completado';
$string['completiondetail:each'] = 'Ver al menos el {$a}% de cada video';
$string['completiondetail:percent'] = 'Alcanzar al menos un {$a}% de progreso promedio en los videos';
$string['completionmodeaverage'] = 'Promedio de todos los videos de al menos';
$string['completionmodeeach'] = 'Cada video visto al menos';
$string['completionpercent'] = 'Requerir ver';
$string['completionpercent_help'] = 'Solo cuentan las partes de un video que realmente se reprodujeron. Adelantar o volver a ver la misma parte no suma progreso.

* **Cada video visto al menos X%**: cada video debe llegar al X% por sí solo.
* **Promedio de todos los videos de al menos X%**: el promedio de todos los videos debe llegar al X%, así que un video omitido se puede compensar con otros.

Por redondeo se suele perder alrededor del 1% al final de un video, así que no se recomienda el 100%.';
$string['confirmdeletevideo'] = '¿Eliminar el video "{$a}" y todo el progreso guardado para él?';
$string['duration'] = 'Duración';
$string['editvideo'] = 'Editar video';
$string['enabled'] = 'Activado';
$string['errorcaptionname'] = '"{$a}": nombre los archivos de subtítulos según su idioma, p. ej. en.vtt o es.vtt.';
$string['errorpercent'] = 'Introduzca un porcentaje del 1 al 100.';
$string['invalidtrackingdata'] = 'Datos de seguimiento del video no válidos.';
$string['lastwatched'] = 'Visto por última vez';
$string['lecturevideos:addinstance'] = 'Agregar una nueva actividad Videos de clase';
$string['lecturevideos:managevideos'] = 'Gestionar los videos de una actividad Videos de clase';
$string['lecturevideos:resetprogress'] = 'Reiniciar el progreso de Videos de clase';
$string['lecturevideos:view'] = 'Ver Videos de clase';
$string['lecturevideos:viewreport'] = 'Ver el informe de progreso de Videos de clase';
$string['lecturevideosname'] = 'Nombre';
$string['managevideos'] = 'Gestionar videos';
$string['minpercent'] = 'Cuenta como visto al';
$string['minpercent_help'] = 'Porcentaje de este video que un estudiante debe ver antes de que aparezca una marca en la lista (y, si los videos deben verse en orden, antes de que se desbloquee el siguiente video).';
$string['modulename'] = 'Videos de clase';
$string['modulename_help'] = 'Una lista de reproducción de videos subidos, con la lista junto al reproductor. Moodle registra qué partes de cada video vio realmente cada estudiante, continúa donde lo dejó y puede completar la actividad cuando se ha visto lo suficiente. Los profesores tienen un informe por estudiante y por video.';
$string['modulenameplural'] = 'Videos de clase';
$string['noactivities'] = 'No hay actividades Videos de clase en este curso.';
$string['nostudents'] = 'Todavía no hay estudiantes inscritos.';
$string['notcomplete'] = 'Todavía no';
$string['notstarted'] = 'Sin empezar';
$string['novideos'] = 'Todavía no se han agregado videos.';
$string['overallprogress'] = 'Progreso general';
$string['partswatched'] = 'Partes vistas';
$string['playlistsettings'] = 'Reproducción';
$string['pluginadministration'] = 'Administración de Videos de clase';
$string['pluginname'] = 'Videos de clase';
$string['posterfile'] = 'Imagen de vista previa';
$string['posterfile_help'] = 'Imagen opcional que se muestra antes de reproducir el video y como miniatura en la lista (JPG, PNG o WebP; 16:9 se ve mejor).';
$string['privacy:metadata:progress'] = 'Progreso de visualización por video.';
$string['privacy:metadata:progress:lastposition'] = 'Última posición de reproducción.';
$string['privacy:metadata:progress:percent'] = 'Porcentaje visto.';
$string['privacy:metadata:progress:segments'] = 'Partes del video realmente vistas.';
$string['privacy:metadata:progress:userid'] = 'El estudiante.';
$string['privacy:metadata:progress:watchtime'] = 'Tiempo total de reproducción del video.';
$string['privacy:metadata:sessions'] = 'Registro técnico de cada sesión de visualización, usado para comprobar que los informes de progreso llegan en orden y a un ritmo creíble.';
$string['privacy:metadata:sessions:lastheartbeat'] = 'Hora del último informe de progreso.';
$string['privacy:metadata:sessions:timecreated'] = 'Hora de inicio de la sesión de visualización.';
$string['privacy:metadata:sessions:userid'] = 'El estudiante.';
$string['reportsummary'] = '{$a->complete} de {$a->total} estudiantes completado · {$a->videos} videos';
$string['reporttitle'] = 'Informe de progreso de videos';
$string['resetprogress'] = 'Eliminar todo el progreso de los videos';
$string['resumeautomatic'] = 'Continuar donde lo dejó el estudiante';
$string['resumefromstart'] = 'Empezar siempre desde el principio';
$string['resumeplayback'] = 'Reanudar la reproducción';
$string['sequential'] = 'Los videos deben verse en orden';
$string['statuscompleted'] = 'Visto';
$string['statuslocked'] = 'Bloqueado';
$string['student'] = 'Estudiante';
$string['videodeleted'] = 'Video eliminado.';
$string['videodescription'] = 'Descripción';
$string['videofile'] = 'Archivo de video';
$string['videofile_help'] = 'MP4 (video H.264, audio AAC) se reproduce en todos los dispositivos. Las grabaciones de pantalla pequeñas de diapositivas funcionan bien a 720p.';
$string['videolocked'] = 'Este video está bloqueado hasta que se haya visto el video anterior.';
$string['videoprogress'] = 'Este video';
$string['videosaved'] = 'Video guardado.';
$string['videoswatched'] = '{$a->done} de {$a->total} videos vistos';
$string['videotitle'] = 'Título del video';
$string['viewreport'] = 'Informe de progreso';
$string['watchedparts'] = 'Verde = partes que ya ha visto';
$string['watchtime'] = 'Tiempo dedicado';
