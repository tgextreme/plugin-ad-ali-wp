<?php
/**
 * Clase de estadísticas para tracking de clics e impresiones
 *
 * @package AliExpress_Smart_Ads
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class Ali_Stats {

    /**
     * Constructor
     */
    public function __construct() {
        // Hook para limpiar estadísticas antiguas
        add_action('wp', array($this, 'maybe_schedule_cleanup'));
    }

    /**
     * Registrar una impresión
     * @param int $banner_id
     */
    public static function track_impression($banner_id) {
        global $wpdb;
        
        if (!$banner_id || !is_numeric($banner_id)) {
            return false;
        }

        // Verificar si ya se registró esta impresión en esta sesión
        if (self::is_impression_tracked($banner_id)) {
            return false;
        }

        $table_name = $wpdb->prefix . 'aliexpress_ads';
        
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE {$table_name} SET impressions = impressions + 1 WHERE id = %d",
            $banner_id
        ));

        if ($result !== false) {
            // Marcar como registrado en esta sesión
            self::mark_impression_tracked($banner_id);
            
            // Log para debug
            ali_ads_log("Impression tracked for banner ID: {$banner_id}", 'info');
            
            return true;
        }

        return false;
    }

    /**
     * Registrar un clic
     * @param int $banner_id
     */
    public static function track_click($banner_id) {
        global $wpdb;
        
        if (!$banner_id || !is_numeric($banner_id)) {
            return false;
        }

        $table_name = $wpdb->prefix . 'aliexpress_ads';
        
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE {$table_name} SET clicks = clicks + 1 WHERE id = %d",
            $banner_id
        ));

        if ($result !== false) {
            // Registrar en tabla de eventos detallados (opcional)
            self::log_click_event($banner_id);
            
            // Log para debug
            ali_ads_log("Click tracked for banner ID: {$banner_id}", 'info');
            
            return true;
        }

        return false;
    }

    /**
     * Verificar si una impresión ya fue registrada en esta sesión
     * @param int $banner_id
     * @return bool
     */
    private static function is_impression_tracked($banner_id) {
        if (!isset($_SESSION)) {
            session_start();
        }

        $tracked_key = 'ali_ads_impressions_' . $banner_id;
        return isset($_SESSION[$tracked_key]);
    }

    /**
     * Marcar impresión como registrada
     * @param int $banner_id
     */
    private static function mark_impression_tracked($banner_id) {
        if (!isset($_SESSION)) {
            session_start();
        }

        $tracked_key = 'ali_ads_impressions_' . $banner_id;
        $_SESSION[$tracked_key] = time();
    }

    /**
     * Registrar evento de clic detallado
     * @param int $banner_id
     */
    private static function log_click_event($banner_id) {
        global $wpdb;
        
        // Crear tabla de eventos si no existe
        $events_table = $wpdb->prefix . 'aliexpress_ads_events';
        
        // Verificar si la tabla existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$events_table}'") === $events_table;
        
        if (!$table_exists) {
            self::create_events_table();
        }

        // Obtener información del usuario y página
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        $ip_address = self::get_client_ip();
        $page_url = $_SERVER['REQUEST_URI'] ?? '';
        
        // Insertar evento
        $wpdb->insert(
            $events_table,
            array(
                'banner_id' => $banner_id,
                'event_type' => 'click',
                'ip_address' => $ip_address,
                'user_agent' => substr($user_agent, 0, 500), // Limitar longitud
                'referrer' => $referrer,
                'page_url' => $page_url,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Crear tabla de eventos detallados
     */
    private static function create_events_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $events_table = $wpdb->prefix . 'aliexpress_ads_events';

        $sql = "CREATE TABLE {$events_table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            banner_id INT NOT NULL,
            event_type VARCHAR(20) NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            referrer TEXT,
            page_url TEXT,
            created_at DATETIME NOT NULL,
            INDEX banner_idx (banner_id),
            INDEX event_type_idx (event_type),
            INDEX created_at_idx (created_at)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Obtener IP del cliente
     * @return string
     */
    private static function get_client_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    
                    // Validar IP
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Obtener estadísticas de un banner específico
     * @param int $banner_id
     * @param array $args
     * @return array
     */
    public static function get_banner_stats($banner_id, $args = array()) {
        global $wpdb;
        
        $defaults = array(
            'period' => '30', // días
            'include_events' => false
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $table_name = $wpdb->prefix . 'aliexpress_ads';
        
        // Estadísticas básicas
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT impressions, clicks FROM {$table_name} WHERE id = %d",
            $banner_id
        ));

        if (!$stats) {
            return array(
                'impressions' => 0,
                'clicks' => 0,
                'ctr' => 0,
                'events' => array()
            );
        }

        $ctr = $stats->impressions > 0 ? ($stats->clicks / $stats->impressions) * 100 : 0;

        $result = array(
            'impressions' => intval($stats->impressions),
            'clicks' => intval($stats->clicks),
            'ctr' => round($ctr, 2),
            'events' => array()
        );

        // Eventos detallados si se solicitan
        if ($args['include_events']) {
            $events_table = $wpdb->prefix . 'aliexpress_ads_events';
            
            if ($wpdb->get_var("SHOW TABLES LIKE '{$events_table}'") === $events_table) {
                $date_limit = date('Y-m-d H:i:s', strtotime("-{$args['period']} days"));
                
                $events = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$events_table} 
                     WHERE banner_id = %d AND created_at >= %s 
                     ORDER BY created_at DESC 
                     LIMIT 100",
                    $banner_id,
                    $date_limit
                ));
                
                $result['events'] = $events ?: array();
            }
        }

        return $result;
    }

    /**
     * Obtener estadísticas generales por período
     * @param string $period
     * @return array
     */
    public static function get_period_stats($period = '30') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aliexpress_ads';
        $events_table = $wpdb->prefix . 'aliexpress_ads_events';
        
        // Si no hay tabla de eventos, usar estadísticas básicas
        if ($wpdb->get_var("SHOW TABLES LIKE '{$events_table}'") !== $events_table) {
            return Ali_Banner::get_stats();
        }

        $date_limit = date('Y-m-d H:i:s', strtotime("-{$period} days"));
        
        // Clics por período
        $period_clicks = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$events_table} 
             WHERE event_type = 'click' AND created_at >= %s",
            $date_limit
        ));

        // Estadísticas totales
        $total_stats = Ali_Banner::get_stats();
        
        return array(
            'total_banners' => $total_stats['total_banners'],
            'active_banners' => $total_stats['active_banners'],
            'total_impressions' => $total_stats['total_impressions'],
            'total_clicks' => $total_stats['total_clicks'],
            'period_clicks' => intval($period_clicks),
            'ctr' => $total_stats['ctr'],
            'period' => $period
        );
    }

    /**
     * Obtener los banners más performantes
     * @param int $limit
     * @return array
     */
    public static function get_top_performing_banners($limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aliexpress_ads';
        
        $banners = $wpdb->get_results($wpdb->prepare(
            "SELECT id, title, impressions, clicks, 
                    CASE 
                        WHEN impressions > 0 THEN (clicks / impressions) * 100 
                        ELSE 0 
                    END as ctr
             FROM {$table_name} 
             WHERE active = 1 AND impressions > 0
             ORDER BY ctr DESC, clicks DESC
             LIMIT %d",
            $limit
        ));

        return $banners ?: array();
    }

    /**
     * Obtener estadísticas por categoría
     * @return array
     */
    public static function get_stats_by_category() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aliexpress_ads';
        
        $stats = $wpdb->get_results(
            "SELECT 
                CASE 
                    WHEN category = '' OR category IS NULL THEN 'general' 
                    ELSE category 
                END as category,
                COUNT(*) as total_banners,
                SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) as active_banners,
                SUM(impressions) as total_impressions,
                SUM(clicks) as total_clicks,
                CASE 
                    WHEN SUM(impressions) > 0 THEN (SUM(clicks) / SUM(impressions)) * 100 
                    ELSE 0 
                END as ctr
             FROM {$table_name}
             GROUP BY category
             ORDER BY total_impressions DESC"
        );

        $result = array();
        
        foreach ($stats as $stat) {
            $result[$stat->category] = array(
                'total_banners' => intval($stat->total_banners),
                'active_banners' => intval($stat->active_banners),
                'total_impressions' => intval($stat->total_impressions),
                'total_clicks' => intval($stat->total_clicks),
                'ctr' => round($stat->ctr, 2)
            );
        }

        return $result;
    }

    /**
     * Programar limpieza automática de datos antiguos
     */
    public function maybe_schedule_cleanup() {
        if (!wp_next_scheduled('ali_ads_cleanup_old_events')) {
            wp_schedule_event(time(), 'weekly', 'ali_ads_cleanup_old_events');
        }
    }

    /**
     * Limpiar eventos antiguos
     */
    public static function cleanup_old_events() {
        global $wpdb;
        
        $events_table = $wpdb->prefix . 'aliexpress_ads_events';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$events_table}'") !== $events_table) {
            return;
        }

        // Eliminar eventos más antiguos de 90 días
        $date_limit = date('Y-m-d H:i:s', strtotime('-90 days'));
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$events_table} WHERE created_at < %s",
            $date_limit
        ));

        if ($deleted !== false) {
            ali_ads_log("Cleaned up {$deleted} old event records", 'info');
        }
    }

    /**
     * Exportar estadísticas a CSV
     * @param array $args
     * @return string|false
     */
    public static function export_stats_csv($args = array()) {
        $defaults = array(
            'period' => '30',
            'include_events' => false
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Obtener datos
        $banners = Ali_Banner::get_banners(array('active_only' => false));
        
        if (empty($banners)) {
            return false;
        }

        // Crear CSV
        $csv_data = array();
        $csv_data[] = array(
            'ID',
            'Título',
            'Categoría',
            'Ubicación',
            'Estado',
            'Impresiones',
            'Clics',
            'CTR (%)',
            'Fecha Creación'
        );

        foreach ($banners as $banner) {
            $stats = self::get_banner_stats($banner->id);
            
            $csv_data[] = array(
                $banner->id,
                $banner->title,
                $banner->category ?: 'General',
                $banner->placement,
                $banner->active ? 'Activo' : 'Inactivo',
                $stats['impressions'],
                $stats['clicks'],
                $stats['ctr'],
                $banner->created_at ?? ''
            );
        }

        // Generar contenido CSV
        $output = '';
        foreach ($csv_data as $row) {
            $output .= '"' . implode('","', $row) . '"' . "\n";
        }

        return $output;
    }

    /**
     * Resetear estadísticas de un banner
     * @param int $banner_id
     * @return bool
     */
    public static function reset_banner_stats($banner_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aliexpress_ads';
        
        $result = $wpdb->update(
            $table_name,
            array(
                'impressions' => 0,
                'clicks' => 0
            ),
            array('id' => $banner_id),
            array('%d', '%d'),
            array('%d')
        );

        // También eliminar eventos relacionados
        $events_table = $wpdb->prefix . 'aliexpress_ads_events';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$events_table}'") === $events_table) {
            $wpdb->delete(
                $events_table,
                array('banner_id' => $banner_id),
                array('%d')
            );
        }

        return $result !== false;
    }
}

// Hook para limpieza automática
add_action('ali_ads_cleanup_old_events', array('Ali_Stats', 'cleanup_old_events'));