<?php
/**
 * Plugin Name: YGB Slider 2
 * Description: Slider con texto sobre imagen - Con enlaces en imágenes. Versión hardenizada v4.2.1 con navegación táctil, panel administrativo en acordeón, reordenamiento drag & drop, activación/duplicado de slides, efectos de transición configurables, modo mixto, efecto aleatorio y programación por fecha.
 * Version: 4.2.1
 * Plugin URI: https://github.com/yosdeny
 * Author: YGB
 * Author URI: https://github.com/yosdeny
 * Requires at least: 7.0
 * Tested up to: 7.1
 * Requires PHP: 8.0
 * Tested PHP: 8.2
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * 
 * Security: Hardened version v4.2.1 - XSS protected, CSRF safe, strict validation, capability checks
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==================== CONSTANTES ====================
define('YGB_SLIDER2_VERSION', '4.2.1');
define('YGB_SLIDER2_FILE', __FILE__);
define('YGB_SLIDER2_DIR', plugin_dir_path(__FILE__));
define('YGB_SLIDER2_URL', plugin_dir_url(__FILE__));
define('YGB_SLIDER2_MAX_SLIDES', 50);
define('YGB_SLIDER2_MIN_VELOCIDAD', 1000);
define('YGB_SLIDER2_MAX_VELOCIDAD', 10000);
define('YGB_SLIDER2_DEFAULT_VELOCIDAD', 4000);
define('YGB_SLIDER2_DEFAULT_TRANSICION', 'deslizar');

/**
 * Lista blanca de transiciones.
 *
 * @return array
 */
function ygb_slider2_transiciones_permitidas() {
    return array('deslizar', 'fundido', 'zoom', 'cubo', 'random');
}

/**
 * Transiciones disponibles para el efecto aleatorio.
 *
 * @return array
 */
function ygb_slider2_transiciones_random_pool() {
    return array('fundido', 'zoom', 'cubo');
}

/**
 * Lista blanca de transiciones para modo mixto.
 *
 * @return array
 */
function ygb_slider2_transiciones_mixtas_permitidas() {
    return array('fundido', 'zoom', 'cubo');
}

/**
 * Etiquetas de transiciones.
 *
 * @return array
 */
function ygb_slider2_transiciones_labels() {
    return array(
        'deslizar' => esc_html__('Deslizar horizontal', 'ygb-slider-2'),
        'fundido'  => esc_html__('Fundido (crossfade)', 'ygb-slider-2'),
        'zoom'     => esc_html__('Zoom con fundido', 'ygb-slider-2'),
        'cubo'     => esc_html__('Rotación 3D (cubo)', 'ygb-slider-2'),
        'random'   => esc_html__('Aleatorio (fundido/zoom/cubo)', 'ygb-slider-2')
    );
}

/**
 * Normaliza un valor de transición.
 *
 * @param string $valor
 * @return string
 */
function ygb_slider2_normalizar_transicion($valor) {
    $valor = sanitize_key((string) $valor);
    if (!in_array($valor, ygb_slider2_transiciones_permitidas(), true)) {
        return YGB_SLIDER2_DEFAULT_TRANSICION;
    }
    return $valor;
}

/**
 * Valida y normaliza un valor de fecha en formato Y-m-d.
 *
 * @param string $valor
 * @return string
 */
function ygb_slider2_normalizar_fecha($valor) {
    $valor = trim((string) $valor);
    if ($valor === '') {
        return '';
    }
    
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
        return '';
    }
    
    $partes = explode('-', $valor);
    $anyo = (int) $partes[0];
    $mes  = (int) $partes[1];
    $dia  = (int) $partes[2];
    
    if (!checkdate($mes, $dia, $anyo)) {
        return '';
    }
    
    return sprintf('%04d-%02d-%02d', $anyo, $mes, $dia);
}

/**
 * Parsea una lista de slides con soporte de rangos y orden libre.
 *
 * @param string $entrada
 * @param int    $total
 * @return array
 */
function ygb_slider2_parsear_lista_slides($entrada, $total) {
    $entrada = trim((string) $entrada);
    $total   = (int) $total;
    
    if ($entrada === '' || $total <= 0) {
        return array();
    }
    
    $resultado = array();
    $vistos    = array();
    $partes    = explode(',', $entrada);
    
    foreach ($partes as $parte) {
        $parte = trim($parte);
        if ($parte === '') continue;
        
        if (strpos($parte, '-') !== false) {
            $extremos = explode('-', $parte, 2);
            $desde = absint(trim($extremos[0]));
            $hasta = absint(isset($extremos[1]) ? trim($extremos[1]) : '');
            
            if ($desde < 1 || $hasta < 1) continue;
            
            if ($desde > $hasta) {
                $tmp = $desde;
                $desde = $hasta;
                $hasta = $tmp;
            }
            
            if ($desde > $total) continue;
            if ($hasta > $total) $hasta = $total;
            
            for ($i = $desde; $i <= $hasta; $i++) {
                $idx = $i - 1;
                if (isset($vistos[$idx])) continue;
                $vistos[$idx] = true;
                $resultado[]  = $idx;
            }
        } else {
            $num = absint($parte);
            if ($num < 1 || $num > $total) continue;
            $idx = $num - 1;
            if (isset($vistos[$idx])) continue;
            $vistos[$idx] = true;
            $resultado[]  = $idx;
        }
    }
    
    return $resultado;
}

/**
 * Parsea el atributo "grupos" del shortcode.
 *
 * @param string $entrada
 * @param int    $total
 * @return array
 */
function ygb_slider2_parsear_grupos($entrada, $total) {
    $resultado = array(
        'grupos' => array(),
        'avisos' => array()
    );
    
    $entrada = trim((string) $entrada);
    $total   = (int) $total;
    
    if ($entrada === '' || $total <= 0) {
        return $resultado;
    }
    
    $partes = explode('|', $entrada);
    
    foreach ($partes as $parte) {
        $parte = trim($parte);
        if ($parte === '') continue;
        
        $subpartes = explode(':', $parte);
        if (count($subpartes) < 2) continue;
        
        $lista_slides_raw = trim($subpartes[0]);
        $efecto_raw       = trim($subpartes[1]);
        $velocidad_raw    = isset($subpartes[2]) ? trim($subpartes[2]) : '';
        
        if ($lista_slides_raw === '' || $efecto_raw === '') continue;
        
        $efecto = sanitize_key($efecto_raw);
        $fue_convertido = false;
        $transicion_original = $efecto;
        
        if ($efecto === 'deslizar' || $efecto === 'deslizar_vertical') {
            $efecto = 'fundido';
            $fue_convertido = true;
            $resultado['avisos'][] = sprintf(
                esc_html__('El grupo "%s" usaba "deslizar", que no se puede mezclar en modo mixto. Se ha sustituido por "fundido" automáticamente.', 'ygb-slider-2'),
                $lista_slides_raw
            );
        }
        
        if (!in_array($efecto, ygb_slider2_transiciones_mixtas_permitidas(), true)) {
            $efecto = 'fundido';
            $fue_convertido = true;
            $resultado['avisos'][] = sprintf(
                esc_html__('El efecto "%1$s" del grupo "%2$s" no es válido en modo mixto. Se ha sustituido por "fundido".', 'ygb-slider-2'),
                $efecto_raw,
                $lista_slides_raw
            );
        }
        
        $indices = ygb_slider2_parsear_lista_slides($lista_slides_raw, $total);
        if (empty($indices)) continue;
        
        $velocidad = null;
        if ($velocidad_raw !== '') {
            $v = absint($velocidad_raw);
            if ($v >= YGB_SLIDER2_MIN_VELOCIDAD && $v <= YGB_SLIDER2_MAX_VELOCIDAD) {
                $velocidad = $v;
            }
        }
        
        $resultado['grupos'][] = array(
            'indices'             => $indices,
            'efecto'              => $efecto,
            'velocidad'           => $velocidad,
            'transicion_original' => $transicion_original,
            'fue_convertido'      => $fue_convertido
        );
    }
    
    return $resultado;
}

/**
 * Comprueba si un slide está dentro de su ventana de publicación.
 *
 * @param array $slide
 * @return bool
 */
function ygb_slider2_slide_en_ventana($slide) {
    $hoy = wp_date('Y-m-d');
    
    $fecha_inicio = isset($slide['fecha_inicio']) ? (string) $slide['fecha_inicio'] : '';
    $fecha_fin    = isset($slide['fecha_fin'])    ? (string) $slide['fecha_fin']    : '';
    
    if ($fecha_inicio !== '' && $hoy < $fecha_inicio) return false;
    if ($fecha_fin !== '' && $hoy > $fecha_fin) return false;
    
    return true;
}

// ==================== ACTIVACIÓN ====================
function ygb_slider2_activar() {
    $existentes = get_option('ygb_slider2_slides', false);
    
    if ($existentes === false) {
        $slides = array(
            array('img' => '', 'titulo' => esc_html__('Bienvenidos', 'ygb-slider-2'), 'texto' => esc_html__('Descubre nuestro contenido', 'ygb-slider-2'), 'url' => '', 'target' => '_self', 'activo' => 0, 'fecha_inicio' => '', 'fecha_fin' => ''),
            array('img' => '', 'titulo' => esc_html__('Ofertas Especiales', 'ygb-slider-2'), 'texto' => esc_html__('No te las pierdas', 'ygb-slider-2'), 'url' => '', 'target' => '_self', 'activo' => 0, 'fecha_inicio' => '', 'fecha_fin' => ''),
            array('img' => '', 'titulo' => esc_html__('Contacto', 'ygb-slider-2'), 'texto' => esc_html__('Estamos para ayudarte', 'ygb-slider-2'), 'url' => '', 'target' => '_self', 'activo' => 0, 'fecha_inicio' => '', 'fecha_fin' => '')
        );
        add_option('ygb_slider2_slides', $slides);
    }
    
    if (!get_option('ygb_slider2_velocidad')) add_option('ygb_slider2_velocidad', YGB_SLIDER2_DEFAULT_VELOCIDAD);
    if (!get_option('ygb_slider2_autoplay')) add_option('ygb_slider2_autoplay', 1);
    if (!get_option('ygb_slider2_color')) add_option('ygb_slider2_color', '#ff6b6b');
    
    $trans_actual = get_option('ygb_slider2_transicion', YGB_SLIDER2_DEFAULT_TRANSICION);
    update_option('ygb_slider2_transicion', ygb_slider2_normalizar_transicion($trans_actual));
}
register_activation_hook(__FILE__, 'ygb_slider2_activar');

