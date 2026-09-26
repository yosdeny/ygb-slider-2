=== YGB Slider 2 ===
Contributors: ygbteam
Tags: slider, image slider, slideshow, carousel, elementor, touch, swipe, drag-and-drop, duplicate, transition, shortcode, mixed-effects, scheduling
Requires at least: 7.0
Tested up to: 7.1
Stable tag: 4.2.1
Requires PHP: 8.0
Tested PHP: 8.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Slider profesional con texto sobre imagen y enlaces configurables. Versión endurecida con protección XSS y CSRF, navegación táctil con swipe, panel administrativo en acordeón con reordenamiento drag & drop, activación/duplicado de slides, efectos de transición configurables, modo mixto, efecto aleatorio y programación por fecha.

== Description ==

YGB Slider 2 es un plugin de slider profesional para WordPress que permite crear presentaciones de imágenes con texto superpuesto y enlaces clickeables.

**Características principales:**

* **Hasta 50 slides** configurables individualmente
* **Enlaces en imágenes** con opción de abrir en misma o nueva ventana
* **Texto sobre imagen** con fondo semitransparente y backdrop blur
* **Velocidad ajustable** desde 1 a 10 segundos
* **Autoplay** activable/desactivable
* **Navegación táctil con swipe** para móviles y tablets
* **Navegación con arrastre de ratón** en escritorio
* **Resistencia elástica** en los extremos del slider
* **Bloqueo inteligente de clics** tras arrastre
* **5 efectos de transición**: deslizar horizontal, fundido, zoom, rotación 3D y aleatorio
* **Modo mixto**: cada slide puede tener un efecto distinto dentro del mismo slider
* **Efecto aleatorio**: elige entre fundido/zoom/cubo en cada transición
* **Programación por fecha**: cada slide puede tener fecha de inicio y/o fin
* **Selección de slides por número**: mostrar solo los que quieras con `slides="1,3,4"` o rangos `slides="1-3,7"`
* **Orden personalizado**: `slides="1,7,4,2"` muestra los slides en ese orden exacto
* **Velocidad por grupo** en modo mixto
* **Panel administrativo en acordeón**
* **Reordenamiento por drag & drop**
* **Activar/desactivar slides individualmente**
* **Duplicar slide con un clic**
* **Sincronización en vivo del título**
* **Widget para Elementor**
* **Dashboard con ejemplos de uso**
* **Optimizado para móviles**
* **Lazy loading**
* **Intersection Observer** para pausar cuando no es visible

**Seguridad incluida:**

* Protección contra ataques XSS
* Verificación CSRF con nonces
* Validación estricta de URLs (solo http/https)
* Sanitización de todos los inputs
* Escape apropiado de todos los outputs
* Verificación de capacidades de usuario
* Validación estricta de fechas (formato YYYY-MM-DD + checkdate)

**Efectos de transición:**

5 efectos disponibles. Se configuran de forma global desde "YGB Slider 2 → Configurar" o por instancia con el atributo `transicion` del shortcode:

* **Deslizar horizontal** (por defecto)
* **Fundido**
* **Zoom**
* **Rotación 3D (cubo)**
* **Aleatorio (random)**: elige entre fundido/zoom/cubo en cada transición

Respetan `prefers-reduced-motion`: si el visitante tiene reducción de movimiento activada, el slider fuerza "fundido" automáticamente.

**Modo mixto — varios efectos en el mismo slider:**

Sintaxis: `grupos="lista:efecto|lista:efecto"`

Ejemplos:

* `grupos="1,3:zoom|2,4:cubo"`
* `grupos="1-3:zoom|4-6:cubo|7,8:fundido"`
* `grupos="1,3:zoom:6000|2,4:cubo:4000"` (velocidad por grupo)

**El efecto "deslizar" NO se puede usar en modo mixto.** Si se incluye, se sustituye automáticamente por "fundido".

**Programación por fecha:**

Cada slide puede tener una fecha de inicio y/o una fecha de fin. Los slides fuera de su ventana se saltan automáticamente en el frontend.

**Las fechas se configuran en el PANEL DE ADMIN, no en el shortcode.**

**Shortcode:**

