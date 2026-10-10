# SPEC · local_efscorm — «Reproductor SCORM directo» (0.2.1, 10-10-2026)

Fuente de la verdad del plugin. Las preguntas del §9 quedaron DECIDIDAS por Ana el 09-10-2026 (respuestas junto a
cada una); 0.2.0 las aplica y 0.2.1 incorpora la revisión externa (§10).

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

## 3. Diseño (definitivo desde 0.2.1)

Plugin **local**, sin tocar `mod_scorm`, solo con hooks de salida (Moodle 5.x):

1. **`before_http_headers`** (se ejecuta dentro de `header()`, antes de elegir el layout):
   - En `mod/scorm/view.php`, si «Entrar directamente» está activo y el usuario pasa las mismas guardas que el núcleo
     (capacidad `mod/scorm:skipview`, sin `preventskip=1`, actividad dentro de sus fechas): se llama a
     `scorm_simple_play()` del núcleo con una copia de la actividad con «omitir estructura = siempre». El núcleo
     decide: **redirige al reproductor** al estudiante y **deja la página normal** a quien puede ver informes
     (profesores, gestores). Las actividades configuradas para abrirse en **ventana nueva** van, con el ajuste
     «misma pestaña», al reproductor del núcleo en modo `display=popup` en la pestaña actual, con la marca
     `efscormtab=1` en la URL (§9.4); sin el ajuste, se dejan como están.
   - En `mod/scorm/player.php`, siempre que sea el reproductor de la pestaña actual (también con `display=popup`
     escrito a mano): `set_pagelayout('popup')`, el layout mínimo que todo tema define (el núcleo lo usa en sus
     ventanas emergentes): sin barra, cajones, cabecera de actividad ni pie. **No es `embedded`**, así que efreport
     y eftimeclock pintan su reloj y miden la página. La **ventana emergente de verdad** que abre el núcleo
     (`display=popup` sin la marca, en actividades «en ventana nueva») no se toca: la cierra el alumno.
2. **`before_standard_head_html_generation`**: CSS propio, solo en esa página: `#scormpage` fijo bajo una barra de 48 px,
   con `overflow: auto` (barras de desplazamiento si el contenido es más grande); el `iframe` del SCORM ocupa el 100 %
   de `#scorm_content` (el núcleo ya reserva el hueco de la tabla de contenidos) y el alto de la ventana menos la barra,
   lo que tenga encima (tabla de contenidos plegada) y debajo (panel de navegación entre SCO), medidos por JS al
   cargar, al redimensionar, al plegar la tabla de contenidos y con `ResizeObserver`; `!important` para que el alto
   mínimo de 680 px del núcleo no mande. Los avisos que el núcleo pinta fuera de `#scormpage` (aún no abierta, oculta,
   sin intentos) quedan bajo la barra gracias a un `padding-top` en la región principal.
3. **`before_footer_html_generation`**: barra superior (`<header>` con el nombre de la actividad en `<h1>` + botón
   **«Salir de la actividad»** con el **mismo enlace** del botón del núcleo, que se oculta; sin botón del núcleo, la
   sección del curso). En cursos de formato «actividad única» el enlace es la portada del sitio, como hace el núcleo
   para evitar el bucle curso → actividad → reproductor. Es una navegación real: el `datamodel` del SCORM hace su
   `LMSFinish`/`Terminate` al descargar la página, como siempre. Nunca `window.close()`.

Lo que **no** hace: no abre ventanas, no cambia intentos, informes, calificaciones ni la configuración de las actividades.

Ajustes de sitio (0.1.0): «Reproductor a pantalla completa» (sí), «Entrar directamente» (sí), «Ocultar la tabla de
contenidos» (no).

## 4. Seguridad y Marketplace

Sin páginas propias, sin AJAX, sin tablas: el plugin solo lee `scorm` por el `cm` de la página (que ya pasó
`require_login` y la comprobación de acceso del núcleo). Proveedor de privacidad nulo. Código y comentarios en inglés,
`lang/en` base y `lang/es`. Sin recursos externos. GPL v3. CI con moodle-plugin-ci en 5.1, 5.2 y 5.3 (bloqueante).
Revisión externa de seguridad de la 0.2.0 (10-10-2026): nada explotable; hallazgos aplicados en 0.2.1 (§10).

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