// ==================== ENCOLADO DE ASSETS ====================
function ygb_slider2_enqueue_frontend_assets() {
    if (is_admin()) return;
    
    wp_enqueue_style('ygb-slider-2', YGB_SLIDER2_URL . 'assets/ygb-slider-2.css', array(), YGB_SLIDER2_VERSION);
    wp_enqueue_script('ygb-slider-2', YGB_SLIDER2_URL . 'assets/ygb-slider-2.js', array(), YGB_SLIDER2_VERSION, true);
}
add_action('wp_enqueue_scripts', 'ygb_slider2_enqueue_frontend_assets');

function ygb_slider2_admin_scripts($hook) {
    if (strpos($hook, 'ygb-slider-2') === false) return;
    wp_enqueue_media();
    wp_enqueue_script('jquery-ui-sortable');
}
add_action('admin_enqueue_scripts', 'ygb_slider2_admin_scripts');

// ==================== MENÚ ADMIN ====================
function ygb_slider2_menu() {
    add_menu_page(
        esc_html__('YGB Slider 2', 'ygb-slider-2'),
        esc_html__('YGB Slider 2', 'ygb-slider-2'),
        'manage_options',
        'ygb-slider-2',
        'ygb_slider2_dashboard',
        'dashicons-slides',
        26
    );
    
    add_submenu_page('ygb-slider-2', esc_html__('Slides', 'ygb-slider-2'), esc_html__('Slides', 'ygb-slider-2'), 'manage_options', 'ygb-slider-2-slides', 'ygb_slider2_slides');
    add_submenu_page('ygb-slider-2', esc_html__('Configurar', 'ygb-slider-2'), esc_html__('Configurar', 'ygb-slider-2'), 'manage_options', 'ygb-slider-2-config', 'ygb_slider2_config');
}
add_action('admin_menu', 'ygb_slider2_menu');

function ygb_slider2_contar_slides($slides) {
    $total = 0;
    $activos = 0;
    
    if (!is_array($slides)) {
        return array('total' => 0, 'activos' => 0, 'inactivos' => 0);
    }
    
    foreach ($slides as $s) {
        $total++;
        $a = isset($s['activo']) ? (int) $s['activo'] : 1;
        if ($a === 1) $activos++;
    }
    
    return array('total' => $total, 'activos' => $activos, 'inactivos' => $total - $activos);
}

function ygb_slider2_dashboard() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('No tienes permisos.', 'ygb-slider-2'));
    }
    
    $slides = get_option('ygb_slider2_slides', array());
    $stats  = ygb_slider2_contar_slides($slides);
    $vel    = absint(get_option('ygb_slider2_velocidad', YGB_SLIDER2_DEFAULT_VELOCIDAD));
    
    $transicion = ygb_slider2_normalizar_transicion(get_option('ygb_slider2_transicion', YGB_SLIDER2_DEFAULT_TRANSICION));
    $labels = ygb_slider2_transiciones_labels();
    $trans_label = isset($labels[$transicion]) ? $labels[$transicion] : $transicion;
    
    $total_slides = is_array($slides) ? count($slides) : 0;
    
    $programados = 0;
    $vigentes = 0;
    if (is_array($slides)) {
        foreach ($slides as $s) {
            $fi = isset($s['fecha_inicio']) ? (string) $s['fecha_inicio'] : '';
            $ff = isset($s['fecha_fin']) ? (string) $s['fecha_fin'] : '';
            if ($fi !== '' || $ff !== '') {
                $programados++;
                if (ygb_slider2_slide_en_ventana($s)) $vigentes++;
            }
        }
    }
    
    settings_errors('ygb_slider2');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div style="display:flex; gap:20px; margin:20px 0; flex-wrap:wrap">
            <div style="background:white; padding:20px; border-radius:10px; text-align:center; flex:1; min-width:180px; box-shadow:0 1px 3px rgba(0,0,0,0.06)">
                <div style="font-size:36px; font-weight:bold; color:#2271b1">
                    <?php echo absint($stats['activos']); ?>
                    <span style="font-size:20px; color:#8c8f94; font-weight:400">/ <?php echo absint($stats['total']); ?></span>
                </div>
                <div><?php esc_html_e('Slides activos / Total', 'ygb-slider-2'); ?></div>
            </div>
            <div style="background:white; padding:20px; border-radius:10px; text-align:center; flex:1; min-width:180px; box-shadow:0 1px 3px rgba(0,0,0,0.06)">
                <div style="font-size:36px; font-weight:bold; color:#2271b1"><?php echo esc_html($vel / 1000); ?>s</div>
                <div><?php esc_html_e('Velocidad global', 'ygb-slider-2'); ?></div>
            </div>
            <div style="background:white; padding:20px; border-radius:10px; text-align:center; flex:1; min-width:180px; box-shadow:0 1px 3px rgba(0,0,0,0.06)">
                <div style="font-size:20px; font-weight:bold; color:#2271b1; margin-bottom:6px"><?php echo esc_html($trans_label); ?></div>
                <div><?php esc_html_e('Efecto global', 'ygb-slider-2'); ?></div>
            </div>
            <?php if ($programados > 0) : ?>
            <div style="background:white; padding:20px; border-radius:10px; text-align:center; flex:1; min-width:180px; box-shadow:0 1px 3px rgba(0,0,0,0.06)">
                <div style="font-size:36px; font-weight:bold; color:#2271b1">
                    <?php echo absint($vigentes); ?>
                    <span style="font-size:20px; color:#8c8f94; font-weight:400">/ <?php echo absint($programados); ?></span>
                </div>
                <div><?php esc_html_e('Programados activos hoy', 'ygb-slider-2'); ?></div>
            </div>
            <?php endif; ?>
        </div>
        
        <div style="background:white; padding:20px; border-radius:10px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.06); border-left:4px solid #8b5cf6">
            <h2 style="margin-top:0">🎲 <?php esc_html_e('Efecto aleatorio', 'ygb-slider-2'); ?></h2>
            <p style="color:#50575e">
                <?php esc_html_e('Cada transición elige aleatoriamente entre fundido, zoom y cubo. Cada cambio de slide es distinto.', 'ygb-slider-2'); ?>
            </p>
            <pre style="background:#f6f7f7; padding:12px; border-radius:6px; overflow-x:auto; font-size:13px">[ygb_slider2 transicion="random"]</pre>
        </div>
        
        <div style="background:white; padding:20px; border-radius:10px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.06); border-left:4px solid #10b981">
            <h2 style="margin-top:0">📅 <?php esc_html_e('Programación por fecha', 'ygb-slider-2'); ?></h2>
            
            <div style="background:#fff3cd; border-left:4px solid #dba617; padding:12px 16px; border-radius:4px; margin:15px 0">
                <strong>⚠️ <?php esc_html_e('Las fechas NO van en el shortcode.', 'ygb-slider-2'); ?></strong><br>
                <?php esc_html_e('Se configuran en el panel de administración, en la página "Slides", expandiendo cada slide. El shortcode solo dice qué slides mostrar.', 'ygb-slider-2'); ?>
            </div>
            
            <h3><?php esc_html_e('Cómo se configuran las fechas', 'ygb-slider-2'); ?></h3>
            <ol style="padding-left:20px; color:#50575e; line-height:1.8">
                <li><?php esc_html_e('Ve a YGB Slider 2 → Slides', 'ygb-slider-2'); ?></li>
                <li><?php esc_html_e('Haz clic en cualquier slide para expandirlo', 'ygb-slider-2'); ?></li>
                <li><?php esc_html_e('Al final del formulario verás la sección verde "📅 Programación por fecha (opcional)"', 'ygb-slider-2'); ?></li>
                <li><?php esc_html_e('Rellena la Fecha de inicio y/o la Fecha de fin con el selector de fecha', 'ygb-slider-2'); ?></li>
                <li><?php esc_html_e('Pulsa "Guardar Slides"', 'ygb-slider-2'); ?></li>
            </ol>
            
            <h3><?php esc_html_e('Así se ve en el panel', 'ygb-slider-2'); ?></h3>
            <pre style="background:#f6f7f7; padding:15px; border-radius:6px; overflow-x:auto; font-size:12px; line-height:1.5; color:#1d2327">Panel admin → YGB Slider 2 → Slides → expandir un slide

