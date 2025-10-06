<?php
/**
 * Plugin Name: AliExpress Smart Ads - Auto Banners for WordPress
 * Plugin URI: https://example.com
 * Description: Plugin inteligente para insertar banners automáticos de AliExpress en zonas estratégicas de tu web WordPress. Incluye panel de administración, estadísticas y múltiples modos de inserción.
 * Version: 1.0.0
 * Author: Tu Nombre
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: aliexpress-smart-ads
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 *
 * @package AliExpress_Smart_Ads
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Constantes del plugin
define('ALI_ADS_VERSION', '1.0.0');
define('ALI_ADS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ALI_ADS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ALI_ADS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Clase principal del plugin AliExpress Smart Ads
 */
class AliExpress_Smart_Ads {

    /**
     * Instancia única del plugin (Singleton)
     * @var AliExpress_Smart_Ads
     */
    private static $instance = null;

    /**
     * Obtener instancia única del plugin
     * @return AliExpress_Smart_Ads
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado para Singleton
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Inicializar el plugin
     */
    private function init() {
        // Hooks de activación y desactivación
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Cargar el plugin después de que WordPress esté listo
        add_action('plugins_loaded', array($this, 'load_plugin'));
    }

    /**
     * Cargar el plugin
     */
    public function load_plugin() {
        // Verificar versión de WordPress y PHP
        if (!$this->check_requirements()) {
            return;
        }

        // Cargar idiomas
        load_plugin_textdomain('aliexpress-smart-ads', false, dirname(ALI_ADS_PLUGIN_BASENAME) . '/languages');

        // Incluir archivos necesarios
        $this->include_files();

        // Inicializar componentes
        $this->init_components();

        // Hooks principales
        $this->setup_hooks();
    }

