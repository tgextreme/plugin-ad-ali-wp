<?php
/**
 * Sistema automático de ads tipo AdSense para AliExpress
 * 
 * @package AliExpress_Smart_Ads
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class Ali_Auto_Ads {

    /**
     * Configuración de ads automáticos
     * @var array
     */
    private $auto_config;

    /**
     * Constructor
     */
    public function __construct() {
        $this->auto_config = get_option('ali_ads_auto_config', $this->get_default_auto_config());
        
        // Debug temporal - mostrar configuración actual
        error_log('� Ali_Auto_Ads constructor ejecutado - Enabled: ' . ($this->auto_config['enabled'] ? 'SÍ' : 'NO'));
        
        if (current_user_can('administrator')) {
            add_action('wp_footer', array($this, 'debug_auto_ads_status'), 999);
        }
        
        $this->init_hooks();
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        if (!$this->is_auto_ads_enabled()) {
            return;
        }

        // Hooks para inserción automática inteligente
        add_filter('the_content', array($this, 'auto_insert_in_content'), 20);
        add_action('wp_footer', array($this, 'auto_insert_sticky_ads'));
        add_action('loop_start', array($this, 'maybe_insert_in_loop'));
        add_action('wp_head', array($this, 'add_auto_ads_styles'));
        
        // Ads en sidebar automáticamente
        add_action('dynamic_sidebar_before', array($this, 'maybe_insert_sidebar_ad'));
        
        // Analytics y optimización
        add_action('wp_ajax_ali_track_auto_ad_performance', array($this, 'track_auto_ad_performance'));
        add_action('wp_ajax_nopriv_ali_track_auto_ad_performance', array($this, 'track_auto_ad_performance'));
    }

    /**
     * Verificar si los ads automáticos están habilitados
     * @return bool
     */
    private function is_auto_ads_enabled() {
        // Debug temporal - forzar activación para admin
        if (current_user_can('administrator')) {
            error_log('🔍 is_auto_ads_enabled: ' . (!empty($this->auto_config['enabled']) ? 'TRUE' : 'FALSE'));
            // return true; // Descomenta esta línea para forzar activación temporal
        }
        
        return !empty($this->auto_config['enabled']);
    }

    /**
     * Configuración por defecto de ads automáticos
     * @return array
     */
    private function get_default_auto_config() {
        return array(
            'enabled' => false,
            'density' => 'medium', // low, medium, high
            'content_ads' => array(
                'enabled' => true,
                'min_paragraphs' => 3,
                'frequency' => 3, // cada X párrafos
                'max_per_post' => 3
            ),
            'sidebar_ads' => array(
                'enabled' => true,
                'position' => 'top', // top, middle, bottom
                'auto_size' => true
            ),
            'sticky_ads' => array(
                'enabled' => true,
                'position' => 'bottom', // bottom, top, side
                'delay' => 3000, // 3 segundos
                'auto_hide' => true
            ),
            'loop_ads' => array(
                'enabled' => true,
                'frequency' => 5, // cada 5 posts en listados
                'categories' => array() // categorías específicas
            ),
            'responsive_sizes' => array(
                'mobile' => array(320, 50, 320, 100, 300, 250),
                'tablet' => array(728, 90, 300, 250, 320, 480),
                'desktop' => array(728, 90, 300, 250, 320, 480, 970, 250)
            ),
            'optimization' => array(
                'enabled' => true,
                'learning_period' => 7, // días
                'auto_optimize_positions' => true
            )
        );
    }

    /**
     * Inserción automática en contenido
     * @param string $content
     * @return string
     */
    public function auto_insert_in_content($content) {
        // Debug temporal
        if (current_user_can('administrator')) {
            error_log('🤖 Auto Ads: auto_insert_in_content ejecutado - is_singular: ' . (is_singular('post') ? 'SÍ' : 'NO') . ' | in_loop: ' . (in_the_loop() ? 'SÍ' : 'NO') . ' | main_query: ' . (is_main_query() ? 'SÍ' : 'NO'));
        }
        
        if (!is_singular('post') || !in_the_loop() || !is_main_query()) {
            return $content;
        }

        $config = $this->auto_config['content_ads'];
        
        if (!$config['enabled']) {
            if (current_user_can('administrator')) {
                error_log('🤖 Auto Ads: content_ads no está habilitado');
            }
            return $content;
        }

        // Contar párrafos
        $paragraphs = $this->extract_paragraphs($content);
        
        if (count($paragraphs) < $config['min_paragraphs']) {
            return $content;
        }

        // PRUEBA SIMPLE: Añadir mensaje siempre que esté activado
        $content .= '<div style="border: 3px solid #00a32a; padding: 20px; margin: 20px 0; text-align: center; background: #f0fff0;">';
        $content .= '<h3 style="margin: 0 0 10px 0; color: #00a32a;">✅ AUTO ADS ACTIVO</h3>';
        
        // Verificar si hay banners disponibles
        $test_banner = $this->get_contextual_banner('');
        if ($test_banner) {
            $content .= '<p style="color: #00a32a; margin: 5px 0;">📋 Banner encontrado: ' . esc_html($test_banner->title) . '</p>';
            
            if (!empty($test_banner->iframe_code)) {
                $content .= '<div style="margin: 10px 0;">' . $test_banner->iframe_code . '</div>';
            } else if (!empty($test_banner->image_url)) {
                $click_url = !empty($test_banner->click_url) ? $test_banner->click_url : '#';
                $content .= '<div style="margin: 10px 0;">';
                $content .= '<a href="' . esc_url($click_url) . '" target="_blank">';
                $content .= '<img src="' . esc_url($test_banner->image_url) . '" alt="' . esc_attr($test_banner->title) . '" style="max-width: 300px; height: auto; border: 1px solid #ddd;">';
                $content .= '</a>';
                $content .= '</div>';
            }
        } else {
            $content .= '<p style="color: red; margin: 5px 0;">❌ No hay banners configurados</p>';
            $content .= '<p style="color: #666; font-size: 12px; margin: 5px 0;">Configura banners en "Gestión de Banners" o "Código HTML"</p>';
        }
        
        $content .= '</div>';
        
        // Calcular posiciones óptimas
        $positions = $this->calculate_optimal_positions($paragraphs, $config);
        
        // Insertar ads en las posiciones calculadas
        return $this->insert_ads_at_positions($content, $positions);
    }

    /**
     * Extraer párrafos del contenido
     * @param string $content
     * @return array
     */
    private function extract_paragraphs($content) {
        // Dividir por párrafos y elementos de bloque
        $patterns = array(
            '/<p[^>]*>.*?<\/p>/s',
            '/<div[^>]*>.*?<\/div>/s',
            '/<h[1-6][^>]*>.*?<\/h[1-6]>/s'
        );
        
        $paragraphs = array();
        $offset = 0;
        
        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);
            
            foreach ($matches[0] as $match) {
                $paragraphs[] = array(
                    'content' => $match[0],
                    'position' => $match[1],
                    'length' => strlen($match[0]),
                    'type' => $this->detect_paragraph_type($match[0])
                );
            }
        }
        
        // Ordenar por posición
        usort($paragraphs, function($a, $b) {
            return $a['position'] - $b['position'];
        });
        
        return $paragraphs;
    }

    /**
     * Detectar tipo de párrafo
     * @param string $paragraph
     * @return string
     */
    private function detect_paragraph_type($paragraph) {
        if (preg_match('/<h[1-6]/', $paragraph)) {
            return 'heading';
        }
        
        if (preg_match('/<img/', $paragraph)) {
            return 'image';
        }
        
        if (strlen(strip_tags($paragraph)) < 50) {
            return 'short';
        }
        
        return 'text';
    }

    /**
     * Calcular posiciones óptimas para ads
     * @param array $paragraphs
     * @param array $config
     * @return array
     */
    private function calculate_optimal_positions($paragraphs, $config) {
        $positions = array();
        $paragraph_count = count($paragraphs);
        $max_ads = min($config['max_per_post'], floor($paragraph_count / $config['frequency']));
        
        // Algoritmo inteligente de posicionamiento
        for ($i = 0; $i < $max_ads; $i++) {
            $optimal_position = $this->find_optimal_position($paragraphs, $positions, $i);
            
            if ($optimal_position !== false) {
                $positions[] = array(
                    'after_paragraph' => $optimal_position,
                    'ad_type' => $this->determine_ad_type($paragraphs, $optimal_position),
                    'priority' => $this->calculate_priority($paragraphs, $optimal_position)
                );
            }
        }
        
        // Ordenar por prioridad
        usort($positions, function($a, $b) {
            return $b['priority'] - $a['priority'];
        });
        
        return $positions;
    }

    /**
     * Encontrar posición óptima para ad
     * @param array $paragraphs
     * @param array $existing_positions
     * @param int $ad_index
     * @return int|false
     */
    private function find_optimal_position($paragraphs, $existing_positions, $ad_index) {
        $paragraph_count = count($paragraphs);
        $frequency = $this->auto_config['content_ads']['frequency'];
        
        // Posición base según frecuencia
        $base_position = ($ad_index + 1) * $frequency;
        
        // Buscar mejor posición cerca de la base
        $search_range = 2;
        $best_position = false;
        $best_score = 0;
        
        for ($i = max(1, $base_position - $search_range); 
             $i <= min($paragraph_count - 1, $base_position + $search_range); 
             $i++) {
            
            // Evitar posiciones ya ocupadas
            if ($this->position_already_used($i, $existing_positions)) {
                continue;
            }
            
            $score = $this->calculate_position_score($paragraphs, $i);
            
            if ($score > $best_score) {
                $best_score = $score;
                $best_position = $i;
            }
        }
        
        return $best_position;
    }

    /**
     * Calcular puntuación de una posición
     * @param array $paragraphs
     * @param int $position
     * @return float
     */
    private function calculate_position_score($paragraphs, $position) {
        $score = 100; // Puntuación base
        
        // Penalizar posiciones muy arriba o muy abajo
        $total_paragraphs = count($paragraphs);
        $relative_position = $position / $total_paragraphs;
        
        if ($relative_position < 0.2 || $relative_position > 0.8) {
            $score -= 20;
        }
        
        // Bonificar después de párrafos largos de texto
        if (isset($paragraphs[$position - 1])) {
            $prev_paragraph = $paragraphs[$position - 1];
            
            if ($prev_paragraph['type'] === 'text' && $prev_paragraph['length'] > 200) {
                $score += 15;
            }
            
            if ($prev_paragraph['type'] === 'heading') {
                $score -= 10; // No inmediatamente después de títulos
            }
        }
        
        // Bonificar antes de párrafos importantes
        if (isset($paragraphs[$position])) {
            $next_paragraph = $paragraphs[$position];
            
            if ($next_paragraph['type'] === 'text' && $next_paragraph['length'] > 150) {
                $score += 10;
            }
        }
        
        // Usar datos históricos si están disponibles
        $historical_score = $this->get_historical_performance_score($position, $total_paragraphs);
        $score += $historical_score * 0.3; // 30% peso a datos históricos
        
        return $score;
    }

    /**
     * Obtener puntuación histórica de rendimiento
     * @param int $position
     * @param int $total_paragraphs
     * @return float
     */
    private function get_historical_performance_score($position, $total_paragraphs) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aliexpress_ads_auto_performance';
        
        // Verificar si existe la tabla
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
            $this->create_performance_table();
            return 0;
        }
        
        $relative_position = round(($position / $total_paragraphs) * 10) / 10; // Redondear a décimas
        
        $performance = $wpdb->get_row($wpdb->prepare(
            "SELECT avg_ctr, impressions FROM {$table_name} 
             WHERE position_percentage BETWEEN %f AND %f 
             AND impressions > 10
             ORDER BY impressions DESC
             LIMIT 1",
            $relative_position - 0.05,
            $relative_position + 0.05
        ));
        
        if ($performance) {
            // Convertir CTR a puntuación (0-50 puntos adicionales)
            return min(50, $performance->avg_ctr * 1000);
        }
        
        return 0;
    }

    /**
     * Crear tabla de rendimiento
     */
    private function create_performance_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aliexpress_ads_auto_performance';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$table_name} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            position_percentage DECIMAL(3,2),
            avg_ctr DECIMAL(5,4),
            impressions INT,
            clicks INT,
            last_updated DATETIME,
            INDEX pos_idx (position_percentage),
            INDEX ctr_idx (avg_ctr)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Verificar si posición ya está ocupada
     * @param int $position
     * @param array $existing_positions
     * @return bool
     */
    private function position_already_used($position, $existing_positions) {
        foreach ($existing_positions as $existing) {
            if (abs($existing['after_paragraph'] - $position) < 2) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determinar tipo de ad según contexto
     * @param array $paragraphs
     * @param int $position
     * @return string
     */
    private function determine_ad_type($paragraphs, $position) {
        // Analizar contenido para sugerir categoría de producto
        $context = '';
        
        // Tomar párrafos cercanos para análisis
        for ($i = max(0, $position - 2); $i < min(count($paragraphs), $position + 2); $i++) {
            $context .= strip_tags($paragraphs[$i]['content']) . ' ';
        }
        
        return $this->analyze_content_for_ad_type($context);
    }

    /**
     * Analizar contenido para tipo de ad
     * @param string $context
     * @return string
     */
    private function analyze_content_for_ad_type($context) {
        $context = strtolower($context);
        
        $categories = array(
            'electronics' => array('phone', 'smartphone', 'laptop', 'computer', 'tablet', 'headphones', 'electronic'),
            'fashion' => array('dress', 'clothes', 'fashion', 'shirt', 'shoes', 'bag', 'jewelry'),
            'home' => array('home', 'kitchen', 'furniture', 'decor', 'garden', 'tools'),
            'sports' => array('sport', 'fitness', 'gym', 'running', 'exercise', 'outdoor'),
            'beauty' => array('beauty', 'makeup', 'cosmetic', 'skin', 'hair', 'care')
        );
        
        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($context, $keyword) !== false) {
                    return $category;
                }
            }
        }
        
        return 'general';
    }

    /**
     * Calcular prioridad del ad
     * @param array $paragraphs
     * @param int $position
     * @return float
     */
    private function calculate_priority($paragraphs, $position) {
        $priority = 50; // Base
        
        // Mayor prioridad en posiciones medias
        $relative_pos = $position / count($paragraphs);
        if ($relative_pos >= 0.3 && $relative_pos <= 0.7) {
            $priority += 20;
        }
        
        // Bonificar según longitud del contenido previo
        $content_length = 0;
        for ($i = 0; $i < $position; $i++) {
            $content_length += $paragraphs[$i]['length'];
        }
        
        if ($content_length > 500) {
            $priority += 15;
        }
        
        return $priority;
    }

    /**
     * Insertar ads en las posiciones calculadas
     * @param string $content
     * @param array $positions
     * @return string
     */
    private function insert_ads_at_positions($content, $positions) {
        if (empty($positions)) {
            return $content;
        }

        $paragraphs = $this->extract_paragraphs($content);
        $offset = 0;
        
        foreach ($positions as $pos_data) {
            $position = $pos_data['after_paragraph'];
            
            if (!isset($paragraphs[$position - 1])) {
                continue;
            }
            
            // Buscar la posición en el contenido
            $search_content = $paragraphs[$position - 1]['content'];
            $pos_in_content = strpos($content, $search_content, $offset);
            
            if ($pos_in_content !== false) {
                $insert_pos = $pos_in_content + strlen($search_content);
                
                // Generar ad automático
                $ad_html = $this->generate_auto_ad_html($pos_data);
                
                // Insertar el ad
                $content = substr_replace($content, $ad_html, $insert_pos, 0);
                $offset = $insert_pos + strlen($ad_html);
            }
        }
        
        return $content;
    }

    /**
     * Generar HTML del ad automático
     * @param array $pos_data
     * @return string
     */
    private function generate_auto_ad_html($pos_data) {
        // Obtener banner apropiado para el contexto
        $banner = $this->get_contextual_banner($pos_data['ad_type']);
        
        if (!$banner) {
            return '';
        }
        
        // Determinar tamaño responsive
        $ad_size = $this->get_responsive_ad_size();
        
        $html = '<div class="ali-auto-ad ali-auto-ad-content" data-ad-type="' . esc_attr($pos_data['ad_type']) . '" data-priority="' . esc_attr($pos_data['priority']) . '">';
        $html .= '<div class="ali-auto-ad-label">Publicidad</div>';
        
        if (!empty($banner->iframe_code)) {
            $html .= $banner->iframe_code;
        } else {
            $click_url = ali_ads_get_click_url($banner->id);
            $html .= '<a href="' . esc_url($click_url) . '" target="_blank" rel="noopener nofollow">';
            $html .= '<img src="' . esc_url($banner->image_url) . '" alt="' . esc_attr($banner->title) . '" style="max-width:' . $ad_size['width'] . 'px; height:auto;">';
            $html .= '</a>';
        }
        
        $html .= '</div>';
        
        // Registrar impresión
        Ali_Stats::track_impression($banner->id);
        
        return $html;
    }

    /**
     * Obtener banner contextual
     * @param string $ad_type
     * @return object|null
     */
    private function get_contextual_banner($ad_type) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aliexpress_ads';
        
        // Estrategia mejorada: buscar cualquier banner activo que tengas configurado
        $sql = "SELECT * FROM {$table_name} WHERE is_active = 1";
        
        // Si hay un tipo específico, intentar primero con esa categoría
        if (!empty($ad_type)) {
            $category_sql = $sql . " AND category = %s ORDER BY RAND() LIMIT 1";
            $banner = $wpdb->get_row($wpdb->prepare($category_sql, $ad_type));
            
            if ($banner) {
                return $banner;
            }
        }
        
        // Si no hay banner específico, obtener cualquier banner activo
        $general_sql = $sql . " ORDER BY RAND() LIMIT 1";
        $banner = $wpdb->get_row($general_sql);
        
        return $banner;
    }

    /**
     * Obtener tamaño de ad responsive
     * @return array
     */
    private function get_responsive_ad_size() {
        $sizes = $this->auto_config['responsive_sizes'];
        
        // Detectar dispositivo (simplificado)
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (wp_is_mobile()) {
            if (strpos($user_agent, 'Tablet') !== false || strpos($user_agent, 'iPad') !== false) {
                $device_sizes = $sizes['tablet'];
            } else {
                $device_sizes = $sizes['mobile'];
            }
        } else {
            $device_sizes = $sizes['desktop'];
        }
        
        // Seleccionar tamaño apropiado (formato: width, height pairs)
        $size_index = 0; // Por defecto primer tamaño
        
        return array(
            'width' => $device_sizes[$size_index],
            'height' => $device_sizes[$size_index + 1] ?? 'auto'
        );
    }

    /**
     * Insertar ads sticky automáticamente
     */
    public function auto_insert_sticky_ads() {
        $config = $this->auto_config['sticky_ads'];
        
        if (!$config['enabled']) {
            return;
        }
        
        $banner = Ali_Banner::get_random_banner('', 'floating_bar');
        
        if (!$banner) {
            return;
        }
        
        $delay = $config['delay'];
        $position = $config['position'];
        
        ?>
        <div class="ali-auto-sticky-container" style="display:none;" 
             data-delay="<?php echo esc_attr($delay); ?>" 
             data-position="<?php echo esc_attr($position); ?>">
            
            <div class="ali-auto-sticky-ad ali-placement-floating_bar position-<?php echo esc_attr($position); ?>">
                <?php if ($config['auto_hide']): ?>
                    <button class="ali-close" onclick="aliAds.closeAutoSticky(this)">×</button>
                <?php endif; ?>
                
                <div class="ali-auto-ad-label">Publicidad</div>
                
                <?php if (!empty($banner->iframe_code)): ?>
                    <?php echo $banner->iframe_code; ?>
                <?php else: ?>
                    <a href="<?php echo esc_url(ali_ads_get_click_url($banner->id)); ?>" target="_blank" rel="noopener nofollow">
                        <img src="<?php echo esc_url($banner->image_url); ?>" alt="<?php echo esc_attr($banner->title); ?>">
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            setTimeout(function() {
                $('.ali-auto-sticky-container').fadeIn(500);
            }, <?php echo intval($delay); ?>);
        });
        </script>
        <?php
        
        // Registrar impresión
        Ali_Stats::track_impression($banner->id);
    }

    /**
     * Insertar ads en loop de posts
     * @param WP_Query $query
     */
    public function maybe_insert_in_loop($query) {
        if (!$query->is_main_query() || is_admin()) {
            return;
        }
        
        $config = $this->auto_config['loop_ads'];
        
        if (!$config['enabled']) {
            return;
        }
        
        // Solo en listados
        if (!is_home() && !is_category() && !is_archive()) {
            return;
        }
        
        add_action('loop_end', function() use ($config) {
            global $wp_query;
            
            $current_post = $wp_query->current_post + 1;
            
            if ($current_post % $config['frequency'] === 0) {
                $category = is_category() ? get_queried_object()->slug : '';
                $banner = Ali_Banner::get_random_banner($category, 'between_articles');
                
                if ($banner) {
                    echo '<div class="ali-auto-ad ali-auto-ad-loop">';
                    echo '<div class="ali-auto-ad-label">Publicidad</div>';
                    echo Ali_Display::render_banner($banner, 'between_articles');
                    echo '</div>';
                }
            }
        });
    }

    /**
     * Insertar ad en sidebar automáticamente
     * @param string $sidebar_id
     */
    public function maybe_insert_sidebar_ad($sidebar_id) {
        $config = $this->auto_config['sidebar_ads'];
        
        if (!$config['enabled']) {
            return;
        }
        
        // Solo en sidebar principal
        if ($sidebar_id !== 'sidebar-1' && $sidebar_id !== 'primary-sidebar') {
            return;
        }
        
        $banner = Ali_Banner::get_random_banner('', 'sidebar');
        
        if ($banner) {
            echo '<div class="ali-auto-ad ali-auto-ad-sidebar widget">';
            echo '<div class="ali-auto-ad-label">Publicidad</div>';
            echo Ali_Display::render_banner($banner, 'sidebar');
            echo '</div>';
        }
    }

    /**
     * Añadir estilos para ads automáticos
     */
    public function add_auto_ads_styles() {
        ?>
        <style>
        .ali-auto-ad {
            margin: 20px auto;
            text-align: center;
            clear: both;
            position: relative;
        }
        
        .ali-auto-ad-label {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
            text-align: center;
        }
        
        .ali-auto-ad-content {
            background: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 5px;
            padding: 15px;
            margin: 25px auto;
        }
        
        .ali-auto-ad-loop {
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
        }
        
        .ali-auto-ad-sidebar {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .ali-auto-sticky-ad {
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .ali-auto-sticky-ad.position-top {
            top: 0;
            border-radius: 0 0 10px 10px;
        }
        
        .ali-auto-sticky-ad.position-side {
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            border-radius: 10px;
        }
        
        @media (max-width: 768px) {
            .ali-auto-ad-content {
                margin: 15px auto;
                padding: 10px;
            }
            
            .ali-auto-sticky-ad.position-side {
                display: none;
            }
        }
        </style>
        <?php
    }

    /**
     * Tracking de rendimiento de ads automáticos
     */
    public function track_auto_ad_performance() {
        check_ajax_referer('ali_ads_nonce', 'nonce');
        
        $banner_id = intval($_POST['banner_id']);
        $position_percentage = floatval($_POST['position_percentage']);
        $event_type = sanitize_text_field($_POST['event_type']); // impression, click
        
        if ($banner_id && $position_percentage >= 0) {
            $this->update_performance_data($position_percentage, $event_type);
        }
        
        wp_die();
    }

    /**
     * Actualizar datos de rendimiento
     * @param float $position_percentage
     * @param string $event_type
     */
    private function update_performance_data($position_percentage, $event_type) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aliexpress_ads_auto_performance';
        
        // Redondear posición a décimas
        $rounded_position = round($position_percentage, 1);
        
        // Buscar registro existente
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE position_percentage = %f",
            $rounded_position
        ));
        
        if ($existing) {
            // Actualizar registro existente
            $new_impressions = $existing->impressions;
            $new_clicks = $existing->clicks;
            
            if ($event_type === 'impression') {
                $new_impressions++;
            } elseif ($event_type === 'click') {
                $new_clicks++;
            }
            
            $new_ctr = $new_impressions > 0 ? ($new_clicks / $new_impressions) : 0;
            
            $wpdb->update(
                $table_name,
                array(
                    'impressions' => $new_impressions,
                    'clicks' => $new_clicks,
                    'avg_ctr' => $new_ctr,
                    'last_updated' => current_time('mysql')
                ),
                array('id' => $existing->id),
                array('%d', '%d', '%f', '%s'),
                array('%d')
            );
        } else {
            // Crear nuevo registro
            $impressions = $event_type === 'impression' ? 1 : 0;
            $clicks = $event_type === 'click' ? 1 : 0;
            $ctr = $impressions > 0 ? ($clicks / $impressions) : 0;
            
            $wpdb->insert(
                $table_name,
                array(
                    'position_percentage' => $rounded_position,
                    'impressions' => $impressions,
                    'clicks' => $clicks,
                    'avg_ctr' => $ctr,
                    'last_updated' => current_time('mysql')
                ),
                array('%f', '%d', '%d', '%f', '%s')
            );
        }
    }

    /**
     * Obtener configuración actual
     * @return array
     */
    public function get_config() {
        return $this->auto_config;
    }

    /**
     * Actualizar configuración
     * @param array $config
     * @return bool
     */
    public function update_config($config) {
        $this->auto_config = wp_parse_args($config, $this->get_default_auto_config());
        return update_option('ali_ads_auto_config', $this->auto_config);
    }

    /**
     * Debug temporal - mostrar estado de auto ads
     */
    public function debug_auto_ads_status() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aliexpress_ads';
        $banner_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE is_active = 1");
        
        echo '
        <div style="position: fixed; bottom: 10px; right: 10px; background: #333; color: white; padding: 10px; border-radius: 5px; font-size: 12px; z-index: 9999;">
            <strong>🤖 Auto Ads Debug</strong><br>
            • Enabled: ' . ($this->auto_config['enabled'] ? '✅ SÍ' : '❌ NO') . '<br>
            • Banners: ' . $banner_count . '<br>
            • Page: ' . (is_singular('post') ? 'POST' : 'OTHER') . '<br>
            • In Loop: ' . (in_the_loop() ? 'SÍ' : 'NO') . '<br>
        </div>';
    }
}