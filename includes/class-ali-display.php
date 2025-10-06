<?php
/**
 * Clase de visualización y inserción automática de banners
 *
 * @package AliExpress_Smart_Ads
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class Ali_Display {

    /**
     * Contador de banners mostrados en la página actual
     * @var int
     */
    private static $banners_shown = 0;

    /**
     * Constructor
     */
    public function __construct() {
        $this->setup_hooks();
    }

    /**
     * Configurar hooks de WordPress
     */
    private function setup_hooks() {
        // Solo mostrar en frontend
        if (is_admin()) {
            return;
        }

        // Hook principal para inserción automática
        add_action('wp', array($this, 'init_auto_insertion'));

        // Widget personalizado
        add_action('widgets_init', array($this, 'register_widget'));
    }

    /**
     * Inicializar inserción automática basada en configuración
     */
    public function init_auto_insertion() {
        if (!ali_ads_should_show_banners()) {
            return;
        }

        $options = ali_ads_get_option();

        // Header
        if (!empty($options['auto_insert_header'])) {
            add_action('wp_head', array($this, 'insert_header_banner'));
        }

        // Footer
        if (!empty($options['auto_insert_footer'])) {
            add_action('wp_footer', array($this, 'insert_footer_banner'));
        }

        // Contenido de posts
        if (!empty($options['auto_insert_content'])) {
            add_filter('the_content', array($this, 'insert_content_banner'));
        }

        // Barra flotante
        if (!empty($options['auto_insert_floating'])) {
            add_action('wp_footer', array($this, 'insert_floating_banner'));
        }

        // Entre posts en listados
        if (!empty($options['auto_insert_between_posts'])) {
            add_action('loop_end', array($this, 'insert_between_posts_banner'));
        }
    }

    /**
     * Insertar banner en header
     */
    public function insert_header_banner() {
        if (!$this->can_show_more_banners()) {
            return;
        }

        $category = ali_ads_get_current_post_category();
        $banner = Ali_Banner::get_random_banner($category, 'header');

        if ($banner) {
            echo $this->render_banner($banner, 'header');
            self::$banners_shown++;
        }
    }

    /**
     * Insertar banner en footer
     */
    public function insert_footer_banner() {
        if (!$this->can_show_more_banners()) {
            return;
        }

        $category = ali_ads_get_current_post_category();
        $banner = Ali_Banner::get_random_banner($category, 'footer');

        if ($banner) {
            echo $this->render_banner($banner, 'footer');
            self::$banners_shown++;
        }
    }

    /**
     * Insertar banner en contenido
     * @param string $content
     * @return string
     */
    public function insert_content_banner($content) {
        // Solo en posts singulares
        if (!is_singular('post') || !in_the_loop() || !is_main_query()) {
            return $content;
        }

        if (!$this->can_show_more_banners()) {
            return $content;
        }

        $category = ali_ads_get_current_post_category();
        $banner = Ali_Banner::get_random_banner($category, 'in_content');

        if (!$banner) {
            return $content;
        }

        $position = ali_ads_get_option('content_position', 'after_first_paragraph');
        $banner_html = $this->render_banner($banner, 'in_content');

        switch ($position) {
            case 'after_first_paragraph':
                $content = $this->insert_after_paragraph($content, 1, $banner_html);
                break;

            case 'after_second_paragraph':
                $content = $this->insert_after_paragraph($content, 2, $banner_html);
                break;

            case 'middle_content':
                $content = $this->insert_in_middle($content, $banner_html);
                break;

            case 'end_content':
                $content .= $banner_html;
                break;

            default:
                $content = $this->insert_after_paragraph($content, 1, $banner_html);
        }

        self::$banners_shown++;
        return $content;
    }

    /**
     * Insertar barra flotante
     */
    public function insert_floating_banner() {
        if (!$this->can_show_more_banners()) {
            return;
        }

        $category = ali_ads_get_current_post_category();
        $banner = Ali_Banner::get_random_banner($category, 'floating_bar');

        if ($banner) {
            echo $this->render_banner($banner, 'floating_bar');
            self::$banners_shown++;
        }
    }

    /**
     * Insertar banner entre posts
     */
    public function insert_between_posts_banner() {
        global $wp_query;

        if (!is_home() && !is_category() && !is_archive()) {
            return;
        }

        if (!$this->can_show_more_banners()) {
            return;
        }

        $interval = ali_ads_get_option('between_posts_interval', 3);
        $current_post = $wp_query->current_post + 1;

        // Mostrar cada X posts
        if ($current_post % $interval === 0) {
            $category = is_category() ? get_queried_object()->slug : '';
            $banner = Ali_Banner::get_random_banner($category, 'between_articles');

            if ($banner) {
                echo $this->render_banner($banner, 'between_articles');
                self::$banners_shown++;
            }
        }
    }

    /**
     * Renderizar HTML del banner
     * @param object $banner
     * @param string $placement
     * @return string
     */
    public static function render_banner($banner, $placement = '') {
        if (!$banner) {
            return '';
        }

        // Registrar impresión
        Ali_Stats::track_impression($banner->id);

        // Determinar si usar iframe o imagen
        $use_iframe = !empty($banner->iframe_code);
        
        // Generar HTML
        $html = '<div class="ali-banner ali-placement-' . esc_attr($placement) . '" data-banner-id="' . esc_attr($banner->id) . '">';
        
        if ($use_iframe) {
            $html .= $banner->iframe_code;
        } else {
            $click_url = ali_ads_get_click_url($banner->id);
            $html .= '<a href="' . esc_url($click_url) . '" target="_blank" rel="noopener nofollow" class="ali-banner-link">';
            $html .= '<img src="' . esc_url($banner->image_url) . '" alt="' . esc_attr($banner->title) . '" class="ali-banner-image">';
            $html .= '</a>';
        }

        // Botón de cierre para barra flotante
        if ($placement === 'floating_bar') {
            $html .= '<button class="ali-close" onclick="aliAds.closeFloatingBanner()" aria-label="' . esc_attr__('Cerrar', 'aliexpress-smart-ads') . '">×</button>';
        }

        $html .= '</div>';

        return apply_filters('ali_ads_banner_html', $html, $banner, $placement);
    }

    /**
     * Insertar banner después de un párrafo específico
     * @param string $content
     * @param int $paragraph_number
     * @param string $banner_html
     * @return string
     */
    private function insert_after_paragraph($content, $paragraph_number, $banner_html) {
        $split_content = ali_ads_split_content_by_paragraphs($content, $paragraph_number);
        
        if (!empty($split_content['after'])) {
            return $split_content['before'] . $banner_html . $split_content['after'];
        } else {
            // Si no hay suficientes párrafos, agregar al final
            return $content . $banner_html;
        }
    }

    /**
     * Insertar banner en medio del contenido
     * @param string $content
     * @param string $banner_html
     * @return string
     */
    private function insert_in_middle($content, $banner_html) {
        $paragraphs = preg_split('/(<p[^>]*>.*?<\/p>)/s', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        
        if (count($paragraphs) < 4) {
            return $content . $banner_html;
        }

        $middle_index = floor(count($paragraphs) / 2);
        
        // Insertar en la posición media
        array_splice($paragraphs, $middle_index, 0, $banner_html);
        
        return implode('', $paragraphs);
    }

    /**
     * Verificar si se pueden mostrar más banners
     * @return bool
     */
    private function can_show_more_banners() {
        $max_banners = ali_ads_get_option('max_banners_per_page', 3);
        return self::$banners_shown < $max_banners;
    }

    /**
     * Obtener banner por shortcode
     * @param array $atts
     * @return string
     */
    public static function shortcode_handler($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'placement' => 'in_content',
            'id' => ''
        ), $atts);

        // Si se especifica un ID, usar ese banner
        if (!empty($atts['id'])) {
            $banner = Ali_Banner::get_banner(intval($atts['id']));
        } else {
            $banner = Ali_Banner::get_random_banner($atts['category'], $atts['placement']);
        }

        if ($banner) {
            return self::render_banner($banner, $atts['placement']);
        }

        return '';
    }

    /**
     * Registrar widget personalizado
     */
    public function register_widget() {
        register_widget('Ali_Banner_Widget');
    }

    /**
     * Obtener banners para sidebar automático
     * @param string $sidebar_id
     * @return string
     */
    public static function get_sidebar_banner($sidebar_id = '') {
        if (!ali_ads_should_show_banners()) {
            return '';
        }

        $category = ali_ads_get_current_post_category();
        $banner = Ali_Banner::get_random_banner($category, 'sidebar');

        if ($banner) {
            return self::render_banner($banner, 'sidebar');
        }

        return '';
    }

    /**
     * Reset contador de banners (útil para tests)
     */
    public static function reset_banner_count() {
        self::$banners_shown = 0;
    }

    /**
     * Obtener número de banners mostrados
     * @return int
     */
    public static function get_banners_shown() {
        return self::$banners_shown;
    }
}

