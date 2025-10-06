<?php
/**
 * Clase modelo para gestión de banners de AliExpress
 *
 * @package AliExpress_Smart_Ads
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class Ali_Banner {

    /**
     * Nombre de la tabla
     * @var string
     */
    private static $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'aliexpress_ads';
    }

    /**
     * Obtener un banner por ID
     * @param int $id
     * @return object|null
     */
    public static function get_banner($id) {
        global $wpdb;
        
        if (empty(self::$table_name)) {
            self::$table_name = $wpdb->prefix . 'aliexpress_ads';
        }
        
        $banner = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::$table_name . " WHERE id = %d AND active = 1",
            $id
        ));
        
        return $banner;
    }

    /**
     * Obtener todos los banners
     * @param array $args
     * @return array
     */
    public static function get_banners($args = array()) {
        global $wpdb;
        
        if (empty(self::$table_name)) {
            self::$table_name = $wpdb->prefix . 'aliexpress_ads';
        }
        
        $defaults = array(
            'active_only' => true,
            'category' => '',
            'placement' => '',
            'limit' => 0,
            'orderby' => 'id',
            'order' => 'DESC',
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_clauses = array();
        $where_values = array();
        
        // Solo banners activos
        if ($args['active_only']) {
            $where_clauses[] = "active = 1";
        }
        
        // Filtrar por categoría
        if (!empty($args['category'])) {
            $where_clauses[] = "(category = %s OR category = '' OR category = 'general')";
            $where_values[] = $args['category'];
        }
        
        // Filtrar por ubicación
        if (!empty($args['placement'])) {
            $where_clauses[] = "placement = %s";
            $where_values[] = $args['placement'];
        }
        
        // Construir consulta
        $sql = "SELECT * FROM " . self::$table_name;
        
        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        // Ordenamiento
        $allowed_orderby = array('id', 'title', 'impressions', 'clicks', 'created_at');
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'id';
        $order = in_array(strtoupper($args['order']), array('ASC', 'DESC')) ? strtoupper($args['order']) : 'DESC';
        
        $sql .= " ORDER BY {$orderby} {$order}";
        
        // Límite
        if ($args['limit'] > 0) {
            $sql .= $wpdb->prepare(" LIMIT %d", $args['limit']);
            
            if ($args['offset'] > 0) {
                $sql .= $wpdb->prepare(" OFFSET %d", $args['offset']);
            }
        }
        
        // Preparar consulta
        if (!empty($where_values)) {
            $banners = $wpdb->get_results($wpdb->prepare($sql, $where_values));
        } else {
            $banners = $wpdb->get_results($sql);
        }
        
        return $banners ? $banners : array();
    }

    /**
     * Obtener banner aleatorio según criterios
     * @param string $category
     * @param string $placement
     * @return object|null
     */
    public static function get_random_banner($category = '', $placement = '') {
        $args = array(
            'active_only' => true,
            'orderby' => 'RAND()',
            'limit' => 1
        );
        
        if (!empty($category)) {
            $args['category'] = $category;
        }
        
        if (!empty($placement)) {
            $args['placement'] = $placement;
        }
        
        $banners = self::get_banners($args);
        
        return !empty($banners) ? $banners[0] : null;
    }

    /**
     * Obtener banner con menos impresiones
     * @param string $category
     * @param string $placement
     * @return object|null
     */
    public static function get_least_viewed_banner($category = '', $placement = '') {
        $args = array(
            'active_only' => true,
            'orderby' => 'impressions',
            'order' => 'ASC',
            'limit' => 1
        );
        
        if (!empty($category)) {
            $args['category'] = $category;
        }
        
        if (!empty($placement)) {
            $args['placement'] = $placement;
        }
        
        $banners = self::get_banners($args);
        
        return !empty($banners) ? $banners[0] : null;
    }

    /**
     * Crear nuevo banner
     * @param array $data
     * @return int|false ID del banner creado o false si error
     */
    public static function create_banner($data) {
        global $wpdb;
        
        if (empty(self::$table_name)) {
            self::$table_name = $wpdb->prefix . 'aliexpress_ads';
        }
        
        // Datos por defecto
        $defaults = array(
            'title' => '',
            'image_url' => '',
            'target_url' => '',
            'iframe_code' => '',
            'category' => '',
            'placement' => 'in_content',
            'active' => 1,
            'impressions' => 0,
            'clicks' => 0
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Validaciones
        if (empty($data['title'])) {
            return false;
        }
        
        if (empty($data['image_url']) && empty($data['iframe_code'])) {
            return false;
        }
        
        // Limpiar y validar datos
        $data['title'] = sanitize_text_field($data['title']);
        $data['image_url'] = ali_ads_clean_url($data['image_url']);
        $data['target_url'] = ali_ads_clean_url($data['target_url']);
        $data['iframe_code'] = ali_ads_sanitize_iframe($data['iframe_code']);
        $data['category'] = sanitize_text_field($data['category']);
        $data['placement'] = sanitize_text_field($data['placement']);
        $data['active'] = $data['active'] ? 1 : 0;
        
        // Insertar en base de datos
        $result = $wpdb->insert(
            self::$table_name,
            array(
                'title' => $data['title'],
                'image_url' => $data['image_url'],
                'target_url' => $data['target_url'],
                'iframe_code' => $data['iframe_code'],
                'category' => $data['category'],
                'placement' => $data['placement'],
                'active' => $data['active'],
                'impressions' => intval($data['impressions']),
                'clicks' => intval($data['clicks'])
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d')
        );
        
        if ($result === false) {
            ali_ads_log('Error creating banner: ' . $wpdb->last_error, 'error');
            return false;
        }
        
        return $wpdb->insert_id;
    }

    /**
     * Actualizar banner
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update_banner($id, $data) {
        global $wpdb;
        
        if (empty(self::$table_name)) {
            self::$table_name = $wpdb->prefix . 'aliexpress_ads';
        }
        
        // Verificar que el banner existe
        $banner = self::get_banner_raw($id);
        if (!$banner) {
            return false;
        }
        
        // Campos permitidos para actualizar
        $allowed_fields = array(
            'title', 'image_url', 'target_url', 'iframe_code', 
            'category', 'placement', 'active'
        );
        
        $update_data = array();
        $update_format = array();
        
        foreach ($allowed_fields as $field) {
            if (array_key_exists($field, $data)) {
                switch ($field) {
                    case 'title':
                    case 'category':
                    case 'placement':
                        $update_data[$field] = sanitize_text_field($data[$field]);
                        $update_format[] = '%s';
                        break;
                    
                    case 'image_url':
                    case 'target_url':
                        $update_data[$field] = ali_ads_clean_url($data[$field]);
                        $update_format[] = '%s';
                        break;
                    
                    case 'iframe_code':
                        $update_data[$field] = ali_ads_sanitize_iframe($data[$field]);
                        $update_format[] = '%s';
                        break;
                    
                    case 'active':
                        $update_data[$field] = $data[$field] ? 1 : 0;
                        $update_format[] = '%d';
                        break;
                }
            }
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        $result = $wpdb->update(
            self::$table_name,
            $update_data,
            array('id' => $id),
            $update_format,
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Eliminar banner
     * @param int $id
     * @return bool
     */
    public static function delete_banner($id) {
        global $wpdb;
        
        if (empty(self::$table_name)) {
            self::$table_name = $wpdb->prefix . 'aliexpress_ads';
        }
        
        $result = $wpdb->delete(
            self::$table_name,
            array('id' => $id),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Obtener banner sin filtros (para admin)
     * @param int $id
     * @return object|null
     */
    public static function get_banner_raw($id) {
        global $wpdb;
        
        if (empty(self::$table_name)) {
            self::$table_name = $wpdb->prefix . 'aliexpress_ads';
        }
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::$table_name . " WHERE id = %d",
            $id
        ));
    }

    /**
     * Contar banners
     * @param array $args
     * @return int
     */
    public static function count_banners($args = array()) {
        global $wpdb;
        
        if (empty(self::$table_name)) {
            self::$table_name = $wpdb->prefix . 'aliexpress_ads';
        }
        
        $defaults = array(
            'active_only' => false,
            'category' => '',
            'placement' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_clauses = array();
        $where_values = array();
        
        if ($args['active_only']) {
            $where_clauses[] = "active = 1";
        }
        
        if (!empty($args['category'])) {
            $where_clauses[] = "category = %s";
            $where_values[] = $args['category'];
        }
        
        if (!empty($args['placement'])) {
            $where_clauses[] = "placement = %s";
            $where_values[] = $args['placement'];
        }
        
        $sql = "SELECT COUNT(*) FROM " . self::$table_name;
        
        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        if (!empty($where_values)) {
            return intval($wpdb->get_var($wpdb->prepare($sql, $where_values)));
        } else {
            return intval($wpdb->get_var($sql));
        }
    }

    /**
     * Obtener estadísticas generales
     * @return array
     */
    public static function get_stats() {
        global $wpdb;
        
        if (empty(self::$table_name)) {
            self::$table_name = $wpdb->prefix . 'aliexpress_ads';
        }
        
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_banners,
                SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) as active_banners,
                SUM(impressions) as total_impressions,
                SUM(clicks) as total_clicks
            FROM " . self::$table_name
        );
        
        if (!$stats) {
            return array(
                'total_banners' => 0,
                'active_banners' => 0,
                'total_impressions' => 0,
                'total_clicks' => 0,
                'ctr' => 0
            );
        }
        
        // Calcular CTR
        $ctr = 0;
        if ($stats->total_impressions > 0) {
            $ctr = ($stats->total_clicks / $stats->total_impressions) * 100;
        }
        
        return array(
            'total_banners' => intval($stats->total_banners),
            'active_banners' => intval($stats->active_banners),
            'total_impressions' => intval($stats->total_impressions),
            'total_clicks' => intval($stats->total_clicks),
            'ctr' => round($ctr, 2)
        );
    }

    /**
     * Activar/desactivar banner
     * @param int $id
     * @param bool $active
     * @return bool
     */
    public static function toggle_banner($id, $active) {
        return self::update_banner($id, array('active' => $active));
    }

    /**
     * Duplicar banner
     * @param int $id
     * @return int|false
     */
    public static function duplicate_banner($id) {
        $banner = self::get_banner_raw($id);
        
        if (!$banner) {
            return false;
        }
        
        $data = array(
            'title' => $banner->title . ' (Copia)',
            'image_url' => $banner->image_url,
            'target_url' => $banner->target_url,
            'iframe_code' => $banner->iframe_code,
            'category' => $banner->category,
            'placement' => $banner->placement,
            'active' => 0 // Crear como inactivo
        );
        
        return self::create_banner($data);
    }
}