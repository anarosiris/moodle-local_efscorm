# Changelog — local_efscorm

## [0.2.1] - 2026-10-10

Revisión externa de la 0.2.0 aplicada (20 hallazgos; seguridad: nada explotable). Verificado en navegador en un sitio
limpio (5.3, Boost): paquete de 9 SCO con tabla de contenidos, plegada, 1366×768, 1366×600 y móvil.
- Cursos de formato «actividad única»: «Salir de la actividad» iba en bucle (curso → actividad → reproductor); ahora
  lleva a la portada del sitio, como hace el núcleo, y se respeta `preventskip=1`.
- Con la tabla de contenidos visible el contenido perdía un 21 % de ancho (se restaba dos veces); al plegarla no se
  reajustaba; en ventanas de menos de 730 px de alto sobraba una barra de desplazamiento (alto mínimo de 680 px del
  núcleo); el panel de navegación entre SCO quedaba fuera de la ventana. El iframe ocupa ahora exactamente el hueco.
- La ventana emergente de verdad (profesor, o entrada directa apagada) recibía la barra y «Salir» cargaba el curso
  dentro de la ventana: la URL de la misma pestaña lleva la marca `efscormtab=1` y la ventana real no se toca.
- `display=popup` escrito a mano en una actividad normal dejaba el layout `embedded` sin salida ni reloj: ahora es
  pantalla completa como el resto.
- La entrada directa comprueba la capacidad `mod/scorm:skipview` y las fechas de apertura y cierre, como el núcleo
  (antes el alumno aterrizaba en un aviso casi tapado por la barra); los avisos del núcleo quedan bajo la barra.
- Nombre de la actividad con `&` se veía como `&amp;` en la barra.
- Ajuste «Ocultar la tabla de contenidos» también oculta la tabla «en menú desplegable»; `100dvh` en móvil;
  salvaguarda contra la recursión de `redirect()` con avisos de depuración; registro de la actividad desde `$PAGE`.
- Accesibilidad: la barra es un `header` con el nombre en `h1`, `:focus-visible`, botón de 40 px.
- Textos: el reloj se ve en el reproductor (no «en la barra»); el ajuste principal apagado apaga también la entrada
  directa; «complemento» en español. Guía: FAQ del contenido recortado corregida; capturas nuevas.
- `maturity` STABLE. Tests: 8 PHPUnit (47 aserciones) con los casos nuevos; Behat sin depender del texto del paquete.

## [0.2.0] - 2026-10-10

Primera versión publicable (decisiones de Ana del 09-10 sobre las 8 preguntas del SPEC):
- Nuevo ajuste «Actividades en ventana nueva, en la misma pestaña» (activado): con la entrada directa, las
  actividades configuradas en ventana nueva se abren en el reproductor del núcleo en modo «popup» pero en la pestaña
  actual, a pantalla completa, con la barra superior y «Salir de la actividad» (vuelve a la sección del curso).
  Los bloqueadores de ventanas dejan de estorbar y el tiempo se mide como en cualquier página.
- Barra superior con colores neutros (gris oscuro y blanco), válida para cualquier tema.
- Nota para el administrador en la página de ajustes: con efreport o el Reloj gratuito el tiempo de cada SCORM
  queda medido (nada para el alumno).
- Nombre, activación solo de sitio, tabla de contenidos y página del profesor: se quedan como en 0.1.0.
- Tests PHPUnit (entrada directa, misma pestaña, layout, ajustes) y Behat; guía corta en docs/manual; CI.


## [0.1.0] - 2026-10-03

Prototipo (ALPHA), sin publicar: entrada directa (hook before_http_headers + scorm_simple_play con «omitir
estructura = siempre»), reproductor a pantalla completa en la misma página (layout popup + CSS/JS propios, barra
superior con «Salir de la actividad» usando el enlace del núcleo), ajustes de sitio (activar, entrar directamente,
ocultar tabla de contenidos). Preguntas abiertas en docs/SPEC.md §9.