- PHPUnit (8 tests): entrada directa (estudiante → reproductor; profesores con y sin edición → página normal;
  ajustes apagados; guardas del núcleo: `preventskip`, `skipview` prohibida, invitado, fechas, paquete sin SCO
  lanzable), misma pestaña (URL con marca, SCO, organización, `newattempt`), layout `popup` según el caso (ventana
  emergente real intacta, `display=popup` a mano a pantalla completa), CSS/JS solo en esa página, nombre con `&`
  sin doble escape, salida en «actividad única».
- Behat (sin JS): el estudiante llega al reproductor al abrir la actividad; el profesor ve el botón «Entrar»; misma
  pestaña con y sin el ajuste; entrada directa apagada.
- Con navegador (Playwright, sitio limpio: 5.3 con Boost sin SCSS): paquete de 9 SCO con tabla de contenidos a 1280,
  plegada, 1366×768, 1366×600 y 390 px (el iframe llena el hueco, sin barras de desplazamiento), nombre con `&`,
  actividad aún no abierta (página de la actividad con la fecha), ventana nueva en la misma pestaña, curso de
  actividad única (salir no vuelve al reproductor), `display=popup` a mano, ventana emergente real del profesor sin
  barra, reloj de efreport presente. 22 comprobaciones el 10-10-2026.

## 8. Estado

- 10-10-2026: **0.2.1** con los 20 hallazgos de la revisión externa aplicados (§10) y verificados en navegador en un
  sitio limpio. `maturity` STABLE, como el resto de la suite.
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

## 10. Revisión externa de la 0.2.0 (10-10-2026) → 0.2.1

Informe en `~/moodle52/envio-a-claude/revision-efscorm-0.2.0.md` (20 hallazgos: 2 altos, 6 medios, 12 menores;
seguridad: nada explotable). Todos aplicados; las decisiones técnicas que tomé, para no reabrirlas:

- **Marca de la misma pestaña** (hallazgo 3): parámetro `efscormtab=1` en la URL que construye el plugin (sin
  estado en sesión; `player.php` lo ignora). Con `display=popup` y sin la marca es la ventana emergente real y se deja
  tal cual. Esa misma regla cubre `display=popup` escrito a mano en una actividad normal (hallazgo 8): pantalla completa
  con salida y reloj, en vez del layout `embedded` sin salida.
- **Actividad única** (hallazgo 1): salir = portada del sitio, lo mismo que hace el núcleo con «omitir estructura =
  siempre» (`player.php`), y `preventskip=1` respetado. Alternativa descartada: `view.php?preventskip=1`.
- **Guardas del núcleo** (hallazgos 5 y 7): la entrada directa comprueba `mod/scorm:skipview` y las fechas de
  apertura/cierre (sin redirigir, el alumno ve «Abre: fecha» en la página de la actividad, sin botón «Entrar»).
- **Ancho y alto** (hallazgos 2, 4, 9, 12): el iframe ocupa el 100 % de `#scorm_content`; alto = ventana (`100dvh`,
  con `100vh` de respaldo) − 48 px − lo que hay encima − el panel de navegación entre SCO; `#scorm_layout` sin alto
  mínimo ni margen inferior; reajuste al plegar la tabla de contenidos (clic + `ResizeObserver`).
- **Accesibilidad** (17): `<header role="banner">`, nombre en `<h1>` con `title`, `:focus-visible`, botón de 40 px.
- **Textos** (14, 19, 20): el reloj «se ve en el reproductor» (es el widget flotante, no está en la barra); el ajuste
  «Reproductor a pantalla completa» apagado apaga todo; «complemento» en `lang/es`. La guía sigue diciendo «plugin»
  (estilo de todas las guías de la casa; cambiar las 9 de golpe si Ana quiere).
- **Madurez** (13): STABLE, como el resto de la suite.
- Gotcha de verificación: el 5.2 local lleva un SCSS «SCORM inmersivo» en Boost Union que tapa el CSS del plugin;
  las pruebas de navegador se hacen en el 5.3 (Boost, sin SCSS). Un cliente con un SCSS así debe retirarlo.
