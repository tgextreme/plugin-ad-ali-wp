<?php
/**
 * Archivo de desinstalación para AliExpress Smart Ads
 * 
 * Este archivo se ejecuta cuando el plugin se desinstala completamente
 * desde el panel de administración de WordPress.
 *
 * @package AliExpress_Smart_Ads
 */

// Evitar acceso directo
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Verificar permisos
if (!current_user_can('activate_plugins')) {
    return;
}

// Verificar que el plugin está siendo desinstalado
if (plugin_basename(__FILE__) !== WP_UNINSTALL_PLUGIN) {
    return;
}

/**
 * Limpiar completamente el plugin de la base de datos
 */
function ali_ads_complete_cleanup() {
    global $wpdb;
    
    // Eliminar tablas del plugin
    $table_names = array(
        $wpdb->prefix . 'aliexpress_ads',
        $wpdb->prefix . 'aliexpress_ads_events'
    );
    
    foreach ($table_names as $table_name) {
        $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
    }
    
    // Eliminar todas las opciones del plugin
    $option_names = array(
        'ali_ads_options',
        'ali_ads_db_version',
        'ali_ads_version',
        'ali_ads_activation_date',
        'ali_ads_stats_cache'
    );
    
    foreach ($option_names as $option_name) {
        delete_option($option_name);
        
        // También eliminar opciones de sitios en multisite
        delete_site_option($option_name);
    }
    
    // Eliminar metadatos de posts relacionados
    delete_post_meta_by_key('_ali_ads_banner_id');
    delete_post_meta_by_key('_ali_ads_excluded');
    
    // Eliminar metadatos de usuarios
    delete_metadata('user', 0, 'ali_ads_preferences', '', true);
    
    // Eliminar transients
    delete_transient('ali_ads_banner_cache');
    delete_transient('ali_ads_stats_cache');
    
    // Limpiar caché de objeto si existe
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
    // Eliminar archivos de caché personalizados si existen
    $cache_dir = WP_CONTENT_DIR . '/cache/ali-ads/';
    if (is_dir($cache_dir)) {
        ali_ads_delete_directory($cache_dir);
    }
    
    // Eliminar cron jobs programados
    $cron_hooks = array(
        'ali_ads_cleanup_old_events',
        'ali_ads_update_stats',
        'ali_ads_check_banners'
    );
    
    foreach ($cron_hooks as $hook) {
        wp_clear_scheduled_hook($hook);
    }
    
    // Eliminar roles y capacidades personalizadas si se crearon
    $role = get_role('ali_ads_manager');
    if ($role) {
        remove_role('ali_ads_manager');
    }
    
    // Eliminar capacidades de roles existentes
    $roles_to_clean = array('administrator', 'editor');
    foreach ($roles_to_clean as $role_name) {
        $role = get_role($role_name);
        if ($role) {
            $role->remove_cap('manage_ali_ads');
            $role->remove_cap('edit_ali_banners');
            $role->remove_cap('delete_ali_banners');
        }
    }
    
    // Limpiar widgets
    $sidebars_widgets = get_option('sidebars_widgets');
    if (is_array($sidebars_widgets)) {
        foreach ($sidebars_widgets as $sidebar => $widgets) {
            if (is_array($widgets)) {
                $sidebars_widgets[$sidebar] = array_filter($widgets, function($widget) {
                    return strpos($widget, 'ali_banner_widget') === false;
                });
            }
        }
        update_option('sidebars_widgets', $sidebars_widgets);
    }
    
    // Eliminar opciones de widgets
    $widget_options = $wpdb->get_results(
        "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'widget_ali_%'"
    );
    
    foreach ($widget_options as $option) {
        delete_option($option->option_name);
    }
    
    // Limpiar shortcodes almacenados en posts
    $posts_with_shortcodes = $wpdb->get_results(
        "SELECT ID, post_content FROM {$wpdb->posts} 
         WHERE post_content LIKE '%[ali_banner%' 
         AND post_status IN ('publish', 'draft', 'private')"
    );
    
    foreach ($posts_with_shortcodes as $post) {
        $new_content = preg_replace('/\[ali_banner[^\]]*\]/', '', $post->post_content);
        
        $wpdb->update(
            $wpdb->posts,
            array('post_content' => $new_content),
            array('ID' => $post->ID),
            array('%s'),
            array('%d')
        );
    }
    
    // Limpiar comentarios de meta relacionados
    $wpdb->query("DELETE FROM {$wpdb->commentmeta} WHERE meta_key LIKE '_ali_ads_%'");
    
    // Eliminar logs personalizados si existen
    $log_file = WP_CONTENT_DIR . '/debug-ali-ads.log';
    if (file_exists($log_file)) {
        unlink($log_file);
    }
    
    // Limpiar .htaccess si se modificó
    ali_ads_cleanup_htaccess();
}