`[ygb_slider2]`

Atributos:

* `grupos` - Modo mixto. Ej: `"1,3:zoom|2,4:cubo"`
* `slides` - Selección de slides por número. Ej: `"1,7,4,2"` o `"1-3,7,10"`
* `velocidad` - Milisegundos (ej: `4000`)
* `autoplay` - `si` o `no`
* `transicion` - `deslizar`, `fundido`, `zoom`, `cubo`, `random`
* `admin_panel` - `yes` para controles en vivo (WP_DEBUG)

Ejemplos:

* `[ygb_slider2]`
* `[ygb_slider2 slides="1,3,4"]`
* `[ygb_slider2 slides="1-3,7,10" transicion="cubo" velocidad="6000"]`
* `[ygb_slider2 grupos="1,3:zoom|2,4:cubo"]`
* `[ygb_slider2 transicion="random"]`

== Installation ==

1. Sube la carpeta del plugin a `/wp-content/plugins/`
2. Activa el plugin a través de la sección 'Plugins' en WordPress
3. Ve al menú 'YGB Slider 2' para configurar tus slides
4. Usa el shortcode `[ygb_slider2]` en cualquier página o entrada

O usa el widget "YGB Slider 2" en Elementor.

== Frequently Asked Questions ==

= ¿Cuántos slides puedo crear? =

Puedes crear hasta 50 slides por instancia del slider.

= ¿Las imágenes pueden tener enlaces? =

Sí, cada slide puede tener un enlace opcional que se aplica a toda la imagen.

= ¿Qué formatos de imagen están soportados? =

JPG, JPEG, PNG, GIF, WebP y SVG. Solo se permiten protocolos http y https.

= ¿Puedo cambiar la velocidad del slider? =

Sí, entre 1000 ms (1 s) y 10000 ms (10 s) desde el panel de configuración, por instancia con el atributo `velocidad`, o por grupo en modo mixto.

= ¿Funciona con Elementor? =

Sí, incluye un widget nativo con todos los campos.

= ¿El slider es responsive? =

Sí, se adapta automáticamente con ajustes específicos para pantallas pequeñas.

= ¿Puedo deslizar las imágenes con el dedo? =

Sí. El slider incluye navegación táctil por swipe. También funciona con arrastre de ratón en PC.

= ¿El arrastre funciona también con el ratón en PC? =

Sí. Usamos Pointer Events, que unifica ratón, táctil y stylus. Además bloqueamos el drag nativo HTML5.

= ¿Se abre el enlace si arrastro accidentalmente? =

No. Si el desplazamiento supera los 5 píxeles, se considera arrastre y se bloquea el clic.

= ¿Cómo reordeno los slides? =

Arrastrando desde el encabezado de cada slide en el panel de administración.

= ¿Cómo activo o desactivo un slide? =

Cada slide tiene un interruptor (toggle) en su encabezado. Azul = activo, gris = inactivo.

= ¿Cómo funciona el botón "Duplicar"? =

Crea una copia exacta justo después del original. Arranca desactivada.

= ¿Qué efectos de transición puedo usar? =

5 efectos: **deslizar horizontal** (por defecto), **fundido**, **zoom**, **rotación 3D (cubo)** y **aleatorio (random)**.

En modo mixto (atributo `grupos`) solo están disponibles fundido, zoom y cubo.

= ¿Cómo muestro solo algunos slides en vez de todos? =

Usa el atributo `slides`:

* `[ygb_slider2 slides="1,3,4"]` muestra solo los slides 1, 3 y 4.
* `[ygb_slider2 slides="1-3"]` muestra los slides 1, 2 y 3.
* `[ygb_slider2 slides="1,7,4,2"]` los muestra en ese orden exacto.

= ¿Qué es el efecto "aleatorio" (random)? =

Con `transicion="random"`, cada transición elige aleatoriamente entre fundido, zoom y cubo.

= ¿Dónde se configuran las fechas de programación? =

**En el panel de administración, NUNCA en el shortcode.**

Las fechas se configuran en "YGB Slider 2 → Slides", expandiendo cada slide. Al final del formulario hay una sección verde llamada "Programación por fecha (opcional)" con dos campos:

