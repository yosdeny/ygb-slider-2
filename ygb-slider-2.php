<?php
/**
 * Plugin Name: YGB Slider 2
 * Description: Slider con texto sobre imagen - Con enlaces en imágenes. Versión hardenizada v3.1
 * Version: 3.1
 * Author: YGB Team
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * 
 * Security: Hardened version v3.1 - XSS protected, CSRF safe, strict validation, capability checks
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==================== CONSTANTES Y CONFIGURACIÓN ====================
define('YGB_SLIDER2_VERSION', '3.1');
define('YGB_SLIDER2_MAX_SLIDES', 50);
define('YGB_SLIDER2_MIN_VELOCIDAD', 1000);
define('YGB_SLIDER2_MAX_VELOCIDAD', 10000);
define('YGB_SLIDER2_DEFAULT_VELOCIDAD', 4000);

// ==================== ACTIVACIÓN ====================
/**
 * Callback de activación del plugin.
 * 
 * @return void
 */
function ygb_slider2_activar() {
    $existentes = get_option('ygb_slider2_slides', false);
    
    if ($existentes === false) {
        $slides = array(
            array(
                'img' => '', 
                'titulo' => esc_html__('Bienvenidos', 'ygb-slider-2'), 
                'texto' => esc_html__('Descubre nuestro contenido', 'ygb-slider-2'), 
                'url' => '', 
                'target' => '_self'
            ),
            array(
                'img' => '', 
                'titulo' => esc_html__('Ofertas Especiales', 'ygb-slider-2'), 
                'texto' => esc_html__('No te las pierdas', 'ygb-slider-2'), 
                'url' => '', 
                'target' => '_self'
            ),
            array(
                'img' => '', 
                'titulo' => esc_html__('Contacto', 'ygb-slider-2'), 
                'texto' => esc_html__('Estamos para ayudarte', 'ygb-slider-2'), 
                'url' => '', 
                'target' => '_self'
            )
        );
        add_option('ygb_slider2_slides', $slides);
    }
    
    if (!get_option('ygb_slider2_velocidad')) {
        add_option('ygb_slider2_velocidad', YGB_SLIDER2_DEFAULT_VELOCIDAD);
    }
    if (!get_option('ygb_slider2_autoplay')) {
        add_option('ygb_slider2_autoplay', 1);
    }
    if (!get_option('ygb_slider2_color')) {
        add_option('ygb_slider2_color', '#ff6b6b');
    }
}
register_activation_hook(__FILE__, 'ygb_slider2_activar');

// ==================== MENÚ ADMIN ====================
/**
 * Registra los menús de administración del plugin.
 * 
 * @return void
 */
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
    
    add_submenu_page(
        'ygb-slider-2',
        esc_html__('Slides', 'ygb-slider-2'),
        esc_html__('Slides', 'ygb-slider-2'),
        'manage_options',
        'ygb-slider-2-slides',
        'ygb_slider2_slides'
    );
    
    add_submenu_page(
        'ygb-slider-2',
        esc_html__('Configurar', 'ygb-slider-2'),
        esc_html__('Configurar', 'ygb-slider-2'),
        'manage_options',
        'ygb-slider-2-config',
        'ygb_slider2_config'
    );
}
add_action('admin_menu', 'ygb_slider2_menu');

/**
 * Renderiza el dashboard del plugin.
 * 
 * @return void
 */
function ygb_slider2_dashboard() {
    // Verificar capacidades
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('No tienes permisos para acceder a esta página.', 'ygb-slider-2'));
    }
    
    $slides = get_option('ygb_slider2_slides', array());
    $vel = absint(get_option('ygb_slider2_velocidad', YGB_SLIDER2_DEFAULT_VELOCIDAD));
    
    // Mostrar mensajes de settings si existen
    settings_errors('ygb_slider2');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div style="display:flex; gap:20px; margin:20px 0">
            <div style="background:white; padding:20px; border-radius:10px; text-align:center; flex:1">
                <div style="font-size:36px; font-weight:bold; color:#2271b1">
                    <?php echo absint(count($slides)); ?>
                </div>
                <div><?php esc_html_e('Slides activas', 'ygb-slider-2'); ?></div>
            </div>
            <div style="background:white; padding:20px; border-radius:10px; text-align:center; flex:1">
                <div style="font-size:36px; font-weight:bold; color:#2271b1">
                    <?php echo esc_html($vel / 1000); ?>s
                </div>
                <div><?php esc_html_e('Velocidad', 'ygb-slider-2'); ?></div>
            </div>
        </div>
        
        <div style="background:white; padding:20px; border-radius:10px">
            <h2><?php esc_html_e('Shortcode', 'ygb-slider-2'); ?></h2>
            <code style="background:#f0f0f0; padding:10px; display:inline-block">[ygb_slider2]</code>
            <p><?php esc_html_e('Las imágenes pueden tener enlaces. El texto se muestra sobre la imagen con fondo semitransparente.', 'ygb-slider-2'); ?></p>
        </div>
    </div>
    <?php
}

// ==================== FUNCIONES AUXILIARES DE VALIDACIÓN ====================

/**
 * Valida una URL de imagen permitiendo solo protocolos http/https y extensiones válidas.
 *
 * @param string $url URL a validar.
 * @return bool True si es válida, false en caso contrario.
 */
