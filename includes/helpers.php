<?php
/**
 * Funciones auxiliares para AliExpress Smart Ads
 *
 * @package AliExpress_Smart_Ads
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Obtener opciones del plugin
 * @param string $key Clave específica (opcional)
 * @param mixed $default Valor por defecto
 * @return mixed
 */
function ali_ads_get_option($key = '', $default = null) {
    $options = get_option('ali_ads_options', array());
    
    if (empty($key)) {
        return $options;
    }
    
    return isset($options[$key]) ? $options[$key] : $default;
}

/**
 * Actualizar una opción del plugin
 * @param string $key Clave
 * @param mixed $value Valor
 * @return bool
 */
function ali_ads_update_option($key, $value) {
    $options = get_option('ali_ads_options', array());
    $options[$key] = $value;
    return update_option('ali_ads_options', $options);
}

/**
 * Obtener todas las categorías de WordPress
 * @return array
 */
function ali_ads_get_categories() {
    $categories = get_categories(array(
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC'
    ));
    
    $cat_array = array(
        '' => __('Todas las categorías', 'aliexpress-smart-ads'),
        'general' => __('General (por defecto)', 'aliexpress-smart-ads')
    );
    
    foreach ($categories as $category) {
        $cat_array[$category->slug] = $category->name;
    }
    
    return $cat_array;
}

/**
 * Obtener las ubicaciones disponibles para banners
 * @return array
 */
function ali_ads_get_placements() {
    return array(
        'header' => __('Header (cabecera)', 'aliexpress-smart-ads'),
        'footer' => __('Footer (pie de página)', 'aliexpress-smart-ads'),
        'sidebar' => __('Sidebar (barra lateral)', 'aliexpress-smart-ads'),
        'in_content' => __('Dentro del contenido', 'aliexpress-smart-ads'),
        'floating_bar' => __('Barra flotante', 'aliexpress-smart-ads'),
        'between_articles' => __('Entre artículos', 'aliexpress-smart-ads')
    );
}

/**
 * Obtener la categoría del post actual
 * @param int $post_id ID del post (opcional)
 * @return string
 */
function ali_ads_get_current_post_category($post_id = 0) {
    if (empty($post_id)) {
        global $post;
        $post_id = $post ? $post->ID : 0;
    }
    
    if (empty($post_id)) {
        return '';
    }
    
    $categories = get_the_category($post_id);
    
    if (!empty($categories)) {
        return $categories[0]->slug;
    }
    
    return '';
}

/**
 * Limpiar y validar URL
 * @param string $url
 * @return string
 */
function ali_ads_clean_url($url) {
    $url = trim($url);
    
    if (empty($url)) {
        return '';
    }
    
    // Agregar https:// si no tiene protocolo
    if (!preg_match('/^https?:\/\//', $url)) {
        $url = 'https://' . $url;
    }
    
    return esc_url_raw($url);
}

/**
 * Validar si es una URL de AliExpress válida
 * @param string $url
 * @return bool
 */
function ali_ads_is_aliexpress_url($url) {
    $parsed = parse_url($url);
    
    if (!$parsed || empty($parsed['host'])) {
        return false;
    }
    
    $allowed_domains = array(
        'aliexpress.com',
        'www.aliexpress.com',
        'es.aliexpress.com',
        'pt.aliexpress.com',
        'fr.aliexpress.com',
        'de.aliexpress.com',
        'it.aliexpress.com',
        'ru.aliexpress.com',
        's.click.aliexpress.com',
        'affiliates.aliexpress.com'
    );
    
    return in_array(strtolower($parsed['host']), $allowed_domains);
}

/**
 * Generar URL de clic con tracking
 * @param int $banner_id
 * @return string
 */
function ali_ads_get_click_url($banner_id) {
    return add_query_arg('ali_click', $banner_id, home_url());
}

/**
 * Formatear número para mostrar estadísticas
 * @param int $number
 * @return string
 */
function ali_ads_format_number($number) {
    if ($number >= 1000000) {
        return number_format($number / 1000000, 1) . 'M';
    } elseif ($number >= 1000) {
        return number_format($number / 1000, 1) . 'K';
    }
    return number_format($number);
}

/**
 * Obtener la posición actual en el loop
 * @return int
 */
function ali_ads_get_loop_position() {
    global $wp_query;
    return $wp_query->current_post + 1;
}

/**
 * Verificar si estamos en una página donde mostrar banners
 * @return bool
 */
function ali_ads_should_show_banners() {
    // No mostrar en admin
    if (is_admin()) {
        return false;
    }
    
    // No mostrar en feeds
    if (is_feed()) {
        return false;
    }
    
    // No mostrar en páginas de error
    if (is_404()) {
        return false;
    }
    
    // Permitir filtrado por otros plugins
    return apply_filters('ali_ads_should_show_banners', true);
}

/**
 * Log de errores del plugin
 * @param string $message
 * @param string $type
 */
function ali_ads_log($message, $type = 'info') {
    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log(sprintf('[AliExpress Smart Ads] [%s] %s', strtoupper($type), $message));
    }
}

/**
 * Obtener el contenido entre párrafos
 * @param string $content
 * @param int $paragraph_number
 * @return array
 */
function ali_ads_split_content_by_paragraphs($content, $paragraph_number = 1) {
    // Dividir por párrafos
    $paragraphs = preg_split('/(<p[^>]*>.*?<\/p>|<div[^>]*>.*?<\/div>)/s', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    
    $before = array();
    $after = array();
    $p_count = 0;
    
    foreach ($paragraphs as $index => $paragraph) {
        if (preg_match('/<(p|div)[^>]*>/', $paragraph)) {
            $p_count++;
        }
        
        if ($p_count <= $paragraph_number) {
            $before[] = $paragraph;
        } else {
            $after[] = $paragraph;
        }
    }
    
    return array(
        'before' => implode('', $before),
        'after' => implode('', $after)
    );
}

/**
 * Sanitizar código iframe
 * @param string $iframe_code
 * @return string
 */
function ali_ads_sanitize_iframe($iframe_code) {
    // Permitir solo iframes de dominios confiables
    $allowed_domains = array(
        'aliexpress.com',
        'affiliates.aliexpress.com',
        's.click.aliexpress.com'
    );
    
    // Validar que sea un iframe válido
    if (!preg_match('/<iframe[^>]*src=["\']([^"\']*)["\'][^>]*>/i', $iframe_code, $matches)) {
        return '';
    }
    
    $src = $matches[1];
    $parsed = parse_url($src);
    
    if (!$parsed || empty($parsed['host'])) {
        return '';
    }
    
    $domain_valid = false;
    foreach ($allowed_domains as $allowed_domain) {
        if (strpos($parsed['host'], $allowed_domain) !== false) {
            $domain_valid = true;
            break;
        }
    }
    
    if (!$domain_valid) {
        return '';
    }
    
    // Sanitizar el iframe completo
    return wp_kses($iframe_code, array(
        'iframe' => array(
            'src' => true,
            'width' => true,
            'height' => true,
            'frameborder' => true,
            'scrolling' => true,
            'allowfullscreen' => true,
            'style' => true,
            'class' => true,
            'id' => true
        )
    ));
}

/**
 * Obtener el User Agent para requests
 * @return string
 */
function ali_ads_get_user_agent() {
    return 'AliExpress Smart Ads WordPress Plugin ' . ALI_ADS_VERSION;
}

/**
 * Verificar si es móvil
 * @return bool
 */
function ali_ads_is_mobile() {
    return wp_is_mobile();
}