┌──────────────────────────────────────────────┐
│  Imagen:       [__________________] [Elegir] │
│  Título:       [50% en todo______]           │
│  Descripción:  [__________________]          │
│  URL:          [__________________]          │
│  Abrir enlace: [Misma ventana ▼]             │
│                                              │
│  📅 Programación por fecha (opcional)        │
│  ┌─────────────────┬─────────────────┐       │
│  │ Fecha inicio:   │ Fecha fin:      │       │
│  │ [25/11/2026]    │ [30/11/2026]    │       │
│  └─────────────────┴─────────────────┘       │
└──────────────────────────────────────────────┘</pre>
            
            <h3><?php esc_html_e('Ejemplo completo', 'ygb-slider-2'); ?></h3>
            <table class="widefat striped" style="border-radius:6px; overflow:hidden">
                <thead>
                    <tr>
                        <th style="width:30%"><?php esc_html_e('Dónde', 'ygb-slider-2'); ?></th>
                        <th><?php esc_html_e('Qué pones', 'ygb-slider-2'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong><?php esc_html_e('Panel admin → Slides → Slide 3', 'ygb-slider-2'); ?></strong></td>
                        <td>
                            <?php esc_html_e('Fecha inicio: 2026-11-25', 'ygb-slider-2'); ?><br>
                            <?php esc_html_e('Fecha fin: 2026-11-30', 'ygb-slider-2'); ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Página (shortcode)', 'ygb-slider-2'); ?></strong></td>
                        <td><code style="background:#f0f0f1; padding:3px 6px; border-radius:3px">[ygb_slider2 slides="3"]</code></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Resultado', 'ygb-slider-2'); ?></strong></td>
                        <td><?php esc_html_e('El slide 3 solo aparece entre el 25 y el 30 de noviembre. El resto del año no se muestra. Sin que tú toques nada.', 'ygb-slider-2'); ?></td>
                    </tr>
                </tbody>
            </table>
            
            <h3><?php esc_html_e('Comportamiento según las fechas', 'ygb-slider-2'); ?></h3>
            <ul style="list-style:disc; padding-left:20px; color:#50575e">
                <li><strong><?php esc_html_e('Sin fechas:', 'ygb-slider-2'); ?></strong> <?php esc_html_e('el slide siempre se muestra.', 'ygb-slider-2'); ?></li>
                <li><strong><?php esc_html_e('Solo fecha de inicio:', 'ygb-slider-2'); ?></strong> <?php esc_html_e('el slide aparece a partir de esa fecha (para siempre).', 'ygb-slider-2'); ?></li>
                <li><strong><?php esc_html_e('Solo fecha de fin:', 'ygb-slider-2'); ?></strong> <?php esc_html_e('el slide aparece desde siempre hasta esa fecha.', 'ygb-slider-2'); ?></li>
                <li><strong><?php esc_html_e('Ambas fechas:', 'ygb-slider-2'); ?></strong> <?php esc_html_e('el slide solo aparece dentro del rango (ambas inclusive).', 'ygb-slider-2'); ?></li>
            </ul>
        </div>
        
        <div style="background:white; padding:20px; border-radius:10px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.06); border-left:4px solid #2271b1">
            <h2 style="margin-top:0">✨ <?php esc_html_e('Modo mixto: varios efectos en el mismo slider', 'ygb-slider-2'); ?></h2>
            <p style="color:#50575e">
                <?php esc_html_e('Puedes asignar un efecto distinto a cada slide dentro del mismo slider.', 'ygb-slider-2'); ?>
            </p>
            
            <div style="background:#fff3cd; border-left:4px solid #dba617; padding:12px 16px; border-radius:4px; margin:15px 0">
                <strong>⚠️ <?php esc_html_e('Importante:', 'ygb-slider-2'); ?></strong>
                <?php esc_html_e('El efecto "deslizar" NO se puede usar en modo mixto. Si se incluye, se sustituye automáticamente por "fundido".', 'ygb-slider-2'); ?>
            </div>
            
            <table class="widefat striped" style="border-radius:6px; overflow:hidden">
                <thead>
                    <tr>
                        <th style="width:50%"><?php esc_html_e('Shortcode', 'ygb-slider-2'); ?></th>
                        <th><?php esc_html_e('Resultado', 'ygb-slider-2'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td><code>[ygb_slider2 grupos="1,3:zoom|2,4:cubo"]</code></td><td><?php esc_html_e('Slides 1 y 3 con zoom, 2 y 4 con cubo', 'ygb-slider-2'); ?></td></tr>
                    <tr><td><code>[ygb_slider2 grupos="1-3:zoom|4-6:cubo|7,8:fundido"]</code></td><td><?php esc_html_e('3 grupos consecutivos', 'ygb-slider-2'); ?></td></tr>
                    <tr><td><code>[ygb_slider2 grupos="1,3:zoom:6000|2,4:cubo:4000"]</code></td><td><?php esc_html_e('Con velocidad por grupo', 'ygb-slider-2'); ?></td></tr>
                </tbody>
            </table>
        </div>
        
        <div style="background:white; padding:20px; border-radius:10px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.06)">
            <h2 style="margin-top:0"><?php esc_html_e('Ejemplos de uso (modo clásico)', 'ygb-slider-2'); ?></h2>
            <table class="widefat striped" style="border-radius:6px; overflow:hidden">
                <thead>
                    <tr>
                        <th style="width:22%"><?php esc_html_e('Uso', 'ygb-slider-2'); ?></th>
                        <th style="width:40%"><?php esc_html_e('Shortcode', 'ygb-slider-2'); ?></th>
                        <th><?php esc_html_e('Resultado', 'ygb-slider-2'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td><strong><?php esc_html_e('Todos los slides', 'ygb-slider-2'); ?></strong></td><td><code>[ygb_slider2]</code></td><td><?php esc_html_e('Todos los slides activos', 'ygb-slider-2'); ?></td></tr>
                    <tr><td><strong><?php esc_html_e('Un solo slide', 'ygb-slider-2'); ?></strong></td><td><code>[ygb_slider2 slides="3"]</code></td><td><?php esc_html_e('Solo el slide 3', 'ygb-slider-2'); ?></td></tr>
                    <tr><td><strong><?php esc_html_e('Varios en orden libre', 'ygb-slider-2'); ?></strong></td><td><code>[ygb_slider2 slides="1,7,4,2"]</code></td><td><?php esc_html_e('En ese orden exacto', 'ygb-slider-2'); ?></td></tr>
                    <tr><td><strong><?php esc_html_e('Rango', 'ygb-slider-2'); ?></strong></td><td><code>[ygb_slider2 slides="1-3"]</code></td><td><?php esc_html_e('Slides 1, 2 y 3', 'ygb-slider-2'); ?></td></tr>
                    <tr><td><strong><?php esc_html_e('Cambiar efecto', 'ygb-slider-2'); ?></strong></td><td><code>[ygb_slider2 transicion="cubo"]</code></td><td><?php esc_html_e('Todos con cubo', 'ygb-slider-2'); ?></td></tr>
                    <tr><td><strong><?php esc_html_e('Aleatorio', 'ygb-slider-2'); ?></strong></td><td><code>[ygb_slider2 transicion="random"]</code></td><td><?php esc_html_e('Efecto aleatorio en cada transición', 'ygb-slider-2'); ?></td></tr>
                </tbody>
            </table>
        </div>
        
        <div style="background:white; padding:20px; border-radius:10px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.06)">
            <h2 style="margin-top:0"><?php esc_html_e('¿Cómo saber el número de cada slide?', 'ygb-slider-2'); ?></h2>
            <p style="color:#50575e">
                <?php printf(
                    esc_html__('En la página "Slides" cada slide tiene un número circular azul a la izquierda de su encabezado. Ese número es la posición en el orden actual. Actualmente tienes %d slides en el pool.', 'ygb-slider-2'),
                    absint($total_slides)
                ); ?>
            </p>
            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=ygb-slider-2-slides')); ?>" class="button button-primary"><?php esc_html_e('Ir a la página de Slides', 'ygb-slider-2'); ?></a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=ygb-slider-2-config')); ?>" class="button"><?php esc_html_e('Ir a Configurar', 'ygb-slider-2'); ?></a>
            </p>
        </div>
        
        <div style="background:white; padding:20px; border-radius:10px; box-shadow:0 1px 3px rgba(0,0,0,0.06)">
            <h2 style="margin-top:0"><?php esc_html_e('Atributos del shortcode', 'ygb-slider-2'); ?></h2>
            <ul style="list-style:disc; padding-left:20px; color:#50575e">
                <li><code>grupos</code> — <?php esc_html_e('Modo mixto con varios efectos. Ej: "1,3:zoom|2,4:cubo".', 'ygb-slider-2'); ?></li>
                <li><code>slides</code> — <?php esc_html_e('Selección de slides por número. Ej: "1,7,4,2" o "1-3,7,10".', 'ygb-slider-2'); ?></li>
                <li><code>velocidad</code> — <?php esc_html_e('Milisegundos (1000–10000). Ej: "4000".', 'ygb-slider-2'); ?></li>
                <li><code>autoplay</code> — <?php esc_html_e('"si" o "no".', 'ygb-slider-2'); ?></li>
                <li><code>transicion</code> — <?php esc_html_e('"deslizar", "fundido", "zoom", "cubo" o "random".', 'ygb-slider-2'); ?></li>
                <li><code>admin_panel</code> — <?php esc_html_e('"yes" para mostrar controles en vivo (requiere WP_DEBUG).', 'ygb-slider-2'); ?></li>
            </ul>
            <p style="color:#50575e; margin-bottom:0; background:#f0f6fc; padding:10px 14px; border-radius:4px; border-left:3px solid #2271b1">
                <?php esc_html_e('Recordatorio: las FECHAS no son un atributo del shortcode. Se configuran en el panel de administración, en cada slide.', 'ygb-slider-2'); ?>
            </p>
        </div>
    </div>
    <?php
}

// ==================== VALIDACIONES ====================
function ygb_slider2_validar_img_url($url) {
    if (empty($url)) return false;
    
    $protocolo = parse_url($url, PHP_URL_SCHEME);
    if (!in_array($protocolo, array('http', 'https'), true)) return false;
    if (!filter_var($url, FILTER_VALIDATE_URL)) return false;
    
    $extensiones = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg');
    $path = parse_url($url, PHP_URL_PATH);
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    
    if (!empty($ext) && !in_array($ext, $extensiones, true)) return false;
    return true;
}