function ygb_slider2_validar_img_url($url) {
    if (empty($url)) {
        return false;
    }
    
    $protocolos_permitidos = array('http', 'https');
    $protocolo = parse_url($url, PHP_URL_SCHEME);
    
    if (!in_array($protocolo, $protocolos_permitidos, true)) {
        return false;
    }
    
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    
    $extensiones_permitidas = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg');
    $path = parse_url($url, PHP_URL_PATH);
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    
    // Permitir URLs sin extensión (algunas CDN usan parámetros)
    if (!empty($extension) && !in_array($extension, $extensiones_permitidas, true)) {
        return false;
    }
    
    return true;
}

/**
 * Valida y sanitiza una URL de enlace permitiendo solo http/https.
 *
 * @param string $url URL a validar.
 * @return string URL sanitizada o cadena vacía si es inválida.
 */
function ygb_slider2_validar_url_enlace($url) {
    if (empty($url)) {
        return '';
    }
    
    // Aplicar esc_url_raw primero
    $url = esc_url_raw($url);
    $protocolo = parse_url($url, PHP_URL_SCHEME);
    
    // Solo permitir http/https explícitos
    if (!in_array($protocolo, array('http', 'https'), true)) {
        return '';
    }
    
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return '';
    }
    
    return $url;
}

// ==================== GESTIÓN DE SLIDES ====================

/**
 * Renderiza y procesa la página de gestión de slides.
 * 
 * @return void
 */
