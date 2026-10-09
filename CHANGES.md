# Changelog — local_efscorm

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
