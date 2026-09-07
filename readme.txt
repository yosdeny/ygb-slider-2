=== YGB Slider 2 ===
Contributors: ygbteam
Tags: slider, image slider, slideshow, carousel, elementor
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 3.1
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Slider profesional con texto sobre imagen y enlaces configurables. Versión endurecida con protección XSS y CSRF.

== Description ==

YGB Slider 2 es un plugin de slider profesional para WordPress que permite crear presentaciones de imágenes con texto superpuesto y enlaces clickeables.

**Características principales:**

* **Hasta 50 slides** configurables individualmente
* **Enlaces en imágenes** con opción de abrir en misma o nueva ventana
* **Texto sobre imagen** con fondo semitransparente y backdrop blur
* **Velocidad ajustable** desde 1 a 10 segundos
* **Autoplay** activable/desactivable
* **Personalización de color** para las barras de progreso
* **Panel de control en tiempo real** (disponible en modo debug)
* **Widget para Elementor** incluido
* **Optimizado para móviles** con diseño responsive
* **Lazy loading** para mejor rendimiento
* **Intersection Observer** para pausar cuando no es visible

**Seguridad incluida:**

* Protección contra ataques XSS
* Verificación CSRF con nonces
* Validación estricta de URLs (solo http/https)
* Sanitización de todos los inputs
* Escape apropiado de todos los outputs
* Verificación de capacidades de usuario

**Shortcode:**

`[ygb_slider2]`

Atributos opcionales:
* `velocidad` - Velocidad en milisegundos (ej: 4000)
* `autoplay` - Activar autoplay (si/no)
* `admin_panel` - Mostrar panel de control (yes/no, solo con WP_DEBUG activo)

Ejemplo: `[ygb_slider2 velocidad="5000" autoplay="si"]`

== Installation ==

1. Sube el archivo del plugin a `/wp-content/plugins/`
2. Activa el plugin a través de la sección 'Plugins' en WordPress
3. Ve al menú 'YGB Slider 2' para configurar tus slides
4. Usa el shortcode `[ygb_slider2]` en cualquier página o entrada

O usa el widget "YGB Slider 2" en Elementor.

== Frequently Asked Questions ==

= ¿Cuántos slides puedo crear? =

Puedes crear hasta 50 slides por instancia del slider.

= ¿Las imágenes pueden tener enlaces? =

Sí, cada slide puede tener un enlace opcional que se aplica a toda la imagen. Puedes elegir si se abre en la misma ventana o en una nueva.

= ¿Qué formatos de imagen están soportados? =

JPG, JPEG, PNG, GIF, WebP y SVG. Solo se permiten protocolos http y https.

= ¿Puedo cambiar la velocidad del slider? =

Sí, puedes configurar la velocidad entre 1000ms (1s) y 10000ms (10s) desde el panel de configuración o en tiempo real desde el panel de administración (cuando WP_DEBUG está activo).

= ¿Funciona con Elementor? =

Sí, incluye un widget nativo para Elementor que aparece como "YGB Slider 2" en el panel de widgets.

= ¿El slider es responsive? =

Sí, el slider se adapta automáticamente a dispositivos móviles con ajustes específicos para pantallas pequeñas.

== Screenshots ==

1. Panel de administración - Gestión de slides
2. Panel de configuración - Ajustes de velocidad, autoplay y color
3. Vista frontal del slider con texto superpuesto
4. Widget de Elementor

== Changelog ==

= 3.1 =
* Hardening de seguridad v3.1
* Protección XSS mejorada
* Verificación CSRF con nonces en todos los formularios
* Validación estricta de URLs (solo http/https)
* Sanitización de todos los inputs del usuario
* Escape apropiado de todos los outputs
* Verificación de capacidades en todas las funciones admin
* Límites máximos para slides (50) y velocidad (10000ms)

= 3.0 =
* Soporte para enlaces en imágenes
* Opción de target (_self / _blank)
* Mejoras en la validación de URLs

= 2.0 =
* Interfaz rediseñada
* Selector de medios de WordPress integrado
* Vista previa de imágenes

= 1.0 =
* Versión inicial del plugin

== Upgrade Notice ==

= 3.1 =
Actualización de seguridad recomendada. Mejora la protección contra XSS y CSRF.

== License ==

GPL-2.0-or-later