/**
 * Widget personalizado para banners
 */
class Ali_Banner_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'ali_banner_widget',
            __('AliExpress Smart Banner', 'aliexpress-smart-ads'),
            array(
                'description' => __('Muestra un banner inteligente de AliExpress en el sidebar.', 'aliexpress-smart-ads')
            )
        );
    }

    /**
     * Frontend del widget
     */
    public function widget($args, $instance) {
        if (!ali_ads_should_show_banners()) {
            return;
        }

        $title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base);
        $category = !empty($instance['category']) ? $instance['category'] : '';
        $banner_id = !empty($instance['banner_id']) ? intval($instance['banner_id']) : 0;

        echo $args['before_widget'];
        
        if (!empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        if ($banner_id > 0) {
            $banner = Ali_Banner::get_banner($banner_id);
        } else {
            $current_category = ali_ads_get_current_post_category();
            $search_category = !empty($category) ? $category : $current_category;
            $banner = Ali_Banner::get_random_banner($search_category, 'sidebar');
        }

        if ($banner) {
            echo Ali_Display::render_banner($banner, 'sidebar');
        }

        echo $args['after_widget'];
    }

    /**
     * Backend del widget
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $category = !empty($instance['category']) ? $instance['category'] : '';
        $banner_id = !empty($instance['banner_id']) ? intval($instance['banner_id']) : 0;

        $categories = ali_ads_get_categories();
        $banners = Ali_Banner::get_banners(array('active_only' => true));
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Título:', 'aliexpress-smart-ads'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" 
                   name="<?php echo $this->get_field_name('title'); ?>" type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('banner_id'); ?>"><?php _e('Banner específico:', 'aliexpress-smart-ads'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('banner_id'); ?>" 
                    name="<?php echo $this->get_field_name('banner_id'); ?>">
                <option value="0"><?php _e('Automático (aleatorio)', 'aliexpress-smart-ads'); ?></option>
                <?php foreach ($banners as $banner) : ?>
                    <option value="<?php echo $banner->id; ?>" <?php selected($banner_id, $banner->id); ?>>
                        <?php echo esc_html($banner->title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small><?php _e('Si seleccionas un banner específico, se ignorará la categoría.', 'aliexpress-smart-ads'); ?></small>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('category'); ?>"><?php _e('Categoría:', 'aliexpress-smart-ads'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('category'); ?>" 
                    name="<?php echo $this->get_field_name('category'); ?>">
                <option value=""><?php _e('Categoría del post actual', 'aliexpress-smart-ads'); ?></option>
                <?php foreach ($categories as $slug => $name) : ?>
                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($category, $slug); ?>>
                        <?php echo esc_html($name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    }

    /**
     * Actualizar widget
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['category'] = (!empty($new_instance['category'])) ? sanitize_text_field($new_instance['category']) : '';
        $instance['banner_id'] = (!empty($new_instance['banner_id'])) ? intval($new_instance['banner_id']) : 0;

        return $instance;
    }
}