/**
 * Eliminar directorio recursivamente
 */
function ali_ads_delete_directory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), array('.', '..'));
    
    foreach ($files as $file) {
        $file_path = $dir . DIRECTORY_SEPARATOR . $file;
        
        if (is_dir($file_path)) {
            ali_ads_delete_directory($file_path);
        } else {
            unlink($file_path);
        }
    }
    
    return rmdir($dir);
}

/**
 * Limpiar reglas de .htaccess si fueron añadidas
 */
function ali_ads_cleanup_htaccess() {
    if (!function_exists('get_home_path')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    
    $htaccess_file = get_home_path() . '.htaccess';
    
    if (!file_exists($htaccess_file)) {
        return;
    }
    
    $htaccess_content = file_get_contents($htaccess_file);
    
    // Eliminar reglas añadidas por el plugin
    $pattern = '/# BEGIN AliExpress Smart Ads.*?# END AliExpress Smart Ads\s*/s';
    $new_content = preg_replace($pattern, '', $htaccess_content);
    
    if ($new_content !== $htaccess_content) {
        file_put_contents($htaccess_file, $new_content);
    }
}

/**
 * Limpiar en multisite
 */
function ali_ads_multisite_cleanup() {
    if (!is_multisite()) {
        return;
    }
    
    global $wpdb;
    
    // Obtener todos los sitios
    $sites = get_sites(array(
        'fields' => 'ids',
        'number' => 0
    ));
    
    foreach ($sites as $site_id) {
        switch_to_blog($site_id);
        
        // Ejecutar limpieza en cada sitio
        ali_ads_complete_cleanup();
        
        restore_current_blog();
    }
    
    // Limpiar opciones de red
    delete_site_option('ali_ads_network_options');
}

/**
 * Crear registro de desinstalación para auditoría
 */
function ali_ads_log_uninstall() {
    $log_data = array(
        'plugin' => 'AliExpress Smart Ads',
        'version' => get_option('ali_ads_version', 'unknown'),
        'uninstall_date' => current_time('mysql'),
        'user_id' => get_current_user_id(),
        'site_url' => site_url(),
        'wp_version' => get_bloginfo('version'),
        'php_version' => PHP_VERSION
    );
    
    // Guardar en wp_options temporalmente (se eliminará después)
    add_option('ali_ads_uninstall_log', $log_data, '', 'no');
    
    // Enviar log si está configurado (opcional)
    $send_uninstall_data = get_option('ali_ads_send_uninstall_data', false);
    if ($send_uninstall_data) {
        wp_remote_post('https://stats.yourpluginsite.com/uninstall', array(
            'body' => $log_data,
            'timeout' => 5
        ));
    }
    
    // Eliminar el log después de enviarlo
    delete_option('ali_ads_uninstall_log');
    delete_option('ali_ads_send_uninstall_data');
}

/**
 * Verificar si hay datos que preservar
 */
function ali_ads_should_preserve_data() {
    // Verificar si hay una opción para preservar datos
    $preserve_data = get_option('ali_ads_preserve_data_on_uninstall', false);
    
    if ($preserve_data) {
        // Solo eliminar archivos y opciones no críticas
        delete_option('ali_ads_preserve_data_on_uninstall');
        
        // Crear archivo de información para reinstalación
        $preserve_info = array(
            'banners_count' => wp_count_posts('ali_banner'),
            'uninstall_date' => current_time('mysql'),
            'version' => get_option('ali_ads_version')
        );
        
        update_option('ali_ads_preserved_info', $preserve_info);
        
        return true;
    }
    
    return false;
}

// Ejecutar limpieza
try {
    // Log de desinstalación
    ali_ads_log_uninstall();
    
    // Verificar si debemos preservar datos
    if (!ali_ads_should_preserve_data()) {
        // Limpiar en multisite si aplica
        if (is_multisite()) {
            ali_ads_multisite_cleanup();
        } else {
            // Limpieza estándar
            ali_ads_complete_cleanup();
        }
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Limpiar caché de plugins
    if (function_exists('wp_clean_plugins_cache')) {
        wp_clean_plugins_cache();
    }
    
} catch (Exception $e) {
    // Si hay error, registrarlo
    error_log('AliExpress Smart Ads uninstall error: ' . $e->getMessage());
}

// Mensaje de confirmación para debug
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('AliExpress Smart Ads: Plugin uninstalled successfully');
}