function ygb_slider2_slides() {
    // Verificar capacidades
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('No tienes permisos para acceder a esta página.', 'ygb-slider-2'));
    }
    
    $errores = array();
    $exitos = array();
    
    // Procesar guardado de slides
    if (isset($_POST['guardar_slides'])) {
        // Verificar nonce explícitamente
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'accion_slides2')) {
            wp_die(esc_html__('Verificación de seguridad fallida. Por favor, recarga la página.', 'ygb-slider-2'));
        }
        
        // Aplicar wp_unslash a todos los arrays POST
        $imagenes_raw = isset($_POST['img']) ? wp_unslash($_POST['img']) : array();
        $titulos_raw = isset($_POST['titulo']) ? wp_unslash($_POST['titulo']) : array();
        $textos_raw = isset($_POST['texto']) ? wp_unslash($_POST['texto']) : array();
        $urls_raw = isset($_POST['url']) ? wp_unslash($_POST['url']) : array();
        $targets_raw = isset($_POST['target']) ? wp_unslash($_POST['target']) : array();
        
        // Validar que sean arrays
        if (!is_array($imagenes_raw)) {
            $imagenes_raw = array();
        }
        
        // Limitar cantidad máxima
        $imagenes_raw = array_slice($imagenes_raw, 0, YGB_SLIDER2_MAX_SLIDES);
        
        $slides = array();
        $slides_procesados = 0;
        
        for ($i = 0; $i < count($imagenes_raw); $i++) {
            $img_raw = isset($imagenes_raw[$i]) ? $imagenes_raw[$i] : '';
            
            if (empty($img_raw)) {
                continue;
            }
            
            // Validar URL de imagen
            if (!ygb_slider2_validar_img_url($img_raw)) {
                $errores[] = sprintf(
                    /* translators: %d: Número de slide */
                    esc_html__('Slide %d: URL de imagen inválida o protocolo no permitido.', 'ygb-slider-2'),
                    $i + 1
                );
                continue;
            }
            
            // Sanitizar campos
            $img_url = esc_url_raw($img_raw);
            $titulo = isset($titulos_raw[$i]) ? sanitize_text_field($titulos_raw[$i]) : '';
            
            // Sanitizar texto con HTML limitado
            $allowed_html = array(
                'strong' => array(),
                'em' => array(),
                'b' => array(),
                'i' => array(),
                'br' => array(),
                'a' => array(
                    'href' => array(),
                    'title' => array(),
                    'target' => array()
                )
            );
            $texto = isset($textos_raw[$i]) ? wp_kses($textos_raw[$i], $allowed_html) : '';
            
            // Validar y sanitizar URL de enlace
            $url_raw = isset($urls_raw[$i]) ? $urls_raw[$i] : '';
            $url = ygb_slider2_validar_url_enlace($url_raw);
            
            if (!empty($url_raw) && empty($url)) {
                $errores[] = sprintf(
                    /* translators: %d: Número de slide */
                    esc_html__('Slide %d: URL de enlace inválida (solo http/https permitido).', 'ygb-slider-2'),
                    $i + 1
                );
            }
            
            // Validar target
            $target_raw = isset($targets_raw[$i]) ? sanitize_key($targets_raw[$i]) : '_self';
            $target = ($target_raw === '_blank') ? '_blank' : '_self';
            
            $slides[] = array(
                'img' => $img_url,
                'titulo' => $titulo,
                'texto' => $texto,
                'url' => $url,
                'target' => $target
            );
            
            $slides_procesados++;
        }
        
        // Guardar o mostrar error
        if (!empty($slides)) {
            update_option('ygb_slider2_slides', $slides);
            $exitos[] = sprintf(
                /* translators: %d: Cantidad de slides guardadas */
                esc_html__('Slides guardadas correctamente (%d slides activas).', 'ygb-slider-2'),
                count($slides)
            );
        } else {
            $errores[] = esc_html__('Error: Debes tener al menos un slide válido con imagen.', 'ygb-slider-2');
        }
        
        // Mostrar notificaciones
        if (!empty($exitos)) {
            foreach ($exitos as $exito) {
                add_settings_error('ygb_slider2', 'slides_guardadas', $exito, 'success');
            }
        }
        if (!empty($errores)) {
            foreach ($errores as $error) {
                add_settings_error('ygb_slider2', 'slides_error', $error, 'error');
            }
        }
    }
    
    // Obtener slides actuales
    $slides = get_option('ygb_slider2_slides', array());
    if (empty($slides)) {
        $slides = array(array('img' => '', 'titulo' => '', 'texto' => '', 'url' => '', 'target' => '_self'));
    }
    
    settings_errors('ygb_slider2');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <p class="description">
            <?php 
            printf(
                /* translators: %d: Número máximo de slides */
                esc_html__('Máximo %d slides permitidos.', 'ygb-slider-2'),
                absint(YGB_SLIDER2_MAX_SLIDES)
            ); 
            ?>
        </p>
        
        <form method="post" action="">
            <?php wp_nonce_field('accion_slides2'); ?>
            
            <div id="lista_slides">
                <?php foreach ($slides as $i => $s) : 
                    $slide_numero = $i + 1;
                ?>
                <div class="slide_box" style="background:white; border:1px solid #ddd; border-radius:10px; padding:20px; margin-bottom:20px" data-index="<?php echo absint($i); ?>">
                    <div style="display:flex; justify-content:space-between; margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px">
                        <h3 style="margin:0">
                            <?php 
                            printf(
                                /* translators: %d: Número de slide */
                                esc_html__('Slide %d', 'ygb-slider-2'),
                                absint($slide_numero)
                            ); 
                            ?>
                        </h3>
                        <button type="button" class="button eliminar_slide" style="background:#dc3232; color:white; border-color:#dc3232">
                            <?php esc_html_e('Eliminar', 'ygb-slider-2'); ?>
                        </button>
                    </div>
                    
                    <div style="margin-bottom:15px">
                        <label style="font-weight:bold; display:block; margin-bottom:5px">
                            <?php esc_html_e('Imagen:', 'ygb-slider-2'); ?>
                        </label>
                        <div style="display:flex; gap:10px">
                            <input type="text" 
                                   name="img[]" 
                                   class="img_url" 
                                   value="<?php echo esc_attr($s['img']); ?>" 
                                   style="flex:1"
                                   placeholder="https://">
                            <button type="button" class="button subir_img">
                                <?php esc_html_e('Seleccionar', 'ygb-slider-2'); ?>
                            </button>
                        </div>
                        <?php if (!empty($s['img'])) : ?>
                        <div class="vista_previa" style="margin-top:10px">
                            <img src="<?php echo esc_url($s['img']); ?>" 
                                 style="max-width:200px; border-radius:5px; max-height:150px; object-fit:cover;" 
                                 alt="<?php echo esc_attr($s['titulo']); ?>">
                        </div>
                        <?php else: ?>
                        <div class="vista_previa" style="margin-top:10px; display:none;"></div>
                        <?php endif; ?>
                    </div>
                    
                    <div style="margin-bottom:15px">
                        <label style="font-weight:bold; display:block; margin-bottom:5px">
                            <?php esc_html_e('Título:', 'ygb-slider-2'); ?>
                        </label>
                        <input type="text" 
                               name="titulo[]" 
                               value="<?php echo esc_attr($s['titulo']); ?>" 
                               style="width:100%"
                               maxlength="200">
                    </div>
                    
                    <div style="margin-bottom:15px">
                        <label style="font-weight:bold; display:block; margin-bottom:5px">
                            <?php esc_html_e('Descripción:', 'ygb-slider-2'); ?>
                        </label>
                        <textarea name="texto[]" 
                                  rows="3" 
                                  style="width:100%"
                                  maxlength="500"><?php echo esc_textarea($s['texto']); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('HTML permitido:', 'ygb-slider-2'); ?> 
                            <code>&lt;strong&gt;, &lt;em&gt;, &lt;a href&gt;, &lt;br&gt;</code>
                        </p>
                    </div>
                    
                    <div style="margin-bottom:15px">
                        <label style="font-weight:bold; display:block; margin-bottom:5px">
                            <?php esc_html_e('URL del enlace (opcional):', 'ygb-slider-2'); ?>
                        </label>
                        <input type="url" 
                               name="url[]" 
                               value="<?php echo esc_attr($s['url']); ?>" 
                               style="width:100%" 
                               placeholder="https://ejemplo.com">
                        <p class="description">
                            <?php esc_html_e('Dejar vacío para que la imagen no sea clickeable. Solo http/https permitidos.', 'ygb-slider-2'); ?>
                        </p>
                    </div>
                    
                    <div>
                        <label style="font-weight:bold; display:block; margin-bottom:5px">
                            <?php esc_html_e('Abrir enlace en:', 'ygb-slider-2'); ?>
                        </label>
                        <select name="target[]" style="width:100%">
                            <option value="_self" <?php selected($s['target'], '_self'); ?>>
                                <?php esc_html_e('Misma ventana', 'ygb-slider-2'); ?>
                            </option>
                            <option value="_blank" <?php selected($s['target'], '_blank'); ?>>
                                <?php esc_html_e('Nueva ventana', 'ygb-slider-2'); ?>
                            </option>
                        </select>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div style="margin:20px 0">
                <button type="button" 
                        id="agregar_slide" 
                        class="button button-secondary" 
                        data-max="<?php echo absint(YGB_SLIDER2_MAX_SLIDES); ?>">
                    + <?php esc_html_e('Agregar Slide', 'ygb-slider-2'); ?>
                </button>
                <input type="submit" 
                       name="guardar_slides" 
                       class="button button-primary" 
                       value="<?php esc_attr_e('Guardar Slides', 'ygb-slider-2'); ?>"
                       style="margin-left:10px;">
            </div>
        </form>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        var maxSlides = parseInt($('#agregar_slide').data('max'), 10) || 50;
        
        // Media uploader
        $(document).on('click', '.subir_img', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $input = $btn.siblings('.img_url');
            var $preview = $btn.closest('div').find('.vista_previa');
            
            var frame = wp.media({
                title: '<?php echo esc_js(__('Seleccionar Imagen', 'ygb-slider-2')); ?>',
                multiple: false,
                library: { type: 'image' }
            });
            
            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                var imgUrl = attachment.url;
                
                // Validar protocolo
                if (imgUrl && (imgUrl.indexOf('http://') === 0 || imgUrl.indexOf('https://') === 0)) {
                    $input.val(imgUrl);
                    
                    var previewHtml = '<img src="' + encodeURI(imgUrl) + '" style="max-width:200px; border-radius:5px; max-height:150px; object-fit:cover;" alt="<?php echo esc_js(__('Preview', 'ygb-slider-2')); ?>">';
                    
                    if ($preview.length) {
                        $preview.html(previewHtml).show();
                    } else {
                        $btn.closest('div').append('<div class="vista_previa" style="margin-top:10px">' + previewHtml + '</div>');
                    }
                } else {
                    alert('<?php echo esc_js(__('Solo se permiten imágenes con protocolo http o https', 'ygb-slider-2')); ?>');
                }
            });
            
            frame.open();
        });
        
        // Agregar slide
        $('#agregar_slide').click(function() {
            var totalActual = $('.slide_box').length;
            
            if (totalActual >= maxSlides) {
                alert('<?php echo esc_js(__('Máximo', 'ygb-slider-2')); ?> ' + maxSlides + ' <?php echo esc_js(__('slides permitidos', 'ygb-slider-2')); ?>');
                return;
            }
            
            var $nuevo = $('.slide_box:first').clone();
            var nuevoIndex = $('.slide_box').length + 1;
            
            // Limpiar valores
            $nuevo.find('h3').text('<?php echo esc_js(__('Slide', 'ygb-slider-2')); ?> ' + nuevoIndex);
            $nuevo.find('.img_url').val('');
            $nuevo.find('input[type="text"]').val('');
            $nuevo.find('textarea').val('');
            $nuevo.find('input[type="url"]').val('');
            $nuevo.find('select').val('_self');
            $nuevo.find('.vista_previa').remove();
            $nuevo.attr('data-index', totalActual);
            
            $('#lista_slides').append($nuevo);
        });
        
        // Eliminar slide
        $(document).on('click', '.eliminar_slide', function() {
            if ($('.slide_box').length > 1) {
                if (confirm('<?php echo esc_js(__('¿Eliminar este slide? Esta acción no se guardará hasta que hagas clic en "Guardar Slides".', 'ygb-slider-2')); ?>')) {
                    $(this).closest('.slide_box').remove();
                    
                    // Renumerar
                    $('.slide_box').each(function(i) {
                        $(this).find('h3').text('<?php echo esc_js(__('Slide', 'ygb-slider-2')); ?> ' + (i + 1));
                        $(this).attr('data-index', i);
                    });
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

/**
 * Renderiza y procesa la página de configuración.
 * 
 * @return void
 */
function ygb_slider2_config() {
    // Verificar capacidades
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('No tienes permisos para acceder a esta página.', 'ygb-slider-2'));
    }
    
    // Procesar guardado
    if (isset($_POST['guardar_config'])) {
        // Verificar nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'accion_config2')) {
            wp_die(esc_html__('Verificación de seguridad fallida.', 'ygb-slider-2'));
        }
        
        // Validar y sanitizar velocidad
        $velocidad = isset($_POST['velocidad']) ? absint(wp_unslash($_POST['velocidad'])) : YGB_SLIDER2_DEFAULT_VELOCIDAD;
        $velocidad = max(YGB_SLIDER2_MIN_VELOCIDAD, min(YGB_SLIDER2_MAX_VELOCIDAD, $velocidad));
        update_option('ygb_slider2_velocidad', $velocidad);
        
        // Sanitizar autoplay
        $autoplay = isset($_POST['autoplay']) ? 1 : 0;
        update_option('ygb_slider2_autoplay', $autoplay);
        
        // Sanitizar color
        $color = isset($_POST['color']) ? sanitize_hex_color(wp_unslash($_POST['color'])) : '#ff6b6b';
        if (empty($color)) {
            $color = '#ff6b6b';
        }
        update_option('ygb_slider2_color', $color);
        
        add_settings_error('ygb_slider2', 'config_guardada', esc_html__('Configuración guardada correctamente.', 'ygb-slider-2'), 'success');
    }
    
    $vel = absint(get_option('ygb_slider2_velocidad', YGB_SLIDER2_DEFAULT_VELOCIDAD));
    $auto = absint(get_option('ygb_slider2_autoplay', 1));
    $color = sanitize_hex_color(get_option('ygb_slider2_color', '#ff6b6b'));
    
    settings_errors('ygb_slider2');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <form method="post" action="" style="background:white; padding:20px; border-radius:10px; max-width:500px">
            <?php wp_nonce_field('accion_config2'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="velocidad"><?php esc_html_e('Velocidad', 'ygb-slider-2'); ?></label>
                    </th>
                    <td>
                        <input type="number" 
                               id="velocidad"
                               name="velocidad" 
                               value="<?php echo absint($vel); ?>" 
                               min="<?php echo absint(YGB_SLIDER2_MIN_VELOCIDAD); ?>" 
                               max="<?php echo absint(YGB_SLIDER2_MAX_VELOCIDAD); ?>" 
                               step="100"
                               class="small-text">
                        <p class="description">
                            <?php esc_html_e('Milisegundos (1000ms = 1s)', 'ygb-slider-2'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Autoplay', 'ygb-slider-2'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="autoplay" 
                                   value="1" 
                                   <?php checked($auto, 1); ?>>
                            <?php esc_html_e('Activar autoplay', 'ygb-slider-2'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="color"><?php esc_html_e('Color principal', 'ygb-slider-2'); ?></label>
                    </th>
                    <td>
                        <input type="color" 
                               id="color"
                               name="color" 
                               value="<?php echo esc_attr($color); ?>">
                        <p class="description">
                            <?php esc_html_e('Color de las barras de progreso', 'ygb-slider-2'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(esc_html__('Guardar Configuración', 'ygb-slider-2'), 'primary', 'guardar_config'); ?>
        </form>
    </div>
    <?php
}

// ==================== SHORTCODE ====================

/**
 * Shortcode para mostrar el slider.
 *
 * @param array $atts Atributos del shortcode.
 * @return string HTML del slider.
 */
function ygb_slider2_shortcode($atts) {
    // Parsear atributos con valores por defecto seguros
    $atts = shortcode_atts(array(
        'velocidad' => get_option('ygb_slider2_velocidad', YGB_SLIDER2_DEFAULT_VELOCIDAD),
        'autoplay' => get_option('ygb_slider2_autoplay', 1) ? 'si' : 'no',
        'admin_panel' => 'no' // Nuevo atributo para controlar panel admin
    ), $atts, 'ygb_slider2');
    
    // Validar velocidad
    $velocidad = absint($atts['velocidad']);
    $velocidad = max(YGB_SLIDER2_MIN_VELOCIDAD, min(YGB_SLIDER2_MAX_VELOCIDAD, $velocidad));
    
    // Validar autoplay
    $autoplay = in_array(strtolower($atts['autoplay']), array('si', 'true', '1', 'yes'), true);
    
    // Determinar si mostrar panel admin (solo si se pide explícitamente y tiene permisos)
    $mostrar_panel = false;
    if (defined('WP_DEBUG') && WP_DEBUG && 
        strtolower($atts['admin_panel']) === 'yes' && 
        current_user_can('manage_options')) {
        $mostrar_panel = true;
    }
    
    // Obtener configuración
    $color = sanitize_hex_color(get_option('ygb_slider2_color', '#ff6b6b'));
    $slides = get_option('ygb_slider2_slides', array());
    
    if (empty($slides)) {
        return '<p style="padding:20px; text-align:center; background:#f8d7da; border-radius:5px;">' . 
               esc_html__('⚠️ No hay slides configuradas. Ve al panel de administración y agrega imágenes.', 'ygb-slider-2') . 
               '</p>';
    }
    
    // Generar ID único para este slider
    static $slider_counter = 0;
    $slider_counter++;
    $id = 'ygb_slider_' . $slider_counter . '_' . wp_rand(1000, 9999);
    
    ob_start();
    ?>
    <div id="<?php echo esc_attr($id); ?>" 
         class="ygb-slider" 
         data-vel="<?php echo absint($velocidad); ?>" 
         data-auto="<?php echo $autoplay ? '1' : '0'; ?>"
         data-instance="<?php echo absint($slider_counter); ?>">
        
        <?php if ($mostrar_panel) : ?>
        <div class="ygb-panel" style="background:#1e1e2f; padding:10px 20px; border-radius:10px; margin-bottom:10px">
            <div style="display:flex; gap:15px; align-items:center; flex-wrap:wrap; color:white">
                <span>⚡ <?php esc_html_e('Velocidad:', 'ygb-slider-2'); ?></span>
                <input type="range" 
                       class="ygb-rango" 
                       min="<?php echo absint(YGB_SLIDER2_MIN_VELOCIDAD); ?>" 
                       max="<?php echo absint(YGB_SLIDER2_MAX_VELOCIDAD); ?>" 
                       step="100" 
                       value="<?php echo absint($velocidad); ?>" 
                       style="width:180px">
                <span class="ygb-valor" style="background:#333; padding:4px 12px; border-radius:20px">
                    <?php echo esc_html($velocidad / 1000); ?>s
                </span>
                <button class="ygb-pre" data-vel="2000" style="background:#333; border:none; color:white; padding:4px 12px; border-radius:20px; cursor:pointer">2s</button>
                <button class="ygb-pre" data-vel="4000" style="background:#333; border:none; color:white; padding:4px 12px; border-radius:20px; cursor:pointer">4s</button>
                <button class="ygb-pre" data-vel="6000" style="background:#333; border:none; color:white; padding:4px 12px; border-radius:20px; cursor:pointer">6s</button>
                <button class="ygb-pre" data-vel="8000" style="background:#333; border:none; color:white; padding:4px 12px; border-radius:20px; cursor:pointer">8s</button>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="ygb-contenedor" style="position:relative; overflow:hidden; background:#000; line-height:0">
            <div class="ygb-pista" style="display:flex; transition:transform 0.5s ease">
                <?php foreach ($slides as $index => $s) : 
                    // Validar datos del slide
                    if (empty($s['img']) || !ygb_slider2_validar_img_url($s['img'])) {
                        continue;
                    }
                ?>
                <div class="ygb-item" style="min-width:100%; position:relative" data-slide="<?php echo absint($index); ?>">
                    <?php 
                    // Preparar imagen con atributos seguros
                    $img_src = esc_url($s['img']);
                    $img_alt = !empty($s['titulo']) ? esc_attr($s['titulo']) : esc_attr__('Slide imagen', 'ygb-slider-2');
                    
                    $img_html = sprintf(
                        '<img src="%s" style="width:100%%; height:auto; display:block" alt="%s" loading="lazy">',
                        $img_src,
                        $img_alt
                    );
                    
                    // Agregar enlace si existe y es válido
                    if (!empty($s['url'])) {
                        $url_validada = ygb_slider2_validar_url_enlace($s['url']);
                        if (!empty($url_validada)) {
                            $target_attr = ($s['target'] === '_blank') ? ' target="_blank" rel="noopener noreferrer"' : '';
                            $img_html = sprintf(
                                '<a href="%s"%s class="ygb-slide-link">%s</a>',
                                esc_url($url_validada),
                                $target_attr,
                                $img_html
                            );
                        }
                    }
                    
                    echo $img_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ya escapado arriba
                    ?>
                    
                    <?php if (!empty($s['titulo']) || !empty($s['texto'])) : ?>
                    <div class="ygb-texto-overlay">
                        <?php if (!empty($s['titulo'])) : ?>
                        <h3><?php echo esc_html($s['titulo']); ?></h3>
                        <?php endif; ?>
                        
                        <?php if (!empty($s['texto'])) : ?>
                        <div class="ygb-descripcion">
                            <?php 
                            // wp_kses_post ya aplicado al guardar, pero re-aplicamos por seguridad
                            echo wp_kses_post($s['texto']); 
                            ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="ygb-dots" style="position:absolute; bottom:15px; left:50%; transform:translateX(-50%); display:flex; gap:10px; z-index:10"></div>
        </div>
    </div>
    
    <style>
    .ygb-slider { 
        display: inline-block; 
        width: auto; 
        max-width: 100%; 
        margin: 0; 
        padding: 0; 
        line-height: 0; 
    }
    .ygb-contenedor { 
        display: inline-block; 
        width: auto; 
        max-width: 100%; 
        border-radius: 0; 
        position: relative; 
    }
    .ygb-pista, .ygb-item { 
        line-height: 0; 
    }
    .ygb-item img { 
        width: 100%; 
        max-width: 100%; 
        height: auto; 
        display: block; 
    }
    .ygb-slide-link { 
        display: block; 
        line-height: 0; 
        text-decoration: none; 
    }
    .ygb-slide-link:hover { 
        opacity: 0.95; 
        transition: opacity 0.3s ease; 
    }
    .ygb-texto-overlay { 
        position: absolute; 
        bottom: 20px; 
        left: 20px; 
        right: 20px; 
        background: rgba(0, 0, 0, 0.6); 
        color: white; 
        padding: 12px 20px; 
        border-radius: 8px; 
        text-align: left; 
        line-height: 1.4; 
        backdrop-filter: blur(4px); 
        z-index: 5; 
        pointer-events: none; 
    }
    .ygb-texto-overlay h3 { 
        margin: 0 0 5px 0; 
        font-size: 1.3rem; 
        color: white; 
    }
    .ygb-descripcion { 
        margin: 0; 
        font-size: 0.9rem; 
        color: rgba(255,255,255,0.9); 
    }
    .ygb-descripcion a { 
        color: #fff; 
        text-decoration: underline; 
        pointer-events: auto; 
    }
    .ygb-descripcion strong { 
        font-weight: bold; 
    }
    .ygb-descripcion em { 
        font-style: italic; 
    }
    .ygb-pre { 
        transition: all 0.2s; 
        cursor: pointer; 
    }
    .ygb-pre:hover { 
        background: <?php echo esc_attr($color); ?> !important; 
    }
    .ygb-dot { 
        cursor: pointer; 
        width: 35px; 
        transition: opacity 0.2s; 
        display: inline-block; 
    }
    .ygb-dot-bar { 
        width: 100%; 
        height: 3px; 
        background: rgba(255,255,255,0.5); 
        border-radius: 3px; 
        overflow: hidden; 
    }
    .ygb-dot-fill { 
        height: 100%; 
        width: 0%; 
        background: <?php echo esc_attr($color); ?>; 
        transition: width 0.05s linear; 
        border-radius: 3px; 
    }
    @media (max-width: 768px) {
        .ygb-dot { width: 28px; }
        .ygb-panel { padding: 8px 12px; }
        .ygb-panel div { gap: 8px; }
        .ygb-rango { width: 140px; }
        .ygb-texto-overlay { 
            bottom: 12px; 
            left: 12px; 
            right: 12px; 
            padding: 8px 12px; 
        }
        .ygb-texto-overlay h3 { 
            font-size: 1rem; 
            margin-bottom: 3px; 
        }
        .ygb-descripcion { 
            font-size: 0.75rem; 
        }
    }
    </style>
    
    <script>
    (function() {
        'use strict';
        
        var contenedor = document.getElementById('<?php echo esc_js($id); ?>');
        if (!contenedor) return;
        
        var velocidad = parseInt(contenedor.dataset.vel, 10) || <?php echo absint(YGB_SLIDER2_DEFAULT_VELOCIDAD); ?>;
        var autoplay = contenedor.dataset.auto === '1';
        var actual = 0;
        var items = contenedor.querySelectorAll('.ygb-item');
        var total = items.length;
        
        if (total === 0) return;
        
        var animacion = null;
        var tiempoInicio = 0;
        var pausado = false;
        
        var pista = contenedor.querySelector('.ygb-pista');
        var dotsDiv = contenedor.querySelector('.ygb-dots');
        
        // Intentar recuperar velocidad de localStorage (solo si es válida)
        try {
            var guardado = localStorage.getItem('ygb_slider2_velocidad_<?php echo absint($slider_counter); ?>');
            if (guardado) {
                var nuevaVel = parseInt(guardado, 10);
                if (!isNaN(nuevaVel) && nuevaVel >= <?php echo absint(YGB_SLIDER2_MIN_VELOCIDAD); ?> && nuevaVel <= <?php echo absint(YGB_SLIDER2_MAX_VELOCIDAD); ?>) {
                    velocidad = nuevaVel;
                }
            }
        } catch(e) {
            // localStorage no disponible o bloqueado
        }
        
        function detenerAnim() { 
            if (animacion) { 
                cancelAnimationFrame(animacion); 
                animacion = null; 
            } 
        }
        
        function crearDots() {
            if (!dotsDiv) return;
            dotsDiv.innerHTML = '';
            for (var i = 0; i < total; i++) {
                var dot = document.createElement('div');
                dot.className = 'ygb-dot';
                dot.setAttribute('data-idx', i);
                dot.setAttribute('role', 'button');
                dot.setAttribute('aria-label', '<?php echo esc_js(__('Ir a slide', 'ygb-slider-2')); ?> ' + (i + 1));
                dot.innerHTML = '<div class="ygb-dot-bar"><div class="ygb-dot-fill"></div></div>';
                dot.onclick = (function(idx) { 
                    return function() { irA(idx); }; 
                })(i);
                dotsDiv.appendChild(dot);
            }
            actualizarDots();
        }
        
        function actualizarDots() {
            if (!dotsDiv) return;
            var dots = dotsDiv.querySelectorAll('.ygb-dot');
            for (var i = 0; i < dots.length; i++) {
                dots[i].style.opacity = (i === actual) ? '1' : '0.5';
            }
        }
        
        function irA(index) {
            if (index < 0) index = total - 1;
            if (index >= total) index = 0;
            if (actual === index) return;
            
            actual = index;
            if (pista) {
                pista.style.transform = 'translateX(-' + (actual * 100) + '%)';
            }
            actualizarDots();
            
            if (autoplay && !pausado) {
                detenerAnim();
                iniciarTimer();
            }
        }
        
        function siguiente() { 
            irA(actual + 1); 
        }
        
        function iniciarTimer() {
            detenerAnim();
            
            var dotActivo = dotsDiv ? dotsDiv.querySelectorAll('.ygb-dot')[actual] : null;
            if (dotActivo) {
                var fill = dotActivo.querySelector('.ygb-dot-fill');
                if (fill) fill.style.width = '0%';
            }
            
            tiempoInicio = performance.now();
            
            function animar(ahora) {
                if (pausado) { 
                    animacion = requestAnimationFrame(animar); 
                    return; 
                }
                
                var transcurrido = ahora - tiempoInicio;
                var progreso = Math.min(1, transcurrido / velocidad);
                
                var dotActivo = dotsDiv ? dotsDiv.querySelectorAll('.ygb-dot')[actual] : null;
                if (dotActivo) {
                    var fill = dotActivo.querySelector('.ygb-dot-fill');
                    if (fill) fill.style.width = (progreso * 100) + '%';
                }
                
                if (progreso >= 1) { 
                    siguiente(); 
                } else { 
                    animacion = requestAnimationFrame(animar); 
                }
            }
            
            animacion = requestAnimationFrame(animar);
        }
        
        function cambiarVelocidad(nueva) {
            var minVel = <?php echo absint(YGB_SLIDER2_MIN_VELOCIDAD); ?>;
            var maxVel = <?php echo absint(YGB_SLIDER2_MAX_VELOCIDAD); ?>;
            
            if (nueva < minVel) nueva = minVel;
            if (nueva > maxVel) nueva = maxVel;
            
            velocidad = nueva;
            
            try { 
                localStorage.setItem('ygb_slider2_velocidad_<?php echo absint($slider_counter); ?>', nueva); 
            } catch(e) {}
            
            var rango = contenedor.querySelector('.ygb-rango');
            var valor = contenedor.querySelector('.ygb-valor');
            
            if (rango) rango.value = nueva;
            if (valor) valor.textContent = (nueva / 1000).toFixed(1) + 's';
            
            if (autoplay && !pausado) {
                detenerAnim();
                iniciarTimer();
            }
        }
        
        // Event listeners
        var sliderDiv = contenedor.querySelector('.ygb-contenedor');
        if (sliderDiv) {
            sliderDiv.addEventListener('mouseenter', function() { pausado = true; });
            sliderDiv.addEventListener('mouseleave', function() { 
                pausado = false; 
                if (autoplay) iniciarTimer(); 
            });
            
            // Pausar cuando no es visible (intersection observer)
            if ('IntersectionObserver' in window) {
                var observer = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (!entry.isIntersecting) {
                            pausado = true;
                        } else if (!sliderDiv.matches(':hover')) {
                            pausado = false;
                            if (autoplay) iniciarTimer();
                        }
                    });
                }, { threshold: 0.5 });
                
                observer.observe(sliderDiv);
            }
        }
        
        // Controles de velocidad
        var rangoVel = contenedor.querySelector('.ygb-rango');
        if (rangoVel) { 
            rangoVel.oninput = function(e) { 
                cambiarVelocidad(parseInt(e.target.value, 10)); 
            }; 
        }
        
        var botonesPre = contenedor.querySelectorAll('.ygb-pre');
        for (var i = 0; i < botonesPre.length; i++) { 
            botonesPre[i].onclick = function() { 
                cambiarVelocidad(parseInt(this.dataset.vel, 10)); 
            }; 
        }
        
        // Inicializar
        crearDots();
        irA(0);
        if (autoplay) iniciarTimer();
        
        // Actualizar UI inicial
        var rangoIni = contenedor.querySelector('.ygb-rango');
        if (rangoIni) rangoIni.value = velocidad;
        var valorIni = contenedor.querySelector('.ygb-valor');
        if (valorIni) valorIni.textContent = (velocidad / 1000).toFixed(1) + 's';
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('ygb_slider2', 'ygb_slider2_shortcode');

// ==================== ELEMENTOR WIDGET ====================

/**
 * Registra el widget de Elementor si está disponible.
 * 
 * @param \Elementor\Widgets_Manager $widgets_manager Manager de widgets.
 * @return void
 */
function ygb_slider2_register_elementor_widget($widgets_manager) {
    if (!class_exists('Elementor\Widget_Base')) {
        return;
    }
    
    /**
     * Widget de Elementor para YGB Slider 2.
     */
    class YGB_Slider2_Widget extends \Elementor\Widget_Base {
        
        /**
         * Obtiene el nombre del widget.
         *
         * @return string
         */
        public function get_name() { 
            return 'ygb_slider2'; 
        }
        
        /**
         * Obtiene el título del widget.
         *
         * @return string
         */
        public function get_title() { 
            return esc_html__('YGB Slider 2', 'ygb-slider-2'); 
        }
        
        /**
         * Obtiene el icono del widget.
         *
         * @return string
         */
        public function get_icon() { 
            return 'eicon-slider-push'; 
        }
        
        /**
         * Obtiene las categorías del widget.
         *
         * @return array
         */
        public function get_categories() { 
            return array('general'); 
        }
        
        /**
         * Registra los controles del widget.
         *
         * @return void
         */
        protected function register_controls() {
            $this->start_controls_section(
                'seccion', 
                array('label' => esc_html__('Configuración', 'ygb-slider-2'))
            );
            
            $this->add_control(
                'velocidad', 
                array(
                    'label' => esc_html__('Velocidad (ms)', 'ygb-slider-2'), 
                    'type' => \Elementor\Controls_Manager::NUMBER, 
                    'default' => absint(get_option('ygb_slider2_velocidad', YGB_SLIDER2_DEFAULT_VELOCIDAD)),
                    'min' => YGB_SLIDER2_MIN_VELOCIDAD,
                    'max' => YGB_SLIDER2_MAX_VELOCIDAD,
                    'step' => 100
                )
            );
            
            $this->add_control(
                'autoplay', 
                array(
                    'label' => esc_html__('Autoplay', 'ygb-slider-2'), 
                    'type' => \Elementor\Controls_Manager::SWITCHER, 
                    'default' => absint(get_option('ygb_slider2_autoplay', 1)) ? 'yes' : 'no'
                )
            );
            
            $this->end_controls_section();
        }
        
        /**
         * Renderiza el widget.
         *
         * @return void
         */
        protected function render() {
            $settings = $this->get_settings_for_display();
            
            $velocidad = absint($settings['velocidad']);
            $velocidad = max(YGB_SLIDER2_MIN_VELOCIDAD, min(YGB_SLIDER2_MAX_VELOCIDAD, $velocidad));
            
            $autoplay = ($settings['autoplay'] === 'yes') ? 'si' : 'no';
            
            echo do_shortcode(sprintf(
                '[ygb_slider2 velocidad="%d" autoplay="%s"]',
                $velocidad,
                esc_attr($autoplay)
            ));
        }
    }
    
    $widgets_manager->register(new YGB_Slider2_Widget());
}
add_action('elementor/widgets/register', 'ygb_slider2_register_elementor_widget');

// ==================== ADMIN SCRIPTS ====================

/**
 * Encola scripts de admin para el plugin.
 *
 * @param string $hook Hook de la página actual.
 * @return void
 */
function ygb_slider2_admin_scripts($hook) {
    if (strpos($hook, 'ygb-slider-2') === false) {
        return;
    }
    
    wp_enqueue_media();
    
    // Añadir estilos adicionales si es necesario
    wp_enqueue_style(
        'ygb-slider-2-admin',
        false,
        array(),
        YGB_SLIDER2_VERSION
    );
}
add_action('admin_enqueue_scripts', 'ygb_slider2_admin_scripts');

// ==================== DESACTIVACIÓN ====================

/**
 * Callback de desactivación del plugin.
 * 
 * @return void
 */
function ygb_slider2_limpiar() {
    // Las opciones se conservan para preservar datos al reactivar
    // La limpieza completa ocurre en desinstalación
}
register_deactivation_hook(__FILE__, 'ygb_slider2_limpiar');