    /**
     * Verificar requisitos del sistema
     * @return bool
     */
    private function check_requirements() {
        global $wp_version;

        // Verificar versión de WordPress
        if (version_compare($wp_version, '6.0', '<')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . 
                     esc_html__('AliExpress Smart Ads requiere WordPress 6.0 o superior.', 'aliexpress-smart-ads') . 
                     '</p></div>';
            });
            return false;
        }

        // Verificar versión de PHP
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . 
                     esc_html__('AliExpress Smart Ads requiere PHP 7.4 o superior.', 'aliexpress-smart-ads') . 
                     '</p></div>';
            });
            return false;
        }

        return true;
    }

    /**
     * Incluir archivos del plugin
     */
    private function include_files() {
        $includes_path = ALI_ADS_PLUGIN_PATH . 'includes/';

        // Archivos principales
        require_once $includes_path . 'helpers.php';
        require_once $includes_path . 'class-ali-banner.php';
        require_once $includes_path . 'class-ali-stats.php';
        require_once $includes_path . 'class-ali-display.php';
        require_once $includes_path . 'class-ali-auto-ads.php';

        // Solo en admin
        if (is_admin()) {
            require_once $includes_path . 'class-ali-admin.php';
        }
    }

    /**
     * Inicializar componentes del plugin
     */
    private function init_components() {
        // Inicializar estadísticas
        new Ali_Stats();

        // Inicializar display en frontend
        if (!is_admin()) {
            new Ali_Display();
        }

        // Inicializar ads automáticos
        new Ali_Auto_Ads();

        // Inicializar admin en backend
        if (is_admin()) {
            new Ali_Admin();
        }
    }

    /**
     * Configurar hooks principales
     */
    private function setup_hooks() {
        // Enqueue scripts y styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Shortcodes
        add_shortcode('ali_banner', array($this, 'shortcode_ali_banner'));

        // AJAX handlers para clics
        add_action('wp_ajax_ali_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_ali_track_click', array($this, 'track_click'));

        // URL de redirección para clics
        add_action('init', array($this, 'handle_click_redirect'));
    }

    /**
     * Activar plugin
     */
    public function activate() {
        // Crear tablas de base de datos
        $this->create_database_tables();

        // Crear opciones por defecto
        $this->create_default_options();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Desactivar plugin
     */
    public function deactivate() {
        // Limpiar cron jobs si los hay
        wp_clear_scheduled_hook('ali_ads_cleanup');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Crear tablas de base de datos
     */
    private function create_database_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'aliexpress_ads';

        $sql = "CREATE TABLE $table_name (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            image_url TEXT,
            target_url TEXT,
            iframe_code TEXT,
            category VARCHAR(100) DEFAULT '',
            placement VARCHAR(50) DEFAULT 'in_content',
            active BOOLEAN DEFAULT TRUE,
            impressions INT DEFAULT 0,
            clicks INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Guardar versión de la base de datos
        update_option('ali_ads_db_version', '1.0');
    }

    /**
     * Crear opciones por defecto
     */
    private function create_default_options() {
        $default_options = array(
            'affiliate_id' => '',
            'auto_insert_header' => false,
            'auto_insert_footer' => false,
            'auto_insert_content' => true,
            'auto_insert_sidebar' => false,
            'auto_insert_floating' => false,
            'auto_insert_between_posts' => false,
            'content_position' => 'after_first_paragraph',
            'max_banners_per_page' => 3,
            'floating_bar_position' => 'bottom',
            'between_posts_interval' => 3,
            'default_banner_category' => 'general'
        );

        add_option('ali_ads_options', $default_options);
    }

    /**
     * Enqueue assets para frontend
     */
    public function enqueue_frontend_assets() {
        // CSS
        wp_enqueue_style(
            'ali-banners-css',
            ALI_ADS_PLUGIN_URL . 'assets/css/ali-banners.css',
            array(),
            ALI_ADS_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'ali-banners-js',
            ALI_ADS_PLUGIN_URL . 'assets/js/ali-banners.js',
            array('jquery'),
            ALI_ADS_VERSION,
            true
        );

        // Localizar script
        wp_localize_script('ali-banners-js', 'ali_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ali_ads_nonce')
        ));
    }

    /**
     * Enqueue assets para admin
     */
    public function enqueue_admin_assets($hook) {
        // Solo en páginas del plugin
        if (strpos($hook, 'aliexpress-ads') === false) {
            return;
        }

        wp_enqueue_style(
            'ali-admin-css',
            ALI_ADS_PLUGIN_URL . 'assets/css/ali-admin.css',
            array(),
            ALI_ADS_VERSION
        );

        wp_enqueue_script(
            'ali-admin-js',
            ALI_ADS_PLUGIN_URL . 'assets/js/ali-admin.js',
            array('jquery'),
            ALI_ADS_VERSION,
            true
        );
    }

    /**
     * Shortcode para mostrar banner
     */
    public function shortcode_ali_banner($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'placement' => 'in_content',
            'id' => ''
        ), $atts);

        $banner = Ali_Banner::get_random_banner($atts['category'], $atts['placement']);
        
        if ($banner) {
            return Ali_Display::render_banner($banner, $atts['placement']);
        }

        return '';
    }

    /**
     * Manejar tracking de clics via AJAX
     */
    public function track_click() {
        check_ajax_referer('ali_ads_nonce', 'nonce');

        $banner_id = intval($_POST['banner_id']);
        
        if ($banner_id > 0) {
            Ali_Stats::track_click($banner_id);
        }

        wp_die();
    }

    /**
     * Manejar redirección de clics via URL
     */
    public function handle_click_redirect() {
        if (isset($_GET['ali_click']) && is_numeric($_GET['ali_click'])) {
            $banner_id = intval($_GET['ali_click']);
            
            // Registrar clic
            Ali_Stats::track_click($banner_id);
            
            // Obtener URL de destino
            $banner = Ali_Banner::get_banner($banner_id);
            
            if ($banner && !empty($banner->target_url)) {
                wp_redirect(esc_url_raw($banner->target_url));
                exit;
            }
        }
    }
}

// Inicializar el plugin
function ali_ads_init() {
    return AliExpress_Smart_Ads::get_instance();
}

// Ejecutar cuando WordPress esté cargado
ali_ads_init();