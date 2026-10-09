# SPEC · local_efscorm — «Reproductor SCORM directo» (0.2, 10-10-2026)

Fuente de la verdad del plugin. Lo marcado como **PROPUESTA** no está decidido: Ana lo confirma o lo cambia.
Las preguntas del §9 quedaron DECIDIDAS por Ana el 09-10-2026 (respuestas junto a cada una); 0.2.0 las aplica.

## 1. Qué pidió Ana (02-10-2026)

- Al pulsar la actividad SCORM el estudiante **entra directamente**, sin la página del botón «Entrar».
- El contenido se ve **completo, a pantalla completa**, pero **no en una capa sobre el curso**: tiene que ser una
  **página de la actividad**, para que efreport (y el Reloj gratuito) la midan.
- Un **botón de cerrar** equivalente al «Salir de la actividad» que Moodle muestra hoy.
- **Sin depender del tema**: debe servir con cualquiera.
- **Adaptable**; si el SCORM no lo es o es grande, **barras de desplazamiento** para verlo entero.
- El **reloj** de efreport / eftimeclock sale **arriba**.
- **Gratuito**, para el Marketplace de Moodle: escaparate de la suite, como el Reloj gratuito.

## 2. Qué hace ya Moodle y qué falta (comprobado en 5.2)

| Necesidad | Núcleo (mod_scorm) | Qué falta |
|---|---|---|
| Entrar directo | Ajuste por actividad «Omitir la página de estructura: Siempre» | Que sea general (sitio) sin tocar cada actividad; los profesores siguen viendo la página normal (el núcleo ya lo hace así) |
| Pantalla completa | Solo en «ventana nueva» (layout `embedded`) | En la misma pestaña: la ventana nueva la bloquean los navegadores y rompe la medición (COOP); además efreport y eftimeclock **no pintan el reloj en el layout `embedded`** |
| Sin barra del tema | — (layout `incourse`: barra, cajones, cabecera, pie) | Un layout mínimo con el contenido a todo el ancho y alto |
| Salir | Botón «Salir de la actividad» del núcleo (enlace a la sección del curso; el SCORM se cierra y se guarda al descargar la página) | Un botón visible en la barra superior que use **el mismo enlace** |
| Barras de desplazamiento | El contenido se recorta al tamaño de la actividad | Contenedor con desplazamiento |

## 3. Diseño (PROPUESTA, prototipada en 0.1.0)

Plugin **local**, sin tocar `mod_scorm`, solo con hooks de salida (Moodle 5.x):

1. **`before_http_headers`** (se ejecuta dentro de `header()`, antes de elegir el layout):
   - En `mod/scorm/view.php`, si «Entrar directamente» está activo: se llama a `scorm_simple_play()` del núcleo con una
     copia de la actividad con «omitir estructura = siempre». El núcleo decide: **redirige al reproductor** al estudiante
     y **deja la página normal** a quien puede ver informes (profesores, gestores). Las actividades configuradas para
     abrirse en **ventana nueva** se dejan como están.
   - En `mod/scorm/player.php` (ventana actual): `set_pagelayout('popup')`, el layout mínimo que todo tema define
     (el núcleo lo usa en sus ventanas emergentes): sin barra, cajones, cabecera de actividad ni pie. **No es `embedded`**,
     así que efreport y eftimeclock pintan su reloj y miden la página.
2. **`before_standard_head_html_generation`**: CSS propio, solo en esa página: `#scormpage` fijo bajo una barra de 48 px,
   con `overflow: auto` (barras de desplazamiento si el contenido es más grande); el `iframe` del SCORM ocupa el resto
   de la ventana (variables CSS actualizadas por JS, con `!important` para que el redimensionado del núcleo no las pise).
3. **`before_footer_html_generation`**: barra superior (nombre de la actividad + botón **«Salir de la actividad»** con el
   **mismo enlace** del botón del núcleo, que se oculta). Es una navegación real: el `datamodel` del SCORM hace su
   `LMSFinish`/`Terminate` al descargar la página, como siempre. Nunca `window.close()`.

Lo que **no** hace: no abre ventanas, no cambia intentos, informes, calificaciones ni la configuración de las actividades.

Ajustes de sitio (0.1.0): «Reproductor a pantalla completa» (sí), «Entrar directamente» (sí), «Ocultar la tabla de
contenidos» (no).