* **Fecha de inicio** (opcional)
* **Fecha de fin** (opcional)

Ahí eliges las fechas con el selector de fecha de WordPress, pulsas "Guardar Slides" y listo.

El shortcode **solo dice qué slides mostrar** (por ejemplo `[ygb_slider2 slides="3"]`). Las fechas las consulta el plugin automáticamente del panel cada vez que alguien carga la página.

= Ejemplo concreto: quiero que el slide 3 solo aparezca del 25 al 30 de noviembre =

**Paso 1 — En el panel admin:**

1. Ve a "YGB Slider 2 → Slides"
2. Haz clic en el slide 3 para expandirlo
3. Baja hasta la sección verde "Programación por fecha (opcional)"
4. En "Fecha de inicio" pones `2026-11-25`
5. En "Fecha de fin" pones `2026-11-30`
6. Pulsa "Guardar Slides"

**Paso 2 — En la página:**

Escribes este shortcode: `[ygb_slider2 slides="3"]`

**Resultado:**

* Hoy: no se ve nada (fuera de fecha).
* 25-30 noviembre: aparece solo el slide 3.
* 1 diciembre: desaparece solo.

**Tú no tocas el shortcode nunca más.** El plugin mira la fecha cada día y decide si mostrar el slide o no.

= ¿Qué pasa si NO pongo ninguna fecha en un slide? =

El slide se muestra siempre. Es el comportamiento normal. Las fechas son opcionales: solo las pones si quieres que un slide aparezca y desaparezca automáticamente.

= ¿Puedo combinar slides normales (sin fecha) con slides programados en el mismo slider? =

Sí. Es lo más habitual. En `[ygb_slider2]` (sin atributos) el plugin muestra:

* Los slides sin fecha siempre.
* Los slides con fecha solo cuando están dentro de su ventana.

Por ejemplo:

* Slide 1: sin fecha → siempre se ve.
* Slide 2: sin fecha → siempre se ve.
* Slide 3: con fecha 25-30 nov → solo se ve esos días.
* Slide 4: con fecha 20-31 dic → solo se ve esos días.

En septiembre verás los slides 1 y 2. En noviembre verás 1, 2 y 3. En diciembre verás 1, 2 y 4.

= ¿Puedo hacer que un slider muestre solo los programados? =

Sí. Usa `slides="3,4"` (los que tengan fecha) y el plugin filtrará automáticamente. Si ninguno está vigente hoy, se muestra un aviso amarillo indicando que no hay slides activos vigentes.

= ¿Qué comportamiento tienen las fechas? =

* **Sin fechas**: el slide siempre se muestra.
* **Solo fecha de inicio**: el slide aparece a partir de esa fecha (para siempre).
* **Solo fecha de fin**: el slide aparece desde siempre hasta esa fecha.
* **Ambas fechas**: el slide solo aparece dentro del rango (ambas inclusive).

Las fechas son **inclusivas**: si pones inicio `2026-11-25`, el slide se ve ese mismo día.

= ¿Qué pasa si la fecha de fin es anterior a la de inicio? =

El sistema rechaza la fecha de fin y muestra un error al guardar. Conserva la de inicio y limpia la de fin. No se guarda una configuración inválida.

= ¿Cómo sé si un slide está vigente hoy sin abrir la web? =

En el panel admin, cada slide programado muestra un badge:

* **Vigente** (verde): dentro de su ventana, se muestra en el frontend ahora mismo.
* **Programado** (amarillo): fuera de su ventana, no se muestra todavía o ya caducó.

Los slides sin fecha no muestran badge porque siempre están disponibles.

= ¿Qué zona horaria se usa? =

La configurada en WordPress en "Ajustes → Generales". Si tu sitio está en horario de Madrid, el cambio de día ocurre a las 00:00 hora de Madrid.

= ¿Qué pasa si desactivo un slide que está programado? =

El interruptor (toggle) tiene prioridad. Si está desactivado, no se muestra aunque esté vigente por fecha.

= ¿Los slides programados cuentan en el contador del dashboard? =

Sí. El dashboard muestra un contador "X / Y" con:

