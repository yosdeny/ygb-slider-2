<?php
/**
 * uninstall.php - Limpieza completa al desinstalar YGB Slider 2
 *
 * Este archivo se ejecuta cuando el usuario elimina el plugin desde WordPress.
 * Elimina todas las opciones de la base de datos relacionadas con el plugin.
 *
 * @package YGB_Slider_2
 * @since 3.1
 */

// Verificar que WordPress está cargando este archivo para desinstalación
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Verificar capacidad de administrador para mayor seguridad
if (!current_user_can('activate_plugins')) {
    exit;
}

// Lista de todas las opciones del plugin a eliminar
$opciones_a_eliminar = array(
    'ygb_slider2_slides',
    'ygb_slider2_velocidad',
    'ygb_slider2_autoplay',
    'ygb_slider2_color'
);

// Eliminar cada opción
foreach ($opciones_a_eliminar as $opcion) {
    delete_option($opcion);
}

// Limpiar posibles opciones de red (si es multisite)
if (is_multisite()) {
    // Obtener todos los IDs de blog
    $blog_ids = get_sites(array(
        'fields' => 'ids',
        'number' => -1
    ));
    
    // Eliminar opciones de cada sitio
    foreach ($blog_ids as $blog_id) {
        switch_to_blog($blog_id);
        foreach ($opciones_a_eliminar as $opcion) {
            delete_option($opcion);
        }
        restore_current_blog();
    }
}