## 4. Seguridad y Marketplace

Sin páginas propias, sin AJAX, sin tablas: el plugin solo lee `scorm` por el `cm` de la página (que ya pasó
`require_login` y la comprobación de acceso del núcleo). Proveedor de privacidad nulo. Código y comentarios en inglés,
`lang/en` base y `lang/es`. Sin recursos externos. GPL v3. CI con moodle-plugin-ci en 5.1 y 5.2 (y 5.3 cuando salga).

## 5. Compatibilidad

Moodle 5.1, 5.2 (y 5.3). Temas: cualquiera que defina el layout `popup` (todos los basados en Boost; los que no lo
definan caen al layout base, que sigue siendo válido). Con efreport ≥ 2.x y eftimeclock: el reloj sale arriba a la
izquierda, como en cualquier actividad. Sin ninguno de los dos: funciona igual, solo que no hay reloj.

## 6. Nombre

**PROPUESTA: `local_efscorm`**, nombre visible «Reproductor SCORM directo» / «Direct SCORM player».
Aviso: SCORM® es marca registrada de ADL (EE. UU.). En el directorio de plugins de Moodle hay muchos plugins con
«scorm» en el nombre técnico (describe el formato, como en el propio `mod_scorm`), pero la regla de la casa es no poner
marcas en los nombres. Alternativas: `local_efplayer` («Reproductor directo»), `local_efdirect`. Ana decide (§9).

## 7. Pruebas

- PHPUnit: entrada directa (estudiante → redirección al reproductor; profesor → página normal; actividad en ventana
  nueva → sin cambios; ajuste apagado → sin cambios), layout `popup` en el reproductor, CSS/JS solo en esa página.
- Behat (sin JS): el estudiante llega al reproductor al abrir la actividad; el profesor ve el botón «Entrar».
- Manual con Playwright (tools/e2e): escritorio y móvil, un SCO y varios SCO, salir vuelve al curso, reloj visible.

## 8. Estado

- 10-10-2026: **0.2.0** con las 8 decisiones aplicadas, tests PHPUnit y Behat, guía corta y CI; primera versión
  publicable (repo remoto privado, zip en entregado-por-claude/efscorm). Entra en el curso demo comercial.
- 03-10-2026: prototipo 0.1.0 instalado en el 5.2 local; probado como alumno en el curso demo (cm 119, un SCO):
  entra directo, barra + salir, contenido a toda la ventana en 1366×768 y 390×800, el reloj de efreport sale y mide,
  salir vuelve a la sección del curso. Sin repo remoto hasta que Ana confirme el nombre. Sin pruebas automáticas aún.

## 9. Preguntas para Ana — DECIDIDAS (09-10-2026)

1. **Nombre**: se mantiene `local_efscorm` («Reproductor SCORM directo»): «scorm» describe el formato, como `mod_scorm`.
2. **Dónde se activa**: solo en el sitio. El nivel por curso, el día que un cliente lo pida.
3. **Tabla de contenidos**: como la tenga la actividad; el ajuste de sitio «ocultar» sigue, apagado por defecto.
4. **Actividades en «ventana nueva»**: se fuerzan a la misma pestaña a pantalla completa (ajuste «Actividades en
   ventana nueva, en la misma pestaña», activado por defecto; se puede desactivar). Técnica (0.2.0): en `view.php`
   el hook redirige al reproductor del núcleo con `display=popup` (la misma URL que el núcleo abriría en la ventana
   nueva, construida como `scorm_simple_play()`), y en `player.php` con `display=popup` se sustituye el layout
   `embedded` por `popup` + barra propia; «Salir» vuelve a la sección del curso (en ese modo el núcleo no pinta
   botón de salida). Sin la entrada directa, la página de la actividad queda como en el núcleo.
5. **Profesores**: como el núcleo (página de entrada con los informes).
6. **Barra superior**: nombre + «Salir de la actividad»; colores neutros (gris oscuro `#1f2937`, blanco).
7. **Sin efreport ni eftimeclock**: funciona igual; una nota en la página de ajustes del administrador (nunca al alumno).
8. **Guía**: sí, corta, en `docs/manual/` (la exige `build_zips.py` y la lista el curso Área de cliente).