* X = slides programados vigentes hoy.
* Y = total de slides que tienen fechas configuradas.

= ¿Qué es el modo mixto? =

El modo mixto permite asignar un efecto de transición distinto a cada slide dentro del mismo slider. Se activa con el atributo `grupos`. Efectos permitidos: fundido, zoom y cubo.

= ¿Puedo mezclar "deslizar" dentro del modo mixto? =

No. Si se incluye, se sustituye automáticamente por "fundido". Con WP_DEBUG activo, verás un aviso amarillo sobre el slider indicando qué grupos fueron convertidos.

= ¿Puedo poner velocidad por grupo en modo mixto? =

Sí. Añade un tercer valor separado por dos puntos:

`[ygb_slider2 grupos="1,3:zoom:6000|2,4:cubo:4000"]`

= ¿Puedo mezclar `slides` y `grupos` en el mismo shortcode? =

No. Si usas `grupos`, el atributo `slides` se ignora. Son mutuamente excluyentes.

= ¿Cómo sé cuál es el número de cada slide? =

En la página "Slides" cada slide tiene un número circular azul a la izquierda del encabezado. Ese número es la posición en el orden actual.

= Si reordeno los slides, ¿cambian los números? =

Sí. Los números reflejan la posición actual. Si mueves el slide 5 a la primera posición, pasa a ser el slide 1.

= ¿Funciona con plugins de caché y optimización? =

Sí. La arquitectura externalizada está diseñada para ser compatible con WP Rocket, Autoptimize, LiteSpeed Cache, W3 Total Cache, Perfmatters y cualquier otro plugin de optimización.

= ¿Cómo verifico que la versión 4.2.1 está instalada? =

Ve a la página de plugins en tu WordPress. Junto al nombre "YGB Slider 2" debe aparecer "Versión 4.2.1". En el código fuente de una página con el slider verás los assets cargados como `?ver=4.2.1`.

== Screenshots ==

1. Panel de administración - Vista compacta de los slides en acordeón
2. Panel de administración - Slide expandido con la sección de programación por fecha visible
3. Panel de administración - Reordenamiento por drag & drop con placeholder visible
4. Panel de administración - Badge verde "Vigente" y badge amarillo "Programado"
5. Dashboard - Sección de programación por fecha con pasos claros y ejemplo visual
6. Dashboard - Sección de efecto aleatorio
7. Dashboard - Sección de modo mixto
8. Panel de configuración - Ajustes de velocidad, autoplay, color y efecto
9. Vista frontal con efecto "deslizar horizontal"
10. Vista frontal con efecto "rotación 3D (cubo)"
11. Vista frontal del modo mixto
12. Widget de Elementor

== Changelog ==

= 4.2.1 =
* MEJORA DE DOCUMENTACIÓN: la sección de programación por fecha del dashboard ahora explica paso a paso cómo configurar las fechas
* Aviso destacado en el dashboard: "Las fechas NO van en el shortcode. Se configuran en el panel de administración"
* Nuevo ejemplo visual del panel admin en el dashboard mostrando dónde están los campos de fecha
* Nueva tabla de ejemplo completo en el dashboard: "Dónde" / "Qué pones" / "Resultado"
* Nuevas FAQ en el readme:
  * ¿Dónde se configuran las fechas de programación?
  * Ejemplo concreto paso a paso (slide 3 del 25 al 30 de noviembre)
  * ¿Qué pasa si NO pongo ninguna fecha en un slide?
  * ¿Puedo combinar slides normales con slides programados?
  * ¿Puedo hacer que un slider muestre solo los programados?
  * ¿Qué comportamiento tienen las fechas?
  * ¿Qué zona horaria se usa?
* Sin cambios de código funcional: solo texto y estructura del dashboard y del readme

= 4.2.0 =
* ELIMINADO: función de parallax por completo
* ELIMINADO: función de fade del texto por completo
* Motivo: problemas de estabilidad en las transiciones con efectos apilados
* Sin cambios en el modelo de datos: no requiere migración
* Se mantienen intactos los 5 efectos, el modo mixto, la selección por número, la programación por fecha y el resto de funciones

