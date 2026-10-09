<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Spanish strings.
 *
 * @package    local_efscorm
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @author     Ana Rosiris Castro Mendez <info@elearningfacil.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['exit'] = 'Salir de la actividad';
$string['pluginname'] = 'Reproductor SCORM directo';
$string['privacy:metadata'] = 'El plugin Reproductor SCORM directo solo cambia cómo se abren y se muestran las actividades SCORM. No guarda datos personales.';
$string['setting_directentry'] = 'Entrar directamente';
$string['setting_directentry_desc'] = 'El estudiante entra en el contenido al pulsar la actividad, sin la página del botón «Entrar». Los profesores y quienes pueden ver los informes conservan la página normal, como hace Moodle con «Omitir la página de estructura del contenido: Siempre».';
$string['setting_enabled'] = 'Reproductor a pantalla completa';
$string['setting_enabled_desc'] = 'Muestra el contenido SCORM en una página a pantalla completa (sin barra de navegación ni bloques), con su propia barra superior y el botón de salir. Es la misma página de la actividad, así que los plugins que miden el tiempo la siguen midiendo.';
$string['setting_hidetoc'] = 'Ocultar la tabla de contenidos';
$string['setting_hidetoc_desc'] = 'En el reproductor a pantalla completa, ocultar la tabla de contenidos del SCORM aunque la actividad la muestre.';
$string['setting_samewindow'] = 'Actividades en ventana nueva, en la misma pestaña';
$string['setting_samewindow_desc'] = 'Las actividades configuradas para abrirse en ventana nueva se muestran en el reproductor a pantalla completa de la pestaña actual (con la entrada directa). Los bloqueadores de ventanas emergentes dejan de estorbar y los plugins que miden el tiempo miden el contenido como cualquier otra página. Desmarca la casilla para conservar la ventana nueva en esas actividades.';
$string['setting_timetracking'] = 'Tiempo dedicado a cada SCORM';
$string['setting_timetracking_desc'] = 'Este plugin solo cambia cómo se abren las actividades SCORM. Con el plugin de medición de tiempo del Pack Formación Bonificada Pro (local_efreport) o con el Reloj gratuito (local_eftimeclock) de eLearning Fácil, el tiempo que cada estudiante pasa dentro de cada SCORM queda medido y se muestra en la barra superior del reproductor.';