function ygb_slider2_validar_url_enlace($url) {
    if (empty($url)) return '';
    
    $url = esc_url_raw($url);
    $protocolo = parse_url($url, PHP_URL_SCHEME);
    
    if (!in_array($protocolo, array('http', 'https'), true)) return '';
    if (!filter_var($url, FILTER_VALIDATE_URL)) return '';
    return $url;
}

// ==================== GESTIÓN DE SLIDES ====================
function ygb_slider2_slides() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('No tienes permisos.', 'ygb-slider-2'));
    }
    
    $errores = array();
    $exitos = array();
    
    if (isset($_POST['guardar_slides'])) {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'accion_slides2')) {
            wp_die(esc_html__('Verificación de seguridad fallida.', 'ygb-slider-2'));
        }
        
        $imagenes_raw      = isset($_POST['img']) ? wp_unslash($_POST['img']) : array();
        $titulos_raw       = isset($_POST['titulo']) ? wp_unslash($_POST['titulo']) : array();
        $textos_raw        = isset($_POST['texto']) ? wp_unslash($_POST['texto']) : array();
        $urls_raw          = isset($_POST['url']) ? wp_unslash($_POST['url']) : array();
        $targets_raw       = isset($_POST['target']) ? wp_unslash($_POST['target']) : array();
        $activos_raw       = isset($_POST['activo']) ? wp_unslash($_POST['activo']) : array();
        $fechas_ini_raw    = isset($_POST['fecha_inicio']) ? wp_unslash($_POST['fecha_inicio']) : array();
        $fechas_fin_raw    = isset($_POST['fecha_fin']) ? wp_unslash($_POST['fecha_fin']) : array();
        
        if (!is_array($imagenes_raw)) $imagenes_raw = array();
        if (!is_array($activos_raw)) $activos_raw = array();
        if (!is_array($fechas_ini_raw)) $fechas_ini_raw = array();
        if (!is_array($fechas_fin_raw)) $fechas_fin_raw = array();
        
        $imagenes_raw = array_slice($imagenes_raw, 0, YGB_SLIDER2_MAX_SLIDES);
        
        $slides = array();
        
        for ($i = 0; $i < count($imagenes_raw); $i++) {
            $img_raw = isset($imagenes_raw[$i]) ? $imagenes_raw[$i] : '';
            if (empty($img_raw)) continue;
            
            if (!ygb_slider2_validar_img_url($img_raw)) {
                $errores[] = sprintf(esc_html__('Slide %d: URL de imagen inválida.', 'ygb-slider-2'), $i + 1);
                continue;
            }
            
            $img_url = esc_url_raw($img_raw);
            $titulo = isset($titulos_raw[$i]) ? sanitize_text_field($titulos_raw[$i]) : '';
            
            $allowed_html = array(
                'strong' => array(), 'em' => array(), 'b' => array(), 'i' => array(), 'br' => array(),
                'a' => array('href' => array(), 'title' => array(), 'target' => array())
            );
            $texto = isset($textos_raw[$i]) ? wp_kses($textos_raw[$i], $allowed_html) : '';
            
            $url_raw = isset($urls_raw[$i]) ? $urls_raw[$i] : '';
            $url = ygb_slider2_validar_url_enlace($url_raw);
            
            if (!empty($url_raw) && empty($url)) {
                $errores[] = sprintf(esc_html__('Slide %d: URL de enlace inválida.', 'ygb-slider-2'), $i + 1);
            }
            
            $target_raw = isset($targets_raw[$i]) ? sanitize_key($targets_raw[$i]) : '_self';
            $target = ($target_raw === '_blank') ? '_blank' : '_self';
            
            $activo = isset($activos_raw[$i]) ? (absint($activos_raw[$i]) ? 1 : 0) : 0;
            
            $fecha_ini_raw = isset($fechas_ini_raw[$i]) ? $fechas_ini_raw[$i] : '';
            $fecha_fin_raw = isset($fechas_fin_raw[$i]) ? $fechas_fin_raw[$i] : '';
            
            $fecha_inicio = ygb_slider2_normalizar_fecha($fecha_ini_raw);
            $fecha_fin    = ygb_slider2_normalizar_fecha($fecha_fin_raw);
            
            if (!empty($fecha_ini_raw) && empty($fecha_inicio)) {
                $errores[] = sprintf(esc_html__('Slide %d: fecha de inicio inválida.', 'ygb-slider-2'), $i + 1);
            }
            if (!empty($fecha_fin_raw) && empty($fecha_fin)) {
                $errores[] = sprintf(esc_html__('Slide %d: fecha de fin inválida.', 'ygb-slider-2'), $i + 1);
            }
            
            if (!empty($fecha_inicio) && !empty($fecha_fin) && $fecha_fin < $fecha_inicio) {
                $errores[] = sprintf(esc_html__('Slide %d: la fecha de fin no puede ser anterior a la fecha de inicio.', 'ygb-slider-2'), $i + 1);
                $fecha_fin = '';
            }
            
            $slides[] = array(
                'img'          => $img_url,
                'titulo'       => $titulo,
                'texto'        => $texto,
                'url'          => $url,
                'target'       => $target,
                'activo'       => $activo,
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin'    => $fecha_fin
            );
        }
        
        if (!empty($slides)) {
            update_option('ygb_slider2_slides', $slides);
            $stats = ygb_slider2_contar_slides($slides);
            $exitos[] = sprintf(
                esc_html__('Slides guardadas correctamente (%1$d en total, %2$d activas).', 'ygb-slider-2'),
                (int) $stats['total'], (int) $stats['activos']
            );
        } else {
            $errores[] = esc_html__('Error: Debes tener al menos un slide válido con imagen.', 'ygb-slider-2');
        }
        
        foreach ($exitos as $exito) add_settings_error('ygb_slider2', 'slides_guardadas', $exito, 'success');
        foreach ($errores as $error) add_settings_error('ygb_slider2', 'slides_error', $error, 'error');
    }
    
    $slides = get_option('ygb_slider2_slides', array());
    if (empty($slides)) {
        $slides = array(array('img' => '', 'titulo' => '', 'texto' => '', 'url' => '', 'target' => '_self', 'activo' => 0, 'fecha_inicio' => '', 'fecha_fin' => ''));
    }
    
    settings_errors('ygb_slider2');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <p class="description">
            <?php printf(
                esc_html__('Máximo %d slides permitidos. Haz clic en cada slide para expandir y editar. Arrastra desde el encabezado para reordenar.', 'ygb-slider-2'),
                absint(YGB_SLIDER2_MAX_SLIDES)
            ); ?>
        </p>
        
        <form method="post" action="">
            <?php wp_nonce_field('accion_slides2'); ?>
            
            <div id="lista_slides">
                <?php foreach ($slides as $i => $s) : 
                    $slide_numero   = $i + 1;
                    $tiene_img      = !empty($s['img']);
                    $tiene_titulo   = !empty($s['titulo']);
                    $nombre_mostrar = $tiene_titulo ? $s['titulo'] : esc_html__('Sin título', 'ygb-slider-2');
                    $clase_nombre   = $tiene_titulo ? 'slide_nombre' : 'slide_nombre ygb-vacio';
                    $clase_estado   = $tiene_img ? 'slide_estado_imagen ygb-tiene-img' : 'slide_estado_imagen';
                    $texto_estado   = $tiene_img ? esc_html__('Con imagen', 'ygb-slider-2') : esc_html__('Sin imagen', 'ygb-slider-2');
                    $activo = isset($s['activo']) ? (int) $s['activo'] : 1;
                    $clase_box = 'slide_box' . (!$activo ? ' ygb-inactivo' : '');
                    
                    $fecha_inicio = isset($s['fecha_inicio']) ? (string) $s['fecha_inicio'] : '';
                    $fecha_fin    = isset($s['fecha_fin'])    ? (string) $s['fecha_fin']    : '';
                    
                    $esta_programado = ($fecha_inicio !== '' || $fecha_fin !== '');
                    $en_ventana = ygb_slider2_slide_en_ventana($s);
                    $clase_programado = '';
                    if ($esta_programado) {
                        $clase_programado = $en_ventana ? 'ygb-programado-vigente' : 'ygb-programado-inactivo';
                    }
                ?>
                <div class="<?php echo esc_attr($clase_box . ' ' . $clase_programado); ?>" data-index="<?php echo absint($i); ?>">
                    
                    <div class="slide_header" role="button" tabindex="0" aria-expanded="false">
                        <div class="slide_header_left">
                            <span class="slide_drag_handle dashicons dashicons-menu" aria-hidden="true"></span>
                            <span class="slide_numero"><?php echo absint($slide_numero); ?></span>
                            <div class="slide_mini_preview">
                                <?php if ($tiene_img) : ?>
                                    <img src="<?php echo esc_url($s['img']); ?>" alt="">
                                <?php else : ?>
                                    <span class="dashicons dashicons-format-image"></span>
                                <?php endif; ?>
                            </div>
                            <span class="<?php echo esc_attr($clase_nombre); ?>"><?php echo esc_html($nombre_mostrar); ?></span>
                            
                            <?php if ($esta_programado) : ?>
                                <span class="slide_estado_programado <?php echo $en_ventana ? 'ygb-vigente' : 'ygb-fuera'; ?>">
                                    📅 <?php echo $en_ventana ? esc_html__('Vigente', 'ygb-slider-2') : esc_html__('Programado', 'ygb-slider-2'); ?>
                                </span>
                            <?php endif; ?>
                            
                            <span class="<?php echo esc_attr($clase_estado); ?>"><?php echo esc_html($texto_estado); ?></span>
                        </div>
                        
                        <div class="slide_header_right">
                            <label class="ygb-toggle" title="<?php esc_attr_e('Activar / Desactivar slide', 'ygb-slider-2'); ?>">
                                <input type="checkbox" class="ygb-toggle-check" <?php checked($activo, 1); ?>>
                                <span class="ygb-toggle-slider"></span>
                            </label>
                            <input type="hidden" name="activo[]" class="activo_hidden" value="<?php echo $activo ? '1' : '0'; ?>">
                            
                            <button type="button" class="button duplicar_slide"><?php esc_html_e('Duplicar', 'ygb-slider-2'); ?></button>
                            <button type="button" class="button eliminar_slide" style="background:#dc3232; color:white; border-color:#dc3232"><?php esc_html_e('Eliminar', 'ygb-slider-2'); ?></button>
                            <span class="slide_chevron" aria-hidden="true">▸</span>
                        </div>
                    </div>
                    
                    <div class="slide_body">
                        <div style="margin-bottom:15px">
                            <label style="font-weight:bold; display:block; margin-bottom:5px"><?php esc_html_e('Imagen:', 'ygb-slider-2'); ?></label>
                            <div style="display:flex; gap:10px">
                                <input type="text" name="img[]" class="img_url" value="<?php echo esc_attr($s['img']); ?>" style="flex:1" placeholder="https://">
                                <button type="button" class="button subir_img"><?php esc_html_e('Seleccionar', 'ygb-slider-2'); ?></button>
                            </div>
                            <?php if (!empty($s['img'])) : ?>
                            <div class="vista_previa" style="margin-top:10px">
                                <img src="<?php echo esc_url($s['img']); ?>" style="max-width:200px; max-height:150px; object-fit:cover;" alt="">
                            </div>
                            <?php else: ?>
                            <div class="vista_previa" style="margin-top:10px; display:none;"></div>
                            <?php endif; ?>
                        </div>
                        
                        <div style="margin-bottom:15px">
                            <label style="font-weight:bold; display:block; margin-bottom:5px"><?php esc_html_e('Título:', 'ygb-slider-2'); ?></label>
                            <input type="text" name="titulo[]" value="<?php echo esc_attr($s['titulo']); ?>" style="width:100%" maxlength="200">
                        </div>
                        
                        <div style="margin-bottom:15px">
                            <label style="font-weight:bold; display:block; margin-bottom:5px"><?php esc_html_e('Descripción:', 'ygb-slider-2'); ?></label>
                            <textarea name="texto[]" rows="3" style="width:100%" maxlength="500"><?php echo esc_textarea($s['texto']); ?></textarea>
                            <p class="description"><?php esc_html_e('HTML permitido:', 'ygb-slider-2'); ?> <code>&lt;strong&gt;, &lt;em&gt;, &lt;a href&gt;, &lt;br&gt;</code></p>
                        </div>
                        
                        <div style="margin-bottom:15px">
                            <label style="font-weight:bold; display:block; margin-bottom:5px"><?php esc_html_e('URL del enlace (opcional):', 'ygb-slider-2'); ?></label>
                            <input type="url" name="url[]" value="<?php echo esc_attr($s['url']); ?>" style="width:100%" placeholder="https://ejemplo.com">
                        </div>
                        
                        <div style="margin-bottom:15px">
                            <label style="font-weight:bold; display:block; margin-bottom:5px"><?php esc_html_e('Abrir enlace en:', 'ygb-slider-2'); ?></label>
                            <select name="target[]" style="width:100%">
                                <option value="_self" <?php selected($s['target'], '_self'); ?>><?php esc_html_e('Misma ventana', 'ygb-slider-2'); ?></option>
                                <option value="_blank" <?php selected($s['target'], '_blank'); ?>><?php esc_html_e('Nueva ventana', 'ygb-slider-2'); ?></option>
                            </select>
                        </div>
                        
                        <div style="background:#f6f7f7; padding:14px; border-radius:6px; margin-top:5px; border-left:3px solid #10b981">
                            <label style="font-weight:bold; display:block; margin-bottom:8px">
                                📅 <?php esc_html_e('Programación por fecha (opcional)', 'ygb-slider-2'); ?>
                            </label>
                            
                            <div style="display:flex; gap:12px; flex-wrap:wrap">
                                <div style="flex:1; min-width:180px">
                                    <label style="font-size:12px; color:#50575e; display:block; margin-bottom:3px">
                                        <?php esc_html_e('Fecha de inicio:', 'ygb-slider-2'); ?>
                                    </label>
                                    <input type="date" name="fecha_inicio[]" class="fecha_inicio_input" value="<?php echo esc_attr($fecha_inicio); ?>" style="width:100%">
                                </div>
                                <div style="flex:1; min-width:180px">
                                    <label style="font-size:12px; color:#50575e; display:block; margin-bottom:3px">
                                        <?php esc_html_e('Fecha de fin:', 'ygb-slider-2'); ?>
                                    </label>
                                    <input type="date" name="fecha_fin[]" class="fecha_fin_input" value="<?php echo esc_attr($fecha_fin); ?>" style="width:100%">
                                </div>
                            </div>
                            
                            <p class="description" style="margin-top:8px; margin-bottom:0">
                                <?php esc_html_e('Deja ambas vacías para que el slide esté siempre disponible. Con solo una fecha, el slide aparece desde/hasta esa fecha. Con ambas, solo dentro del rango.', 'ygb-slider-2'); ?>
                            </p>
                        </div>
                    </div>
                    
                </div>
                <?php endforeach; ?>
            </div>
            
            <div style="margin:20px 0">
                <button type="button" id="agregar_slide" class="button button-secondary" data-max="<?php echo absint(YGB_SLIDER2_MAX_SLIDES); ?>">
                    + <?php esc_html_e('Agregar Slide', 'ygb-slider-2'); ?>
                </button>
                <input type="submit" name="guardar_slides" class="button button-primary" value="<?php esc_attr_e('Guardar Slides', 'ygb-slider-2'); ?>" style="margin-left:10px;">
            </div>
        </form>
    </div>
    
    <style>
    .slide_box { background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; margin-bottom: 12px; overflow: hidden; transition: box-shadow 0.15s ease, border-color 0.15s ease, opacity 0.15s ease; }
    .slide_box:hover { box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
    .slide_box.ygb-open { box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-color: #2271b1; }
    .slide_box.ygb-inactivo { opacity: 0.6; }
    .slide_box.ygb-inactivo:hover, .slide_box.ygb-inactivo.ygb-open { opacity: 1; }
    .slide_box.ygb-programado-inactivo { border-left: 4px solid #dba617; }
    .slide_box.ygb-programado-vigente { border-left: 4px solid #10b981; }
    .slide_box.ygb-sorting { box-shadow: 0 8px 24px rgba(0,0,0,0.18); opacity: 0.9; border-color: #2271b1; transform: scale(1.01); }
    .slide_placeholder { background: #f0f6fc; border: 2px dashed #2271b1; border-radius: 8px; margin-bottom: 12px; visibility: visible !important; }
    .slide_header { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 14px; cursor: grab; user-select: none; transition: background 0.15s; outline: none; }
    .slide_box.ygb-sorting .slide_header { cursor: grabbing; }
    .slide_header:hover { background: #f6f7f7; }
    .slide_header:focus-visible { box-shadow: inset 0 0 0 2px #2271b1; }
    .slide_box.ygb-open .slide_header { background: #f6f7f7; border-bottom: 1px solid #f0f0f1; }
    .slide_header_left { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; }
    .slide_drag_handle { color: #c3c4c7; font-size: 18px; width: 18px; height: 18px; line-height: 1; flex-shrink: 0; transition: color 0.15s; }
    .slide_header:hover .slide_drag_handle { color: #50575e; }
    .slide_box.ygb-sorting .slide_drag_handle { color: #2271b1; }
    .slide_numero { background: #2271b1; color: #fff; width: 26px; height: 26px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; flex-shrink: 0; }
    .slide_box.ygb-inactivo .slide_numero { background: #8c8f94; }
    .slide_mini_preview { width: 40px; height: 40px; background: #f0f0f1; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; color: #8c8f94; }
    .slide_mini_preview img { width: 100%; height: 100%; object-fit: cover; display: block; pointer-events: none; }
    .slide_mini_preview .dashicons { font-size: 20px; width: 20px; height: 20px; }
    .slide_nombre { font-weight: 600; color: #1d2327; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0; flex: 1; }
    .slide_nombre.ygb-vacio { color: #8c8f94; font-weight: 400; font-style: italic; }
    .slide_estado_imagen { font-size: 11px; padding: 2px 8px; border-radius: 10px; background: #fcf3d5; color: #8a6d3b; flex-shrink: 0; white-space: nowrap; }
    .slide_estado_imagen.ygb-tiene-img { background: #d5f0d8; color: #2c6e2f; }
    .slide_estado_programado { font-size: 11px; padding: 2px 8px; border-radius: 10px; flex-shrink: 0; white-space: nowrap; font-weight: 500; }
    .slide_estado_programado.ygb-vigente { background: #d5f0d8; color: #2c6e2f; }
    .slide_estado_programado.ygb-fuera { background: #fcf3d5; color: #8a6d3b; }
    .slide_header_right { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
    .ygb-toggle { position: relative; display: inline-block; width: 36px; height: 20px; cursor: pointer; vertical-align: middle; flex-shrink: 0; margin: 0; }
    .ygb-toggle input { opacity: 0; width: 0; height: 0; position: absolute; }
    .ygb-toggle-slider { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-color: #c3c4c7; border-radius: 20px; transition: background-color 0.2s; }
    .ygb-toggle-slider::before { content: ''; position: absolute; height: 16px; width: 16px; left: 2px; top: 2px; background-color: #fff; border-radius: 50%; transition: transform 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.15); }
    .ygb-toggle input:checked + .ygb-toggle-slider { background-color: #2271b1; }
    .ygb-toggle input:checked + .ygb-toggle-slider::before { transform: translateX(16px); }
    .duplicar_slide { background: #2271b1 !important; color: #fff !important; border-color: #2271b1 !important; }
    .duplicar_slide:hover { background: #135e96 !important; border-color: #135e96 !important; }
    .slide_chevron { font-size: 16px; color: #50575e; transition: transform 0.2s ease; display: inline-block; line-height: 1; width: 16px; text-align: center; }
    .slide_box.ygb-open .slide_chevron { transform: rotate(90deg); }
    .slide_body { display: none; padding: 16px 14px 14px; }
    .slide_box.ygb-open .slide_body { display: block; }
    @media (max-width: 900px) {
        .slide_estado_imagen, .slide_estado_programado { display: none; }
        .slide_nombre { font-size: 13px; }
        .duplicar_slide, .eliminar_slide { padding: 4px 8px !important; font-size: 12px !important; }
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        var maxSlides = parseInt($('#agregar_slide').data('max'), 10) || 50;
        var wasSorting = false;
        
        if ($.fn.sortable) {
            $('#lista_slides').sortable({
                handle: '.slide_header',
                cancel: '.eliminar_slide, .duplicar_slide, .ygb-toggle, input, textarea, select, button',
                axis: 'y',
                cursor: 'grabbing',
                opacity: 0.85,
                tolerance: 'pointer',
                placeholder: 'slide_placeholder',
                forcePlaceholderSize: true,
                start: function(e, ui) {
                    wasSorting = true;
                    ui.placeholder.height(ui.item.outerHeight());
                    ui.item.addClass('ygb-sorting');
                },
                change: function(e, ui) { ui.placeholder.height(ui.item.outerHeight()); },
                stop: function(e, ui) {
                    ui.item.removeClass('ygb-sorting');
                    renumerarSlides();
                    setTimeout(function() { wasSorting = false; }, 60);
                }
            });
        }
        
        $(document).on('click', '.ygb-toggle', function(e) { e.stopPropagation(); });
        $(document).on('change', '.ygb-toggle input', function() {
            var $toggle = $(this);
            var $box = $toggle.closest('.slide_box');
            var val = $toggle.is(':checked') ? '1' : '0';
            $box.find('.activo_hidden').val(val);
            $box.toggleClass('ygb-inactivo', val === '0');
        });
        
        $(document).on('click', '.slide_header', function(e) {
            if (wasSorting) return;
            if ($(e.target).closest('.eliminar_slide, .duplicar_slide, .ygb-toggle').length) return;
            var $box = $(this).closest('.slide_box');
            $box.toggleClass('ygb-open');
            $(this).attr('aria-expanded', $box.hasClass('ygb-open') ? 'true' : 'false');
        });
        
        $(document).on('keydown', '.slide_header', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                if ($(e.target).closest('.eliminar_slide, .duplicar_slide, .ygb-toggle').length) return;
                e.preventDefault();
                $(this).trigger('click');
            }
        });
        
        $(document).on('input', '.slide_box input[name="titulo[]"]', function() {
            var val = $(this).val().trim();
            var $nombre = $(this).closest('.slide_box').find('.slide_nombre');
            if (val) {
                $nombre.text(val).removeClass('ygb-vacio');
            } else {
                $nombre.text('<?php echo esc_js(esc_html__('Sin título', 'ygb-slider-2')); ?>').addClass('ygb-vacio');
            }
        });
        
        $(document).on('change', '.fecha_inicio_input, .fecha_fin_input', function() {
            var $box = $(this).closest('.slide_box');
            var fi = $box.find('.fecha_inicio_input').val();
            var ff = $box.find('.fecha_fin_input').val();
            
            $box.removeClass('ygb-programado-vigente ygb-programado-inactivo');
            
            if (fi || ff) {
                var hoy = new Date();
                var hoyStr = hoy.getFullYear() + '-' + 
                             String(hoy.getMonth() + 1).padStart(2, '0') + '-' + 
                             String(hoy.getDate()).padStart(2, '0');
                
                var vigente = true;
                if (fi && hoyStr < fi) vigente = false;
                if (ff && hoyStr > ff) vigente = false;
                
                $box.addClass(vigente ? 'ygb-programado-vigente' : 'ygb-programado-inactivo');
            }
        });
        
        function renumerarSlides() {
            $('.slide_box').each(function(i) {
                $(this).find('.slide_numero').text(i + 1);
                $(this).attr('data-index', i);
            });
        }
        
        $(document).on('click', '.subir_img', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var $btn = $(this);
            var $input = $btn.siblings('.img_url');
            var $preview = $btn.closest('div').find('.vista_previa');
            var $box = $btn.closest('.slide_box');
            
            var frame = wp.media({
                title: '<?php echo esc_js(__('Seleccionar Imagen', 'ygb-slider-2')); ?>',
                multiple: false,
                library: { type: 'image' }
            });
            
            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                var imgUrl = attachment.url;
                
                if (imgUrl && (imgUrl.indexOf('http://') === 0 || imgUrl.indexOf('https://') === 0)) {
                    $input.val(imgUrl);
                    var $imgGrande = $('<img>').attr('src', imgUrl).attr('alt', '').css({ 'max-width': '200px', 'max-height': '150px', 'object-fit': 'cover' });
                    if ($preview.length) {
                        $preview.empty().append($imgGrande).show();
                    } else {
                        $btn.closest('div').append($('<div class="vista_previa">').css('margin-top', '10px').append($imgGrande));
                    }
                    $box.find('.slide_mini_preview').empty().append($('<img>').attr('src', imgUrl).attr('alt', ''));
                    $box.find('.slide_estado_imagen').text('<?php echo esc_js(esc_html__('Con imagen', 'ygb-slider-2')); ?>').addClass('ygb-tiene-img');
                } else {
                    alert('<?php echo esc_js(__('Solo se permiten imágenes http/https', 'ygb-slider-2')); ?>');
                }
            });
            
            frame.open();
        });
        
        $('#agregar_slide').click(function() {
            var totalActual = $('.slide_box').length;
            
            if (totalActual >= maxSlides) {
                alert('<?php echo esc_js(__('Máximo', 'ygb-slider-2')); ?> ' + maxSlides + ' <?php echo esc_js(__('slides permitidos', 'ygb-slider-2')); ?>');
                return;
            }
            
            var $nuevo = $('.slide_box:first').clone();
            var nuevoIndex = totalActual + 1;
            
            $nuevo.find('.img_url').val('');
            $nuevo.find('input[type="text"]').val('');
            $nuevo.find('textarea').val('');
            $nuevo.find('input[type="url"]').val('');
            $nuevo.find('input[type="date"]').val('');
            $nuevo.find('select').val('_self');
            $nuevo.find('.vista_previa').remove();
            $nuevo.removeClass('ygb-open ygb-sorting ygb-programado-vigente ygb-programado-inactivo');
            $nuevo.find('.slide_header').attr('aria-expanded', 'false');
            $nuevo.addClass('ygb-inactivo');
            $nuevo.find('.activo_hidden').val('0');
            $nuevo.find('.ygb-toggle input').prop('checked', false);
            $nuevo.find('.slide_numero').text(nuevoIndex);
            $nuevo.find('.slide_nombre').text('<?php echo esc_js(esc_html__('Sin título', 'ygb-slider-2')); ?>').addClass('ygb-vacio');
            $nuevo.find('.slide_mini_preview').html('<span class="dashicons dashicons-format-image"></span>');
            $nuevo.find('.slide_estado_imagen').text('<?php echo esc_js(esc_html__('Sin imagen', 'ygb-slider-2')); ?>').removeClass('ygb-tiene-img');
            $nuevo.find('.slide_estado_programado').remove();
            $nuevo.attr('data-index', totalActual);
            
            $('#lista_slides').append($nuevo);
            $('html, body').animate({ scrollTop: $nuevo.offset().top - 80 }, 250);
        });
        
        $(document).on('click', '.duplicar_slide', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var maxSlidesDup = parseInt($('#agregar_slide').data('max'), 10) || 50;
            var total = $('.slide_box').length;
            
            if (total >= maxSlidesDup) {
                alert('<?php echo esc_js(__('Máximo', 'ygb-slider-2')); ?> ' + maxSlidesDup + ' <?php echo esc_js(__('slides permitidos', 'ygb-slider-2')); ?>');
                return;
            }
            
            var $original = $(this).closest('.slide_box');
            var $nuevo = $original.clone();
            
            $nuevo.addClass('ygb-inactivo');
            $nuevo.find('.activo_hidden').val('0');
            $nuevo.find('.ygb-toggle input').prop('checked', false);
            $nuevo.removeClass('ygb-open ygb-sorting');
            $nuevo.find('.slide_header').attr('aria-expanded', 'false');
            
            $original.after($nuevo);
            renumerarSlides();
            
            $('html, body').animate({ scrollTop: $nuevo.offset().top - 80 }, 250);
        });
        
        $(document).on('click', '.eliminar_slide', function(e) {
            e.stopPropagation();
            
            if ($('.slide_box').length > 1) {
                if (confirm('<?php echo esc_js(__('¿Eliminar este slide?', 'ygb-slider-2')); ?>')) {
                    $(this).closest('.slide_box').remove();
                    renumerarSlides();
                }
            } else {
                alert('<?php echo esc_js(__('Debe haber al menos un slide', 'ygb-slider-2')); ?>');
            }
        });
    });
    </script>
    <?php
}

// ==================== CONFIGURACIÓN ====================
function ygb_slider2_config() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('No tienes permisos.', 'ygb-slider-2'));
    }
    
    if (isset($_POST['guardar_config'])) {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'accion_config2')) {
            wp_die(esc_html__('Verificación de seguridad fallida.', 'ygb-slider-2'));
        }
        
        $velocidad = isset($_POST['velocidad']) ? absint(wp_unslash($_POST['velocidad'])) : YGB_SLIDER2_DEFAULT_VELOCIDAD;
        $velocidad = max(YGB_SLIDER2_MIN_VELOCIDAD, min(YGB_SLIDER2_MAX_VELOCIDAD, $velocidad));
        update_option('ygb_slider2_velocidad', $velocidad);
        
        $autoplay = isset($_POST['autoplay']) ? 1 : 0;
        update_option('ygb_slider2_autoplay', $autoplay);
        
        $color = isset($_POST['color']) ? sanitize_hex_color(wp_unslash($_POST['color'])) : '#ff6b6b';
        if (empty($color)) $color = '#ff6b6b';
        update_option('ygb_slider2_color', $color);
        
        $transicion_raw = isset($_POST['transicion']) ? wp_unslash($_POST['transicion']) : YGB_SLIDER2_DEFAULT_TRANSICION;
        update_option('ygb_slider2_transicion', ygb_slider2_normalizar_transicion($transicion_raw));
        
        add_settings_error('ygb_slider2', 'config_guardada', esc_html__('Configuración guardada correctamente.', 'ygb-slider-2'), 'success');
    }
    
    $vel = absint(get_option('ygb_slider2_velocidad', YGB_SLIDER2_DEFAULT_VELOCIDAD));
    $auto = absint(get_option('ygb_slider2_autoplay', 1));
    $color = sanitize_hex_color(get_option('ygb_slider2_color', '#ff6b6b'));
    
    $transicion = ygb_slider2_normalizar_transicion(get_option('ygb_slider2_transicion', YGB_SLIDER2_DEFAULT_TRANSICION));
    $labels = ygb_slider2_transiciones_labels();
    
    settings_errors('ygb_slider2');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <form method="post" action="" style="background:white; padding:20px; border-radius:10px; max-width:600px">
            <?php wp_nonce_field('accion_config2'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="velocidad"><?php esc_html_e('Velocidad', 'ygb-slider-2'); ?></label></th>
                    <td>
                        <input type="number" id="velocidad" name="velocidad" value="<?php echo absint($vel); ?>" min="<?php echo absint(YGB_SLIDER2_MIN_VELOCIDAD); ?>" max="<?php echo absint(YGB_SLIDER2_MAX_VELOCIDAD); ?>" step="100" class="small-text">
                        <p class="description"><?php esc_html_e('Milisegundos (1000ms = 1s)', 'ygb-slider-2'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Autoplay', 'ygb-slider-2'); ?></th>
                    <td><label><input type="checkbox" name="autoplay" value="1" <?php checked($auto, 1); ?>> <?php esc_html_e('Activar autoplay', 'ygb-slider-2'); ?></label></td>
                </tr>
                <tr>
                    <th scope="row"><label for="color"><?php esc_html_e('Color principal', 'ygb-slider-2'); ?></label></th>
                    <td><input type="color" id="color" name="color" value="<?php echo esc_attr($color); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="transicion"><?php esc_html_e('Efecto de transición', 'ygb-slider-2'); ?></label></th>
                    <td>
                        <select id="transicion" name="transicion" style="min-width:260px">
                            <?php foreach ($labels as $valor => $etiqueta) : ?>
                                <option value="<?php echo esc_attr($valor); ?>" <?php selected($transicion, $valor); ?>>
                                    <?php echo esc_html($etiqueta); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e('Efecto global. Se puede sobreescribir con el atributo "transicion" del shortcode o con "grupos" para modo mixto.', 'ygb-slider-2'); ?></p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(esc_html__('Guardar Configuración', 'ygb-slider-2'), 'primary', 'guardar_config'); ?>
        </form>
    </div>
    <?php
}

// ==================== SHORTCODE ====================
function ygb_slider2_shortcode($atts) {
    $atts = shortcode_atts(array(
        'velocidad'   => get_option('ygb_slider2_velocidad', YGB_SLIDER2_DEFAULT_VELOCIDAD),
        'autoplay'    => get_option('ygb_slider2_autoplay', 1) ? 'si' : 'no',
        'admin_panel' => 'no',
        'transicion'  => get_option('ygb_slider2_transicion', YGB_SLIDER2_DEFAULT_TRANSICION),
        'slides'      => '',
        'grupos'      => ''
    ), $atts, 'ygb_slider2');
    
    wp_enqueue_style('ygb-slider-2');
    wp_enqueue_script('ygb-slider-2');
    
    $velocidad = absint($atts['velocidad']);
    $velocidad = max(YGB_SLIDER2_MIN_VELOCIDAD, min(YGB_SLIDER2_MAX_VELOCIDAD, $velocidad));
    
    $autoplay = in_array(strtolower($atts['autoplay']), array('si', 'true', '1', 'yes'), true);
    
    $mostrar_panel = false;
    if (defined('WP_DEBUG') && WP_DEBUG && strtolower($atts['admin_panel']) === 'yes' && current_user_can('manage_options')) {
        $mostrar_panel = true;
    }
    
    $color = sanitize_hex_color(get_option('ygb_slider2_color', '#ff6b6b'));
    $slides = get_option('ygb_slider2_slides', array());
    
    if (empty($slides) || !is_array($slides)) {
        return '<p style="padding:20px; text-align:center; background:#f8d7da; border-radius:5px;">' . 
               esc_html__('⚠️ No hay slides configuradas.', 'ygb-slider-2') . '</p>';
    }
    
    $grupos_raw = isset($atts['grupos']) ? trim((string) $atts['grupos']) : '';
    $modo_mixto = ($grupos_raw !== '');
    
    $slides_activos_por_indice = array();
    foreach ($slides as $idx => $s) {
        if (empty($s['img']) || !ygb_slider2_validar_img_url($s['img'])) continue;
        
        $activo = isset($s['activo']) ? (int) $s['activo'] : 1;
        if ($activo === 0) continue;
        
        if (!ygb_slider2_slide_en_ventana($s)) continue;
        
        $slides_activos_por_indice[$idx] = $s;
    }
    
    $transicion = ygb_slider2_normalizar_transicion($atts['transicion']);
    
    if ($modo_mixto) {
        $parseo = ygb_slider2_parsear_grupos($grupos_raw, count($slides));
        $grupos = $parseo['grupos'];
        $avisos_mixto = $parseo['avisos'];
        
        if (empty($grupos)) {
            return '<p style="padding:20px; text-align:center; background:#fff3cd; border-radius:5px;">' . 
                   esc_html__('⚠️ El atributo "grupos" no contiene ningún grupo válido.', 'ygb-slider-2') . '</p>';
        }
        
        $items_mixto = array();
        foreach ($grupos as $grupo) {
            foreach ($grupo['indices'] as $idx) {
                if (!isset($slides_activos_por_indice[$idx])) continue;
                $items_mixto[] = array(
                    'slide'     => $slides_activos_por_indice[$idx],
                    'efecto'    => $grupo['efecto'],
                    'velocidad' => $grupo['velocidad']
                );
            }
        }
        
        if (empty($items_mixto)) {
            return '<p style="padding:20px; text-align:center; background:#fff3cd; border-radius:5px;">' . 
                   esc_html__('⚠️ Los slides indicados en "grupos" existen pero están desactivados, sin imagen válida o fuera de su ventana de fecha.', 'ygb-slider-2') . '</p>';
        }
        
        static $slider_counter_mix = 0;
        $slider_counter_mix++;
        $instance_id = $slider_counter_mix;
        
        $html = '<div class="ygb-slider-wrapper" data-instance="' . absint($instance_id) . '">';
        
        if (!empty($avisos_mixto) && current_user_can('manage_options') && defined('WP_DEBUG') && WP_DEBUG) {
            $html .= '<div style="background:#fff3cd; border-left:4px solid #dba617; padding:10px 14px; border-radius:4px; margin-bottom:10px; font-size:13px; color:#5a4000">';
            $html .= '<strong>⚠️ ' . esc_html__('Modo mixto - Avisos del shortcode:', 'ygb-slider-2') . '</strong>';
            $html .= '<ul style="margin:6px 0 0 20px; padding:0; list-style:disc">';
            foreach ($avisos_mixto as $aviso) $html .= '<li>' . esc_html($aviso) . '</li>';
            $html .= '</ul></div>';
        }
        
        if ($mostrar_panel) {
            $html .= '<div class="ygb-panel">';
            $html .= '<span>⚡ ' . esc_html__('Velocidad global:', 'ygb-slider-2') . '</span>';
            $html .= '<input type="range" class="ygb-rango" min="' . absint(YGB_SLIDER2_MIN_VELOCIDAD) . '" max="' . absint(YGB_SLIDER2_MAX_VELOCIDAD) . '" step="100" value="' . absint($velocidad) . '" data-slider-instance="' . absint($instance_id) . '">';
            $html .= '<span class="ygb-valor" data-slider-instance="' . absint($instance_id) . '">' . esc_html($velocidad / 1000) . 's</span>';
            $html .= '</div>';
        }
        
        $html .= '<div class="ygb-slider ygb-modo-mixto" data-modo="mixto" data-vel="' . absint($velocidad) . '" data-auto="' . ($autoplay ? '1' : '0') . '" data-instance="' . absint($instance_id) . '" data-color="' . esc_attr($color) . '">';
        $html .= '<div class="ygb-contenedor"><div class="ygb-pista">';
        
        foreach ($items_mixto as $index => $item) {
            $s = $item['slide'];
            $efecto = $item['efecto'];
            
            $clase = 'ygb-item' . ($index === 0 ? ' ygb-actual' : '');
            $attr_velocidad = $item['velocidad'] !== null ? ' data-item-vel="' . absint($item['velocidad']) . '"' : '';
            
            $html .= '<div class="' . esc_attr($clase) . '" data-slide="' . absint($index) . '" data-efecto="' . esc_attr($efecto) . '"' . $attr_velocidad . '>';
            
            $img_src = esc_url($s['img']);
            $img_alt = !empty($s['titulo']) ? esc_attr($s['titulo']) : esc_attr__('Slide', 'ygb-slider-2');
            
            $img_tag = '<img src="' . $img_src . '" alt="' . $img_alt . '" style="border-radius:0 !important;" loading="lazy" decoding="async" draggable="false">';
            
            if (!empty($s['url'])) {
                $url_validada = ygb_slider2_validar_url_enlace($s['url']);
                if (!empty($url_validada)) {
                    $target_attr = ($s['target'] === '_blank') ? ' target="_blank" rel="noopener noreferrer"' : '';
                    $img_tag = '<a href="' . esc_url($url_validada) . '"' . $target_attr . ' class="ygb-slide-link" style="border-radius:0 !important;" draggable="false">' . $img_tag . '</a>';
                }
            }
            
            $html .= $img_tag;
            
            if (!empty($s['titulo']) || !empty($s['texto'])) {
                $html .= '<div class="ygb-texto-overlay">';
                if (!empty($s['titulo'])) $html .= '<h3>' . esc_html($s['titulo']) . '</h3>';
                if (!empty($s['texto'])) $html .= '<div class="ygb-descripcion">' . wp_kses_post($s['texto']) . '</div>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div><div class="ygb-dots" data-slider-instance="' . absint($instance_id) . '"></div></div></div></div>';
        
        return $html;
    }
    
    $filtro_slides_presente = (isset($atts['slides']) && trim((string) $atts['slides']) !== '');
    
    if ($filtro_slides_presente) {
        $seleccion = ygb_slider2_parsear_lista_slides($atts['slides'], count($slides));
        
        if (empty($seleccion)) {
            return '<p style="padding:20px; text-align:center; background:#fff3cd; border-radius:5px;">' . 
                   esc_html__('⚠️ El atributo "slides" no contiene números válidos.', 'ygb-slider-2') . '</p>';
        }
        
        $slides_activos = array();
        foreach ($seleccion as $idx) {
            if (isset($slides_activos_por_indice[$idx])) $slides_activos[] = $slides_activos_por_indice[$idx];
        }
        
        if (empty($slides_activos)) {
            return '<p style="padding:20px; text-align:center; background:#fff3cd; border-radius:5px;">' . 
                   esc_html__('⚠️ Los slides indicados existen pero están desactivados, sin imagen válida o fuera de su ventana de fecha.', 'ygb-slider-2') . '</p>';
        }
    } else {
        $slides_activos = array_values($slides_activos_por_indice);
        
        if (empty($slides_activos)) {
            return '<p style="padding:20px; text-align:center; background:#fff3cd; border-radius:5px;">' . 
                   esc_html__('⚠️ No hay slides activos con imagen configurada y vigentes hoy.', 'ygb-slider-2') . '</p>';
        }
    }
    
    static $slider_counter = 0;
    $slider_counter++;
    $instance_id = $slider_counter;
    
    $slider_classes = 'ygb-slider ygb-trans-' . $transicion;
    
    $html = '<div class="ygb-slider-wrapper" data-instance="' . absint($instance_id) . '">';
    
    if ($mostrar_panel) {
        $html .= '<div class="ygb-panel">';
        $html .= '<span>⚡ ' . esc_html__('Velocidad:', 'ygb-slider-2') . '</span>';
        $html .= '<input type="range" class="ygb-rango" min="' . absint(YGB_SLIDER2_MIN_VELOCIDAD) . '" max="' . absint(YGB_SLIDER2_MAX_VELOCIDAD) . '" step="100" value="' . absint($velocidad) . '" data-slider-instance="' . absint($instance_id) . '">';
        $html .= '<span class="ygb-valor" data-slider-instance="' . absint($instance_id) . '">' . esc_html($velocidad / 1000) . 's</span>';
        foreach (array(2000, 4000, 6000, 8000) as $v) {
            $html .= '<button class="ygb-pre" data-vel="' . absint($v) . '" data-slider-instance="' . absint($instance_id) . '">' . esc_html($v / 1000) . 's</button>';
        }
        $html .= '</div>';
    }
    
    $html .= '<div class="' . esc_attr($slider_classes) . '" data-trans="' . esc_attr($transicion) . '" data-vel="' . absint($velocidad) . '" data-auto="' . ($autoplay ? '1' : '0') . '" data-instance="' . absint($instance_id) . '" data-color="' . esc_attr($color) . '">';
    $html .= '<div class="ygb-contenedor"><div class="ygb-pista">';
    
    foreach ($slides_activos as $index => $s) {
        $clase = 'ygb-item' . ($index === 0 ? ' ygb-actual' : '');
        $html .= '<div class="' . esc_attr($clase) . '" data-slide="' . absint($index) . '">';
        
        $img_src = esc_url($s['img']);
        $img_alt = !empty($s['titulo']) ? esc_attr($s['titulo']) : esc_attr__('Slide', 'ygb-slider-2');
        
        $img_tag = '<img src="' . $img_src . '" alt="' . $img_alt . '" style="border-radius:0 !important;" loading="lazy" decoding="async" draggable="false">';
        
        if (!empty($s['url'])) {
            $url_validada = ygb_slider2_validar_url_enlace($s['url']);
            if (!empty($url_validada)) {
                $target_attr = ($s['target'] === '_blank') ? ' target="_blank" rel="noopener noreferrer"' : '';
                $img_tag = '<a href="' . esc_url($url_validada) . '"' . $target_attr . ' class="ygb-slide-link" style="border-radius:0 !important;" draggable="false">' . $img_tag . '</a>';
            }
        }
        
        $html .= $img_tag;
        
        if (!empty($s['titulo']) || !empty($s['texto'])) {
            $html .= '<div class="ygb-texto-overlay">';
            if (!empty($s['titulo'])) $html .= '<h3>' . esc_html($s['titulo']) . '</h3>';
            if (!empty($s['texto'])) $html .= '<div class="ygb-descripcion">' . wp_kses_post($s['texto']) . '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
    }
    
    $html .= '</div><div class="ygb-dots" data-slider-instance="' . absint($instance_id) . '"></div></div></div></div>';
    
    return $html;
}
add_shortcode('ygb_slider2', 'ygb_slider2_shortcode');

// ==================== ELEMENTOR ====================
function ygb_slider2_register_elementor_widget($widgets_manager) {
    if (!class_exists('Elementor\Widget_Base')) return;
    
    class YGB_Slider2_Widget extends \Elementor\Widget_Base {
        public function get_name() { return 'ygb_slider2'; }
        public function get_title() { return esc_html__('YGB Slider 2', 'ygb-slider-2'); }
        public function get_icon() { return 'eicon-slider-push'; }
        public function get_categories() { return array('general'); }
        
        protected function register_controls() {
            $this->start_controls_section('seccion', array('label' => esc_html__('Configuración', 'ygb-slider-2')));
            
            $this->add_control('grupos', array(
                'label' => esc_html__('Modo mixto (grupos)', 'ygb-slider-2'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => '1,3:zoom|2,4:cubo',
                'description' => esc_html__('Varios efectos en el mismo slider. Efectos permitidos: fundido, zoom, cubo.', 'ygb-slider-2'),
                'default' => ''
            ));
            
            $this->add_control('slides', array(
                'label' => esc_html__('Slides a mostrar (clásico)', 'ygb-slider-2'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => '1,7,4,2',
                'description' => esc_html__('Números de slide separados por coma. Acepta rangos (ej: 1-3,7). Las fechas se configuran en el panel admin, no aquí.', 'ygb-slider-2'),
                'default' => ''
            ));
            
            $this->add_control('velocidad', array(
                'label' => esc_html__('Velocidad (ms)', 'ygb-slider-2'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => absint(get_option('ygb_slider2_velocidad', YGB_SLIDER2_DEFAULT_VELOCIDAD)),
                'min' => YGB_SLIDER2_MIN_VELOCIDAD,
                'max' => YGB_SLIDER2_MAX_VELOCIDAD,
                'step' => 100
            ));
            
            $this->add_control('autoplay', array(
                'label' => esc_html__('Autoplay', 'ygb-slider-2'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => absint(get_option('ygb_slider2_autoplay', 1)) ? 'yes' : 'no'
            ));
            
            $this->end_controls_section();
        }
        
        protected function render() {
            $settings = $this->get_settings_for_display();
            $velocidad = absint($settings['velocidad']);
            $velocidad = max(YGB_SLIDER2_MIN_VELOCIDAD, min(YGB_SLIDER2_MAX_VELOCIDAD, $velocidad));
            $autoplay = ($settings['autoplay'] === 'yes') ? 'si' : 'no';
            $grupos = isset($settings['grupos']) ? trim((string) $settings['grupos']) : '';
            $slides = isset($settings['slides']) ? trim((string) $settings['slides']) : '';
            
            $atts = sprintf(
                '[ygb_slider2 velocidad="%d" autoplay="%s"',
                $velocidad, esc_attr($autoplay)
            );
            
            if ($grupos !== '') {
                $atts .= ' grupos="' . esc_attr($grupos) . '"';
            } elseif ($slides !== '') {
                $atts .= ' slides="' . esc_attr($slides) . '"';
            }
            
            $atts .= ']';
            
            echo do_shortcode($atts);
        }
    }
    
    $widgets_manager->register(new YGB_Slider2_Widget());
}
add_action('elementor/widgets/register', 'ygb_slider2_register_elementor_widget');

// ==================== DESACTIVACIÓN ====================
register_deactivation_hook(__FILE__, function() {
    // Las opciones se conservan
});