= 4.1.0 =
* NUEVA FUNCIÓN: efecto "aleatorio" (`transicion="random"`)
* NUEVA FUNCIÓN: programación por fecha — cada slide puede tener fecha de inicio y/o fin
* Nuevos campos por slide: `fecha_inicio` y `fecha_fin` (opcionales, formato YYYY-MM-DD)
* Validación estricta con `checkdate()`
* Badge visual "Vigente" (verde) y "Programado" (amarillo) en el panel admin
* Filtrado automático en el shortcode: los slides fuera de su ventana se saltan

= 4.0.0 =
* NUEVA FUNCIÓN: modo mixto con el atributo `grupos`
* Cada slide puede tener un efecto distinto dentro del mismo slider
* AUTO-DETECCIÓN: "deslizar" en grupos se sustituye automáticamente por "fundido"
* Nueva función PHP `ygb_slider2_parsear_grupos()`

= 3.9.0 =
* Nueva función: atributo `slides` en el shortcode para seleccionar slides por número
* Soporte de rangos: `slides="1-3"`
* Soporte de orden libre: `slides="1,7,4,2"`

= 3.8.3 =
* Retirado el efecto "deslizar vertical" por problemas de estabilidad

= 3.8.2 =
* Corrección definitiva: los slides ya no se "salen de la ventana"
* Capa de seguridad JS con estilos inline `!important`

= 3.8.1 =
* Corrección crítica: el swipe con ratón funciona en PC
* Bloqueo del drag nativo HTML5 en tres capas

= 3.8.0 =
* Refactor arquitectónico: CSS y JavaScript externalizados
* JavaScript auto-inicializa todas las instancias `.ygb-slider`

= 3.7.1 =
* Corrección: efecto en todos los sliders y esquinas rectas

= 3.7.0 =
* Nueva función: 5 efectos de transición configurables

= 3.6.0 =
* Nueva función: activar/desactivar slides individualmente
* Nueva función: duplicar slide con un clic

= 3.5.0 =
* Nueva función: reordenamiento de slides por drag & drop

= 3.4.0 =
* Interfaz de administración rediseñada con acordeón colapsable

= 3.3.0 =
* Nueva función: navegación táctil por swipe

= 3.2.0 =
* Actualización de versión a 3.2.0
* Compatible con WordPress 7.1 y PHP 8.2

= 3.1 =
* Hardening de seguridad v3.1
* Protección XSS y verificación CSRF

= 3.0 =
* Soporte para enlaces en imágenes
* Opción de target (_self / _blank)

= 2.0 =
* Interfaz rediseñada
* Selector de medios de WordPress integrado

= 1.0 =
* Versión inicial del plugin

== Upgrade Notice ==

= 4.2.1 =
Mejora de documentación: el dashboard y el readme explican ahora paso a paso cómo configurar las fechas de programación. Sin cambios funcionales.

= 4.2.0 =
Elimina por completo las funciones experimentales de parallax y fade del texto por problemas de estabilidad.

= 4.1.0 =
Añade efecto aleatorio y programación por fecha de slides.

= 4.0.0 =
Nueva función mayor: modo mixto con el atributo `grupos`.

= 3.9.0 =
Añade el atributo `slides` para seleccionar qué slides mostrar.

= 3.8.3 =
Retira el efecto "deslizar vertical" por problemas de estabilidad.

= 3.8.2 =
Corrección crítica: los slides ya no se "salen de la ventana".

= 3.8.1 =
Corrección crítica del swipe con ratón en PC.

= 3.8.0 =
Refactor arquitectónico mayor. CSS y JavaScript externalizados.

= 3.7.1 =
Corrección de bugs.

= 3.7.0 =
Añade 5 efectos de transición configurables.

= 3.6.0 =
Añade activación/desactivación individual de slides y duplicado.

= 3.5.0 =
Añade reordenamiento de slides por drag & drop.

= 3.4.0 =
Nueva interfaz administrativa en acordeón.

= 3.3.0 =
Añade navegación táctil por swipe.

= 3.2.0 =
Actualización de mantenimiento.

= 3.1 =
Actualización de seguridad recomendada.

== License ==

GPL-2.0-or-later