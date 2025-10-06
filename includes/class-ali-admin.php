<?php
/**
 * Clase de administración para AliExpress Smart Ads
 *
 * @package AliExpress_Smart_Ads
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class Ali_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_ajax_ali_save_banner', array($this, 'save_banner'));
        add_action('wp_ajax_ali_delete_banner', array($this, 'delete_banner'));
        add_action('wp_ajax_ali_toggle_banner', array($this, 'toggle_banner'));
        add_action('wp_ajax_ali_save_html_banner', array($this, 'save_html_banner'));
    }

    /**
     * Agregar menú de administración
     */
    public function add_admin_menu() {
        // Menú principal
        add_menu_page(
            __('AliExpress Ads', 'aliexpress-smart-ads'),
            __('AliExpress Ads', 'aliexpress-smart-ads'),
            'manage_options',
            'aliexpress-ads',
            array($this, 'dashboard_page'),
            'dashicons-megaphone',
            30
        );

        // Dashboard
        add_submenu_page(
            'aliexpress-ads',
            __('Dashboard', 'aliexpress-smart-ads'),
            __('Dashboard', 'aliexpress-smart-ads'),
            'manage_options',
            'aliexpress-ads',
            array($this, 'dashboard_page')
        );

        // Mis Banners
        add_submenu_page(
            'aliexpress-ads',
            __('Mis Banners', 'aliexpress-smart-ads'),
            __('Mis Banners', 'aliexpress-smart-ads'),
            'manage_options',
            'aliexpress-ads-banners',
            array($this, 'banners_page')
        );

        // Añadir Banner
        add_submenu_page(
            'aliexpress-ads',
            __('Añadir Banner', 'aliexpress-smart-ads'),
            __('Añadir Banner', 'aliexpress-smart-ads'),
            'manage_options',
            'aliexpress-ads-add',
            array($this, 'add_banner_page')
        );

        // Ads Automáticos (Estilo AdSense)
        add_submenu_page(
            'aliexpress-ads',
            __('Ads Automáticos', 'aliexpress-smart-ads'),
            __('Auto Ads', 'aliexpress-smart-ads'),
            'manage_options',
            'aliexpress-ads-auto',
            array($this, 'auto_ads_page')
        );

        // Código HTML (para banners de AliExpress)
        add_submenu_page(
            'aliexpress-ads',
            __('Código HTML', 'aliexpress-smart-ads'),
            __('Código HTML', 'aliexpress-smart-ads'),
            'manage_options',
            'aliexpress-ads-html',
            array($this, 'html_banner_page')
        );

        // ID de Afiliado AliExpress
        add_submenu_page(
            'aliexpress-ads',
            __('ID de Afiliado', 'aliexpress-smart-ads'),
            __('ID Afiliado', 'aliexpress-smart-ads'),
            'manage_options',
            'aliexpress-ads-affiliate',
            array($this, 'affiliate_config_page')
        );

        // Configuración
        add_submenu_page(
            'aliexpress-ads',
            __('Configuración', 'aliexpress-smart-ads'),
            __('Configuración', 'aliexpress-smart-ads'),
            'manage_options',
            'aliexpress-ads-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Inicializar admin
     */
    public function admin_init() {
        // Registrar configuraciones
        register_setting('ali_ads_options', 'ali_ads_options', array($this, 'validate_options'));
    }

    /**
     * Página de dashboard
     */
    public function dashboard_page() {
        $stats = Ali_Banner::get_stats();
        $recent_banners = Ali_Banner::get_banners(array('limit' => 5));
        
        ?>
        <div class="wrap">
            <h1><?php _e('AliExpress Smart Ads - Dashboard', 'aliexpress-smart-ads'); ?></h1>
            
            <div class="ali-dashboard-stats">
                <div class="ali-stat-box">
                    <h3><?php echo ali_ads_format_number($stats['total_banners']); ?></h3>
                    <p><?php _e('Total Banners', 'aliexpress-smart-ads'); ?></p>
                </div>
                <div class="ali-stat-box">
                    <h3><?php echo ali_ads_format_number($stats['active_banners']); ?></h3>
                    <p><?php _e('Banners Activos', 'aliexpress-smart-ads'); ?></p>
                </div>
                <div class="ali-stat-box">
                    <h3><?php echo ali_ads_format_number($stats['total_impressions']); ?></h3>
                    <p><?php _e('Total Impresiones', 'aliexpress-smart-ads'); ?></p>
                </div>
                <div class="ali-stat-box">
                    <h3><?php echo ali_ads_format_number($stats['total_clicks']); ?></h3>
                    <p><?php _e('Total Clics', 'aliexpress-smart-ads'); ?></p>
                </div>
                <div class="ali-stat-box">
                    <h3><?php echo $stats['ctr']; ?>%</h3>
                    <p><?php _e('CTR Promedio', 'aliexpress-smart-ads'); ?></p>
                </div>
            </div>

            <div class="ali-dashboard-content">
                <div class="ali-recent-banners">
                    <h2><?php _e('Banners Recientes', 'aliexpress-smart-ads'); ?></h2>
                    
                    <?php if (!empty($recent_banners)) : ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Título', 'aliexpress-smart-ads'); ?></th>
                                    <th><?php _e('Categoría', 'aliexpress-smart-ads'); ?></th>
                                    <th><?php _e('Ubicación', 'aliexpress-smart-ads'); ?></th>
                                    <th><?php _e('Impresiones', 'aliexpress-smart-ads'); ?></th>
                                    <th><?php _e('Clics', 'aliexpress-smart-ads'); ?></th>
                                    <th><?php _e('Estado', 'aliexpress-smart-ads'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_banners as $banner) : ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($banner->title); ?></strong></td>
                                        <td><?php echo esc_html($banner->category ?: 'General'); ?></td>
                                        <td><?php echo esc_html($this->get_placement_label($banner->placement)); ?></td>
                                        <td><?php echo ali_ads_format_number($banner->impressions); ?></td>
                                        <td><?php echo ali_ads_format_number($banner->clicks); ?></td>
                                        <td>
                                            <span class="ali-status-<?php echo $banner->active ? 'active' : 'inactive'; ?>">
                                                <?php echo $banner->active ? __('Activo', 'aliexpress-smart-ads') : __('Inactivo', 'aliexpress-smart-ads'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <p><?php _e('No hay banners creados aún.', 'aliexpress-smart-ads'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=aliexpress-ads-add'); ?>" class="button button-primary">
                            <?php _e('Crear mi primer banner', 'aliexpress-smart-ads'); ?>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="ali-quick-stats">
                    <h2><?php _e('Configuración Rápida', 'aliexpress-smart-ads'); ?></h2>
                    <p><?php _e('Estado actual de la inserción automática:', 'aliexpress-smart-ads'); ?></p>
                    
                    <ul>
                        <li>
                            <span class="dashicons <?php echo ali_ads_get_option('auto_insert_header') ? 'dashicons-yes' : 'dashicons-no'; ?>"></span>
                            <?php _e('Inserción en header:', 'aliexpress-smart-ads'); ?>
                            <strong><?php echo ali_ads_get_option('auto_insert_header') ? __('Activada', 'aliexpress-smart-ads') : __('Desactivada', 'aliexpress-smart-ads'); ?></strong>
                        </li>
                        <li>
                            <span class="dashicons <?php echo ali_ads_get_option('auto_insert_content') ? 'dashicons-yes' : 'dashicons-no'; ?>"></span>
                            <?php _e('Inserción en contenido:', 'aliexpress-smart-ads'); ?>
                            <strong><?php echo ali_ads_get_option('auto_insert_content') ? __('Activada', 'aliexpress-smart-ads') : __('Desactivada', 'aliexpress-smart-ads'); ?></strong>
                        </li>
                        <li>
                            <span class="dashicons <?php echo ali_ads_get_option('auto_insert_footer') ? 'dashicons-yes' : 'dashicons-no'; ?>"></span>
                            <?php _e('Inserción en footer:', 'aliexpress-smart-ads'); ?>
                            <strong><?php echo ali_ads_get_option('auto_insert_footer') ? __('Activada', 'aliexpress-smart-ads') : __('Desactivada', 'aliexpress-smart-ads'); ?></strong>
                        </li>
                        <li>
                            <span class="dashicons <?php echo ali_ads_get_option('auto_insert_floating') ? 'dashicons-yes' : 'dashicons-no'; ?>"></span>
                            <?php _e('Barra flotante:', 'aliexpress-smart-ads'); ?>
                            <strong><?php echo ali_ads_get_option('auto_insert_floating') ? __('Activada', 'aliexpress-smart-ads') : __('Desactivada', 'aliexpress-smart-ads'); ?></strong>
                        </li>
                    </ul>
                    
                    <a href="<?php echo admin_url('admin.php?page=aliexpress-ads-settings'); ?>" class="button">
                        <?php _e('Configurar inserción automática', 'aliexpress-smart-ads'); ?>
                    </a>
                </div>
            </div>
        </div>
        
        <style>
        .ali-dashboard-stats {
            display: flex;
            gap: 20px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .ali-stat-box {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-align: center;
            min-width: 150px;
            flex: 1;
        }
        .ali-stat-box h3 {
            font-size: 32px;
            margin: 0 0 10px 0;
            color: #0073aa;
        }
        .ali-stat-box p {
            margin: 0;
            color: #666;
        }
        .ali-dashboard-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        .ali-recent-banners, .ali-quick-stats {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .ali-status-active {
            color: #46b450;
            font-weight: bold;
        }
        .ali-status-inactive {
            color: #dc3232;
            font-weight: bold;
        }
        .ali-quick-stats ul {
            list-style: none;
            padding: 0;
        }
        .ali-quick-stats li {
            margin: 10px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        </style>
        <?php
    }

    /**
     * Página de banners
     */
    public function banners_page() {
        // Manejar acciones
        if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['banner_id'])) {
            check_admin_referer('ali_ads_action');
            Ali_Banner::delete_banner(intval($_POST['banner_id']));
            echo '<div class="notice notice-success"><p>' . __('Banner eliminado correctamente.', 'aliexpress-smart-ads') . '</p></div>';
        }

        $banners = Ali_Banner::get_banners(array('active_only' => false));
        ?>
        <div class="wrap">
            <h1>
                <?php _e('Mis Banners', 'aliexpress-smart-ads'); ?>
                <a href="<?php echo admin_url('admin.php?page=aliexpress-ads-add'); ?>" class="page-title-action">
                    <?php _e('Añadir Nuevo', 'aliexpress-smart-ads'); ?>
                </a>
            </h1>

            <?php if (!empty($banners)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 60px;"><?php _e('ID', 'aliexpress-smart-ads'); ?></th>
                            <th><?php _e('Título', 'aliexpress-smart-ads'); ?></th>
                            <th><?php _e('Tipo', 'aliexpress-smart-ads'); ?></th>
                            <th><?php _e('Categoría', 'aliexpress-smart-ads'); ?></th>
                            <th><?php _e('Ubicación', 'aliexpress-smart-ads'); ?></th>
                            <th><?php _e('Impresiones', 'aliexpress-smart-ads'); ?></th>
                            <th><?php _e('Clics', 'aliexpress-smart-ads'); ?></th>
                            <th><?php _e('Estado', 'aliexpress-smart-ads'); ?></th>
                            <th><?php _e('Acciones', 'aliexpress-smart-ads'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($banners as $banner) : ?>
                            <tr>
                                <td><?php echo $banner->id; ?></td>
                                <td>
                                    <strong><?php echo esc_html($banner->title); ?></strong>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="<?php echo admin_url('admin.php?page=aliexpress-ads-add&action=edit&id=' . $banner->id); ?>">
                                                <?php _e('Editar', 'aliexpress-smart-ads'); ?>
                                            </a>
                                        </span>
                                    </div>
                                </td>
                                <td><?php echo !empty($banner->iframe_code) ? __('Iframe', 'aliexpress-smart-ads') : __('Imagen', 'aliexpress-smart-ads'); ?></td>
                                <td><?php echo esc_html($banner->category ?: 'General'); ?></td>
                                <td><?php echo esc_html($this->get_placement_label($banner->placement)); ?></td>
                                <td><?php echo ali_ads_format_number($banner->impressions); ?></td>
                                <td><?php echo ali_ads_format_number($banner->clicks); ?></td>
                                <td>
                                    <button class="button button-small ali-toggle-banner" 
                                            data-banner-id="<?php echo $banner->id; ?>"
                                            data-active="<?php echo $banner->active; ?>">
                                        <?php echo $banner->active ? __('Desactivar', 'aliexpress-smart-ads') : __('Activar', 'aliexpress-smart-ads'); ?>
                                    </button>
                                </td>
                                <td>
                                    <form method="post" style="display: inline;" 
                                          onsubmit="return confirm('<?php _e('¿Estás seguro de eliminar este banner?', 'aliexpress-smart-ads'); ?>');">
                                        <?php wp_nonce_field('ali_ads_action'); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="banner_id" value="<?php echo $banner->id; ?>">
                                        <button type="submit" class="button button-small button-link-delete">
                                            <?php _e('Eliminar', 'aliexpress-smart-ads'); ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="ali-empty-state">
                    <h2><?php _e('No tienes banners creados aún', 'aliexpress-smart-ads'); ?></h2>
                    <p><?php _e('Crea tu primer banner para empezar a monetizar tu sitio web con AliExpress.', 'aliexpress-smart-ads'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=aliexpress-ads-add'); ?>" class="button button-primary button-large">
                        <?php _e('Crear Mi Primer Banner', 'aliexpress-smart-ads'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('.ali-toggle-banner').click(function() {
                var $btn = $(this);
                var bannerId = $btn.data('banner-id');
                var isActive = $btn.data('active');
                
                $.post(ajaxurl, {
                    action: 'ali_toggle_banner',
                    banner_id: bannerId,
                    active: isActive ? 0 : 1,
                    nonce: '<?php echo wp_create_nonce('ali_ads_admin'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    }
                });
            });
        });
        </script>

        <style>
        .ali-empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .ali-empty-state h2 {
            color: #666;
            font-weight: 300;
            margin-bottom: 15px;
        }
        .ali-empty-state p {
            color: #999;
            margin-bottom: 25px;
        }
        </style>
        <?php
    }

    /**
     * Página de añadir/editar banner
     */
    public function add_banner_page() {
        $banner = null;
        $action = 'add';
        
        // Si estamos editando
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            $action = 'edit';
            $banner = Ali_Banner::get_banner_raw(intval($_GET['id']));
            
            if (!$banner) {
                wp_die(__('Banner no encontrado.', 'aliexpress-smart-ads'));
            }
        }

        // Procesar formulario
        if (isset($_POST['submit_banner'])) {
            check_admin_referer('ali_ads_banner');
            
            $data = array(
                'title' => sanitize_text_field($_POST['title']),
                'image_url' => esc_url_raw($_POST['image_url']),
                'target_url' => esc_url_raw($_POST['target_url']),
                'iframe_code' => wp_unslash($_POST['iframe_code']),
                'category' => sanitize_text_field($_POST['category']),
                'placement' => sanitize_text_field($_POST['placement']),
                'active' => isset($_POST['active']) ? 1 : 0
            );
            
            if ($action === 'edit') {
                $result = Ali_Banner::update_banner($banner->id, $data);
                $message = $result ? __('Banner actualizado correctamente.', 'aliexpress-smart-ads') : __('Error al actualizar el banner.', 'aliexpress-smart-ads');
            } else {
                $result = Ali_Banner::create_banner($data);
                $message = $result ? __('Banner creado correctamente.', 'aliexpress-smart-ads') : __('Error al crear el banner.', 'aliexpress-smart-ads');
            }
            
            if ($result) {
                echo '<div class="notice notice-success"><p>' . $message . '</p></div>';
                
                if ($action === 'add') {
                    // Limpiar formulario después de crear
                    $banner = null;
                } else {
                    // Recargar datos del banner editado
                    $banner = Ali_Banner::get_banner_raw($banner->id);
                }
            } else {
                echo '<div class="notice notice-error"><p>' . $message . '</p></div>';
            }
        }

        $categories = ali_ads_get_categories();
        $placements = ali_ads_get_placements();
        ?>
        <div class="wrap">
            <h1><?php echo $action === 'edit' ? __('Editar Banner', 'aliexpress-smart-ads') : __('Añadir Nuevo Banner', 'aliexpress-smart-ads'); ?></h1>
            
            <form method="post" class="ali-banner-form">
                <?php wp_nonce_field('ali_ads_banner'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="title"><?php _e('Título del Banner', 'aliexpress-smart-ads'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="title" name="title" class="regular-text" 
                                   value="<?php echo $banner ? esc_attr($banner->title) : ''; ?>" required>
                            <p class="description"><?php _e('Nombre interno para identificar el banner.', 'aliexpress-smart-ads'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="banner_type"><?php _e('Tipo de Banner', 'aliexpress-smart-ads'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="radio" name="banner_type" value="image" 
                                       <?php checked(empty($banner->iframe_code) || !empty($banner->image_url)); ?>>
                                <?php _e('Imagen con enlace', 'aliexpress-smart-ads'); ?>
                            </label><br>
                            <label>
                                <input type="radio" name="banner_type" value="iframe" 
                                       <?php checked(!empty($banner->iframe_code) && empty($banner->image_url)); ?>>
                                <?php _e('Código iframe', 'aliexpress-smart-ads'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr class="image-fields">
                        <th scope="row">
                            <label for="image_url"><?php _e('URL de la Imagen', 'aliexpress-smart-ads'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="image_url" name="image_url" class="regular-text" 
                                   value="<?php echo $banner ? esc_attr($banner->image_url) : ''; ?>">
                            <button type="button" class="button" onclick="wp.media.editor.send.attachment = function(props, attachment) { jQuery('#image_url').val(attachment.url); }; wp.media.editor.open();">
                                <?php _e('Seleccionar imagen', 'aliexpress-smart-ads'); ?>
                            </button>
                            <p class="description"><?php _e('URL directa de la imagen del banner.', 'aliexpress-smart-ads'); ?></p>
                        </td>
                    </tr>

                    <tr class="image-fields">
                        <th scope="row">
                            <label for="target_url"><?php _e('URL de destino', 'aliexpress-smart-ads'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="target_url" name="target_url" class="regular-text" 
                                   value="<?php echo $banner ? esc_attr($banner->target_url) : ''; ?>">
                            <p class="description"><?php _e('URL de AliExpress a la que redirigirá el banner.', 'aliexpress-smart-ads'); ?></p>
                        </td>
                    </tr>

                    <tr class="iframe-fields" style="display: none;">
                        <th scope="row">
                            <label for="iframe_code"><?php _e('Código Iframe', 'aliexpress-smart-ads'); ?></label>
                        </th>
                        <td>
                            <textarea id="iframe_code" name="iframe_code" rows="5" class="large-text code"><?php echo $banner ? esc_textarea($banner->iframe_code) : ''; ?></textarea>
                            <p class="description"><?php _e('Código iframe completo proporcionado por AliExpress.', 'aliexpress-smart-ads'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="category"><?php _e('Categoría', 'aliexpress-smart-ads'); ?></label>
                        </th>
                        <td>
                            <select id="category" name="category">
                                <?php foreach ($categories as $slug => $name) : ?>
                                    <option value="<?php echo esc_attr($slug); ?>" 
                                            <?php selected($banner ? $banner->category : '', $slug); ?>>
                                        <?php echo esc_html($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Categoría donde mostrar el banner. Vacío = todas las categorías.', 'aliexpress-smart-ads'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="placement"><?php _e('Ubicación', 'aliexpress-smart-ads'); ?></label>
                        </th>
                        <td>
                            <select id="placement" name="placement">
                                <?php foreach ($placements as $slug => $name) : ?>
                                    <option value="<?php echo esc_attr($slug); ?>" 
                                            <?php selected($banner ? $banner->placement : 'in_content', $slug); ?>>
                                        <?php echo esc_html($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Dónde se mostrará automáticamente el banner.', 'aliexpress-smart-ads'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <?php _e('Estado', 'aliexpress-smart-ads'); ?>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="active" value="1" 
                                       <?php checked($banner ? $banner->active : 1); ?>>
                                <?php _e('Banner activo', 'aliexpress-smart-ads'); ?>
                            </label>
                            <p class="description"><?php _e('Solo los banners activos se mostrarán en el sitio web.', 'aliexpress-smart-ads'); ?></p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="submit_banner" class="button-primary" 
                           value="<?php echo $action === 'edit' ? __('Actualizar Banner', 'aliexpress-smart-ads') : __('Crear Banner', 'aliexpress-smart-ads'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=aliexpress-ads-banners'); ?>" class="button">
                        <?php _e('Cancelar', 'aliexpress-smart-ads'); ?>
                    </a>
                </p>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            function toggleBannerFields() {
                var type = $('input[name="banner_type"]:checked').val();
                
                if (type === 'image') {
                    $('.image-fields').show();
                    $('.iframe-fields').hide();
                } else {
                    $('.image-fields').hide();
                    $('.iframe-fields').show();
                }
            }

            $('input[name="banner_type"]').change(toggleBannerFields);
            toggleBannerFields();
        });
        </script>
        <?php
    }

    /**
     * Página de configuración
     */
    public function settings_page() {
        if (isset($_POST['submit_settings'])) {
            check_admin_referer('ali_ads_settings');
            
            $options = array(
                'affiliate_id' => sanitize_text_field($_POST['affiliate_id']),
                'auto_insert_header' => isset($_POST['auto_insert_header']),
                'auto_insert_footer' => isset($_POST['auto_insert_footer']),
                'auto_insert_content' => isset($_POST['auto_insert_content']),
                'auto_insert_sidebar' => isset($_POST['auto_insert_sidebar']),
                'auto_insert_floating' => isset($_POST['auto_insert_floating']),
                'auto_insert_between_posts' => isset($_POST['auto_insert_between_posts']),
                'content_position' => sanitize_text_field($_POST['content_position']),
                'max_banners_per_page' => intval($_POST['max_banners_per_page']),
                'floating_bar_position' => sanitize_text_field($_POST['floating_bar_position']),
                'between_posts_interval' => intval($_POST['between_posts_interval']),
                'default_banner_category' => sanitize_text_field($_POST['default_banner_category'])
            );
            
            update_option('ali_ads_options', $options);
            echo '<div class="notice notice-success"><p>' . __('Configuración guardada correctamente.', 'aliexpress-smart-ads') . '</p></div>';
        }

        $options = get_option('ali_ads_options', array());
        $categories = ali_ads_get_categories();
        ?>
        <div class="wrap">
            <h1><?php _e('Configuración - AliExpress Smart Ads', 'aliexpress-smart-ads'); ?></h1>
            
            <form method="post">
                <?php wp_nonce_field('ali_ads_settings'); ?>
                
                <h2><?php _e('Configuración de Afiliado', 'aliexpress-smart-ads'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="affiliate_id"><?php _e('ID de Afiliado', 'aliexpress-smart-ads'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="affiliate_id" name="affiliate_id" class="regular-text" 
                                   value="<?php echo esc_attr($options['affiliate_id'] ?? ''); ?>">
                            <p class="description"><?php _e('Tu ID de afiliado de AliExpress para tracking.', 'aliexpress-smart-ads'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php _e('Inserción Automática', 'aliexpress-smart-ads'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Ubicaciones Activas', 'aliexpress-smart-ads'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="auto_insert_header" value="1" 
                                           <?php checked($options['auto_insert_header'] ?? false); ?>>
                                    <?php _e('Insertar en header', 'aliexpress-smart-ads'); ?>
                                </label><br>
                                
                                <label>
                                    <input type="checkbox" name="auto_insert_content" value="1" 
                                           <?php checked($options['auto_insert_content'] ?? true); ?>>
                                    <?php _e('Insertar en contenido de posts', 'aliexpress-smart-ads'); ?>
                                </label><br>
                                
                                <label>
                                    <input type="checkbox" name="auto_insert_footer" value="1" 
                                           <?php checked($options['auto_insert_footer'] ?? false); ?>>
                                    <?php _e('Insertar en footer', 'aliexpress-smart-ads'); ?>
                                </label><br>
                                
                                <label>
                                    <input type="checkbox" name="auto_insert_sidebar" value="1" 
                                           <?php checked($options['auto_insert_sidebar'] ?? false); ?>>
                                    <?php _e('Insertar en sidebar', 'aliexpress-smart-ads'); ?>
                                </label><br>
                                
                                <label>
                                    <input type="checkbox" name="auto_insert_floating" value="1" 
                                           <?php checked($options['auto_insert_floating'] ?? false); ?>>
                                    <?php _e('Mostrar barra flotante', 'aliexpress-smart-ads'); ?>
                                </label><br>
                                
                                <label>
                                    <input type="checkbox" name="auto_insert_between_posts" value="1" 
                                           <?php checked($options['auto_insert_between_posts'] ?? false); ?>>
                                    <?php _e('Insertar entre posts (en listados)', 'aliexpress-smart-ads'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="content_position"><?php _e('Posición en Contenido', 'aliexpress-smart-ads'); ?></label>
                        </th>
                        <td>
                            <select id="content_position" name="content_position">
                                <option value="after_first_paragraph" <?php selected($options['content_position'] ?? 'after_first_paragraph', 'after_first_paragraph'); ?>>
                                    <?php _e('Después del primer párrafo', 'aliexpress-smart-ads'); ?>
                                </option>
                                <option value="after_second_paragraph" <?php selected($options['content_position'] ?? '', 'after_second_paragraph'); ?>>
                                    <?php _e('Después del segundo párrafo', 'aliexpress-smart-ads'); ?>
                                </option>
                                <option value="middle_content" <?php selected($options['content_position'] ?? '', 'middle_content'); ?>>
                                    <?php _e('En medio del contenido', 'aliexpress-smart-ads'); ?>
                                </option>
                                <option value="end_content" <?php selected($options['content_position'] ?? '', 'end_content'); ?>>
                                    <?php _e('Al final del contenido', 'aliexpress-smart-ads'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="max_banners_per_page"><?php _e('Máximo Banners por Página', 'aliexpress-smart-ads'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="max_banners_per_page" name="max_banners_per_page" 
                                   value="<?php echo intval($options['max_banners_per_page'] ?? 3); ?>" min="1" max="10">
                            <p class="description"><?php _e('Número máximo de banners que se mostrarán en una página.', 'aliexpress-smart-ads'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="floating_bar_position"><?php _e('Posición de Barra Flotante', 'aliexpress-smart-ads'); ?></label>
                        </th>
                        <td>
                            <select id="floating_bar_position" name="floating_bar_position">
                                <option value="bottom" <?php selected($options['floating_bar_position'] ?? 'bottom', 'bottom'); ?>>
                                    <?php _e('Inferior', 'aliexpress-smart-ads'); ?>
                                </option>
                                <option value="top" <?php selected($options['floating_bar_position'] ?? '', 'top'); ?>>
                                    <?php _e('Superior', 'aliexpress-smart-ads'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="between_posts_interval"><?php _e('Intervalo Entre Posts', 'aliexpress-smart-ads'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="between_posts_interval" name="between_posts_interval" 
                                   value="<?php echo intval($options['between_posts_interval'] ?? 3); ?>" min="1" max="20">
                            <p class="description"><?php _e('Mostrar banner cada X posts en listados.', 'aliexpress-smart-ads'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="default_banner_category"><?php _e('Categoría por Defecto', 'aliexpress-smart-ads'); ?></label>
                        </th>
                        <td>
                            <select id="default_banner_category" name="default_banner_category">
                                <?php foreach ($categories as $slug => $name) : ?>
                                    <option value="<?php echo esc_attr($slug); ?>" 
                                            <?php selected($options['default_banner_category'] ?? 'general', $slug); ?>>
                                        <?php echo esc_html($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Categoría a usar cuando no se puede determinar la categoría del post.', 'aliexpress-smart-ads'); ?></p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="submit_settings" class="button-primary" 
                           value="<?php _e('Guardar Configuración', 'aliexpress-smart-ads'); ?>">
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Obtener etiqueta de ubicación
     * @param string $placement
     * @return string
     */
    private function get_placement_label($placement) {
        $placements = ali_ads_get_placements();
        return $placements[$placement] ?? $placement;
    }

    /**
     * AJAX: Guardar banner
     */
    public function save_banner() {
        check_ajax_referer('ali_ads_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        // Implementar guardado via AJAX
        wp_send_json_success();
    }

    /**
     * AJAX: Eliminar banner
     */
    public function delete_banner() {
        check_ajax_referer('ali_ads_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $banner_id = intval($_POST['banner_id']);
        $result = Ali_Banner::delete_banner($banner_id);
        
        wp_send_json($result ? array('success' => true) : array('success' => false));
    }

    /**
     * AJAX: Toggle banner activo/inactivo
     */
    public function toggle_banner() {
        check_ajax_referer('ali_ads_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $banner_id = intval($_POST['banner_id']);
        $active = intval($_POST['active']);
        
        $result = Ali_Banner::toggle_banner($banner_id, $active);
        
        wp_send_json($result ? array('success' => true) : array('success' => false));
    }

    /**
     * Página de Ads Automáticos (Estilo AdSense)
     */
    public function auto_ads_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para acceder a esta página.', 'aliexpress-smart-ads'));
        }

        // Procesar guardado de configuración
        if (isset($_POST['save_auto_ads_config']) && check_admin_referer('ali_auto_ads_config', 'ali_auto_ads_nonce')) {
            $this->save_auto_ads_config();
            echo '<div class="notice notice-success"><p>' . __('Configuración guardada correctamente.', 'aliexpress-smart-ads') . '</p></div>';
        }

        // Obtener configuración actual
        $auto_ads = new Ali_Auto_Ads();
        $config = $auto_ads->get_config();
        
        // Debug para ver la configuración cargada
        echo '<div class="notice notice-info"><p><strong>📄 CONFIG CARGADA:</strong> enabled = ' . var_export($config['enabled'], true) . '</p></div>';
        
        // Verificar banners disponibles
        global $wpdb;
        $table_name = $wpdb->prefix . 'aliexpress_ads';
        $total_banners = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE is_active = 1");
        $active_banners = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE is_active = 1");
        

        
        ?>
        <div class="wrap">
            <h1><?php _e('Ads Automáticos - Estilo AdSense', 'aliexpress-smart-ads'); ?></h1>
            
            <div class="ali-auto-ads-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; margin: 20px 0;">
                <h2 style="color: white; margin: 0;"><?php _e('🚀 Colocación Automática Inteligente', 'aliexpress-smart-ads'); ?></h2>
                <p style="margin: 10px 0 0 0; opacity: 0.9;"><?php _e('Deja que nuestro sistema coloque automáticamente TUS BANNERS en las mejores posiciones, igual que AdSense de Google.', 'aliexpress-smart-ads'); ?></p>
                <p style="margin: 8px 0 0 0; opacity: 0.8; font-size: 14px;"><?php _e('💡 Este sistema usa los banners que has configurado en "Gestión de Banners" y "Código HTML" para mostrarlos automáticamente.', 'aliexpress-smart-ads'); ?></p>
            </div>

            <!-- Estado de banners disponibles -->
            <?php if ($active_banners > 0): ?>
                <div class="notice notice-success" style="margin: 15px 0;">
                    <p><strong>✅ <?php _e('Perfecto!', 'aliexpress-smart-ads'); ?></strong> 
                       <?php printf(__('Tienes %d banners activos configurados. El sistema automático puede funcionar.', 'aliexpress-smart-ads'), $active_banners); ?></p>
                </div>
            <?php else: ?>
                <div class="notice notice-warning" style="margin: 15px 0;">
                    <p><strong>⚠️ <?php _e('Sin banners configurados', 'aliexpress-smart-ads'); ?></strong></p>
                    <p><?php _e('Para que el sistema automático funcione, necesitas configurar al menos un banner en:', 'aliexpress-smart-ads'); ?></p>
                    <ul style="margin: 10px 0 10px 20px;">
                        <li>• <a href="<?php echo admin_url('admin.php?page=aliexpress-smart-ads'); ?>"><?php _e('Gestión de Banners', 'aliexpress-smart-ads'); ?></a></li>
                        <li>• <a href="<?php echo admin_url('admin.php?page=aliexpress-html-banners'); ?>"><?php _e('Código HTML', 'aliexpress-smart-ads'); ?></a></li>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <?php wp_nonce_field('ali_auto_ads_config', 'ali_auto_ads_nonce'); ?>
                
                <div class="ali-admin-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
                    
                    <!-- Configuración Principal -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><?php _e('⚙️ Configuración Principal', 'aliexpress-smart-ads'); ?></h2>
                        </div>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="auto_ads_enabled"><?php _e('Activar Ads Automáticos', 'aliexpress-smart-ads'); ?></label>
                                    </th>
                                    <td>
                                        <label class="switch">
                                            <input type="checkbox" id="auto_ads_enabled" name="auto_ads_enabled" 
                                                   value="1" <?php checked($config['enabled'], true); ?>>
                                            <span class="slider round"></span>
                                        </label>
                                        <p class="description"><?php _e('Activa la colocación automática de ads en tu sitio web.', 'aliexpress-smart-ads'); ?></p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label for="ad_density"><?php _e('Densidad de Ads', 'aliexpress-smart-ads'); ?></label>
                                    </th>
                                    <td>
                                        <select id="ad_density" name="ad_density">
                                            <option value="low" <?php selected($config['density'], 'low'); ?>><?php _e('Baja - Pocos ads', 'aliexpress-smart-ads'); ?></option>
                                            <option value="medium" <?php selected($config['density'], 'medium'); ?>><?php _e('Media - Balance optimal', 'aliexpress-smart-ads'); ?></option>
                                            <option value="high" <?php selected($config['density'], 'high'); ?>><?php _e('Alta - Máximos ingresos', 'aliexpress-smart-ads'); ?></option>
                                        </select>
                                        <p class="description"><?php _e('Controla cuántos ads se muestran automáticamente.', 'aliexpress-smart-ads'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Optimización Inteligente -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><?php _e('🧠 Optimización Inteligente', 'aliexpress-smart-ads'); ?></h2>
                        </div>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="optimization_enabled"><?php _e('Auto Optimización', 'aliexpress-smart-ads'); ?></label>
                                    </th>
                                    <td>
                                        <label class="switch">
                                            <input type="checkbox" id="optimization_enabled" name="optimization_enabled" 
                                                   value="1" <?php checked($config['optimization']['enabled'], true); ?>>
                                            <span class="slider round"></span>
                                        </label>
                                        <p class="description"><?php _e('El sistema aprende automáticamente las mejores posiciones.', 'aliexpress-smart-ads'); ?></p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label for="learning_period"><?php _e('Período de Aprendizaje', 'aliexpress-smart-ads'); ?></label>
                                    </th>
                                    <td>
                                        <input type="number" id="learning_period" name="learning_period" 
                                               value="<?php echo intval($config['optimization']['learning_period']); ?>" 
                                               min="1" max="30"> días
                                        <p class="description"><?php _e('Días para recopilar datos y optimizar posiciones.', 'aliexpress-smart-ads'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Configuración por Tipo de Ad -->
                <div class="ali-ad-types" style="margin: 20px 0;">
                    
                    <!-- Ads en Contenido -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><?php _e('📝 Ads en Contenido de Posts', 'aliexpress-smart-ads'); ?></h2>
                        </div>
                        <div class="inside">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div>
                                    <label class="switch">
                                        <input type="checkbox" name="content_ads_enabled" 
                                               value="1" <?php checked($config['content_ads']['enabled'], true); ?>>
                                        <span class="slider round"></span>
                                    </label>
                                    <strong><?php _e('Activar en posts', 'aliexpress-smart-ads'); ?></strong>
                                    <p><?php _e('Coloca ads automáticamente entre párrafos de tus posts.', 'aliexpress-smart-ads'); ?></p>
                                </div>
                                
                                <div>
                                    <label><?php _e('Frecuencia:', 'aliexpress-smart-ads'); ?></label><br>
                                    <input type="number" name="content_ads_frequency" 
                                           value="<?php echo intval($config['content_ads']['frequency']); ?>" 
                                           min="1" max="10" style="width: 80px;"> párrafos
                                    
                                    <br><br>
                                    <label><?php _e('Máximo por post:', 'aliexpress-smart-ads'); ?></label><br>
                                    <input type="number" name="content_ads_max" 
                                           value="<?php echo intval($config['content_ads']['max_per_post']); ?>" 
                                           min="1" max="10" style="width: 80px;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ads Sticky/Flotantes -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><?php _e('📌 Ads Sticky (Flotantes)', 'aliexpress-smart-ads'); ?></h2>
                        </div>
                        <div class="inside">
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                                <div>
                                    <label class="switch">
                                        <input type="checkbox" name="sticky_ads_enabled" 
                                               value="1" <?php checked($config['sticky_ads']['enabled'], true); ?>>
                                        <span class="slider round"></span>
                                    </label>
                                    <strong><?php _e('Activar ads sticky', 'aliexpress-smart-ads'); ?></strong>
                                </div>
                                
                                <div>
                                    <label><?php _e('Posición:', 'aliexpress-smart-ads'); ?></label><br>
                                    <select name="sticky_ads_position">
                                        <option value="bottom" <?php selected($config['sticky_ads']['position'], 'bottom'); ?>><?php _e('Abajo', 'aliexpress-smart-ads'); ?></option>
                                        <option value="top" <?php selected($config['sticky_ads']['position'], 'top'); ?>><?php _e('Arriba', 'aliexpress-smart-ads'); ?></option>
                                        <option value="side" <?php selected($config['sticky_ads']['position'], 'side'); ?>><?php _e('Lateral', 'aliexpress-smart-ads'); ?></option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label><?php _e('Retraso (ms):', 'aliexpress-smart-ads'); ?></label><br>
                                    <input type="number" name="sticky_ads_delay" 
                                           value="<?php echo intval($config['sticky_ads']['delay']); ?>" 
                                           min="0" max="10000" step="500">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ads en Listados -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><?php _e('📋 Ads en Listados de Posts', 'aliexpress-smart-ads'); ?></h2>
                        </div>
                        <div class="inside">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div>
                                    <label class="switch">
                                        <input type="checkbox" name="loop_ads_enabled" 
                                               value="1" <?php checked($config['loop_ads']['enabled'], true); ?>>
                                        <span class="slider round"></span>
                                    </label>
                                    <strong><?php _e('Activar en listados', 'aliexpress-smart-ads'); ?></strong>
                                    <p><?php _e('Muestra ads entre los posts en página de inicio, categorías, etc.', 'aliexpress-smart-ads'); ?></p>
                                </div>
                                
                                <div>
                                    <label><?php _e('Cada cuántos posts:', 'aliexpress-smart-ads'); ?></label><br>
                                    <input type="number" name="loop_ads_frequency" 
                                           value="<?php echo intval($config['loop_ads']['frequency']); ?>" 
                                           min="1" max="20" style="width: 80px;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ads en Sidebar -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><?php _e('➡️ Ads en Sidebar', 'aliexpress-smart-ads'); ?></h2>
                        </div>
                        <div class="inside">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div>
                                    <label class="switch">
                                        <input type="checkbox" name="sidebar_ads_enabled" 
                                               value="1" <?php checked($config['sidebar_ads']['enabled'], true); ?>>
                                        <span class="slider round"></span>
                                    </label>
                                    <strong><?php _e('Activar en sidebar', 'aliexpress-smart-ads'); ?></strong>
                                </div>
                                
                                <div>
                                    <label><?php _e('Posición:', 'aliexpress-smart-ads'); ?></label><br>
                                    <select name="sidebar_ads_position">
                                        <option value="top" <?php selected($config['sidebar_ads']['position'], 'top'); ?>><?php _e('Arriba', 'aliexpress-smart-ads'); ?></option>
                                        <option value="middle" <?php selected($config['sidebar_ads']['position'], 'middle'); ?>><?php _e('Medio', 'aliexpress-smart-ads'); ?></option>
                                        <option value="bottom" <?php selected($config['sidebar_ads']['position'], 'bottom'); ?>><?php _e('Abajo', 'aliexpress-smart-ads'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="submit">
                    <input type="submit" name="save_auto_ads_config" class="button-primary button-hero" 
                           value="<?php _e('💾 Guardar Configuración Automática', 'aliexpress-smart-ads'); ?>">
                </p>
            </form>

            <!-- Estadísticas de Rendimiento -->
            <div class="postbox" style="margin-top: 30px;">
                <div class="postbox-header">
                    <h2 class="hndle"><?php _e('📊 Rendimiento de Ads Automáticos', 'aliexpress-smart-ads'); ?></h2>
                </div>
                <div class="inside">
                    <?php $this->render_auto_ads_stats(); ?>
                </div>
            </div>
        </div>

        <!-- Estilos CSS para la página -->
        <style>
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
            margin-right: 10px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            -webkit-transition: .4s;
            transition: .4s;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            -webkit-transition: .4s;
            transition: .4s;
        }

        input:checked + .slider {
            background-color: #2196F3;
        }

        input:focus + .slider {
            box-shadow: 0 0 1px #2196F3;
        }

        input:checked + .slider:before {
            -webkit-transform: translateX(26px);
            -ms-transform: translateX(26px);
            transform: translateX(26px);
        }

        .slider.round {
            border-radius: 34px;
        }

        .slider.round:before {
            border-radius: 50%;
        }

        .ali-admin-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .ali-admin-grid {
                grid-template-columns: 1fr;
            }
        }
        </style>

        <!-- Debug JavaScript temporal -->
        <script>
        jQuery(document).ready(function($) {
            // Verificar valores iniciales
            console.log('Estado inicial del checkbox:', $('#auto_ads_enabled').is(':checked'));
            console.log('Valor inicial del checkbox:', $('#auto_ads_enabled').val());
            
            // Monitorear cambios
            $('#auto_ads_enabled').change(function() {
                console.log('Checkbox cambió a:', $(this).is(':checked'));
            });
            
            // Monitorear submit del formulario
            $('form').submit(function(e) {
                console.log('🚀 Formulario enviado');
                console.log('Estado del checkbox al enviar:', $('#auto_ads_enabled').is(':checked'));
                
                // FORZAR que el checkbox tenga valor cuando está marcado
                if ($('#auto_ads_enabled').is(':checked')) {
                    console.log('✅ Checkbox marcado - forzando value=1');
                    $('#auto_ads_enabled').val('1');
                } else {
                    console.log('❌ Checkbox NO marcado');
                }
                
                // Mostrar todos los datos del formulario
                var formData = new FormData(this);
                console.log('📋 Datos del formulario:');
                for (var pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }
                
                // NO prevenir el submit - dejar que continúe
                return true;
            });
        });
        </script>
        
        <?php
    }

    /**
     * Guardar configuración de ads automáticos
     */
    private function save_auto_ads_config() {
        $config = array(
            'enabled' => !empty($_POST['auto_ads_enabled']),
            'density' => sanitize_text_field($_POST['ad_density'] ?? 'medium'),
            'content_ads' => array(
                'enabled' => !empty($_POST['content_ads_enabled']),
                'frequency' => intval($_POST['content_ads_frequency'] ?? 3),
                'max_per_post' => intval($_POST['content_ads_max'] ?? 3),
                'min_paragraphs' => 3
            ),
            'sticky_ads' => array(
                'enabled' => !empty($_POST['sticky_ads_enabled']),
                'position' => sanitize_text_field($_POST['sticky_ads_position'] ?? 'bottom'),
                'delay' => intval($_POST['sticky_ads_delay'] ?? 3000),
                'auto_hide' => true
            ),
            'loop_ads' => array(
                'enabled' => !empty($_POST['loop_ads_enabled']),
                'frequency' => intval($_POST['loop_ads_frequency'] ?? 5),
                'categories' => array()
            ),
            'sidebar_ads' => array(
                'enabled' => !empty($_POST['sidebar_ads_enabled']),
                'position' => sanitize_text_field($_POST['sidebar_ads_position'] ?? 'top'),
                'auto_size' => true
            ),
            'optimization' => array(
                'enabled' => !empty($_POST['optimization_enabled']),
                'learning_period' => intval($_POST['learning_period'] ?? 7),
                'auto_optimize_positions' => true
            )
        );

        // Debug completo de lo que recibe POST
        echo '<div class="notice notice-info"><p><strong>🔧 DEBUG POST:</strong><br>';
        echo 'auto_ads_enabled: ' . var_export($_POST['auto_ads_enabled'] ?? 'NO_EXISTE', true) . '<br>';
        echo 'save_auto_ads_config: ' . var_export($_POST['save_auto_ads_config'] ?? 'NO_EXISTE', true) . '<br>';
        echo 'Nonce válido: ' . (wp_verify_nonce($_POST['ali_auto_ads_nonce'] ?? '', 'ali_auto_ads_config') ? 'SÍ' : 'NO');
        echo '</p></div>';
        
        // Debug para verificar qué se está intentando guardar
        echo '<div class="notice notice-warning"><p><strong>🔧 CONFIG A GUARDAR:</strong> enabled = ' . var_export($config['enabled'], true) . '</p></div>';
        
        $auto_ads = new Ali_Auto_Ads();
        $result = $auto_ads->update_config($config);
        
        // Verificar si se guardó correctamente
        echo '<div class="notice notice-' . ($result ? 'success' : 'error') . '"><p><strong>💾 RESULTADO:</strong> ' . ($result ? 'Guardado exitosamente' : 'Error al guardar') . '</p></div>';
        
        // Verificar inmediatamente lo que se guardó
        $saved_config = $auto_ads->get_config();
        echo '<div class="notice notice-success"><p><strong>✅ VERIFICACIÓN:</strong> enabled guardado = ' . var_export($saved_config['enabled'], true) . '</p></div>';
    }

    /**
     * Mostrar estadísticas de ads automáticos
     */
    private function render_auto_ads_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aliexpress_ads_auto_performance';
        
        // Verificar si existe la tabla
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
            echo '<p>' . __('Las estadísticas estarán disponibles después de que los ads automáticos hayan estado activos por un tiempo.', 'aliexpress-smart-ads') . '</p>';
            return;
        }

        // Obtener datos de rendimiento
        $performance_data = $wpdb->get_results(
            "SELECT position_percentage, avg_ctr, impressions, clicks 
             FROM {$table_name} 
             WHERE impressions > 5 
             ORDER BY avg_ctr DESC 
             LIMIT 10"
        );

        if (empty($performance_data)) {
            echo '<p>' . __('Aún no hay suficientes datos para mostrar estadísticas.', 'aliexpress-smart-ads') . '</p>';
            return;
        }

        ?>
        <div class="ali-auto-stats">
            <h4><?php _e('Mejores Posiciones por CTR', 'aliexpress-smart-ads'); ?></h4>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Posición en Contenido', 'aliexpress-smart-ads'); ?></th>
                        <th><?php _e('CTR', 'aliexpress-smart-ads'); ?></th>
                        <th><?php _e('Impresiones', 'aliexpress-smart-ads'); ?></th>
                        <th><?php _e('Clics', 'aliexpress-smart-ads'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($performance_data as $data): ?>
                        <tr>
                            <td><?php echo round($data->position_percentage * 100, 1); ?>%</td>
                            <td><span style="color: green; font-weight: bold;"><?php echo round($data->avg_ctr * 100, 2); ?>%</span></td>
                            <td><?php echo number_format($data->impressions); ?></td>
                            <td><?php echo number_format($data->clicks); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <p class="description">
                <?php _e('El sistema utiliza estos datos para optimizar automáticamente la colocación de ads en futuras publicaciones.', 'aliexpress-smart-ads'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Página de Código HTML para banners de AliExpress
     */
    public function html_banner_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para acceder a esta página.', 'aliexpress-smart-ads'));
        }

        // Procesar guardado de banner HTML
        if (isset($_POST['save_html_banner']) && check_admin_referer('ali_html_banner_nonce', 'ali_html_banner_nonce')) {
            $this->process_html_banner_save();
        }

        // Obtener banners HTML existentes
        $html_banners = $this->get_html_banners();
        
        ?>
        <div class="wrap">
            <h1><?php _e('📋 Código HTML - Banners AliExpress', 'aliexpress-smart-ads'); ?></h1>
            
            <div class="ali-html-banner-header" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); color: white; padding: 20px; border-radius: 10px; margin: 20px 0;">
                <h2 style="color: white; margin: 0;"><?php _e('🚀 Pega directamente el código HTML de AliExpress', 'aliexpress-smart-ads'); ?></h2>
                <p style="margin: 10px 0 0 0; opacity: 0.9;"><?php _e('Copia y pega los códigos HTML que generas desde tu panel de afiliados de AliExpress. El sistema extraerá automáticamente los datos necesarios.', 'aliexpress-smart-ads'); ?></p>
            </div>

            <?php
            $affiliate_id = get_option('ali_ads_affiliate_id', '');
            if (empty($affiliate_id)):
            ?>
            <div class="notice notice-warning" style="margin: 20px 0;">
                <p>
                    <strong><?php _e('⚠️ Configuración recomendada:', 'aliexpress-smart-ads'); ?></strong> 
                    <?php _e('Para usar enlaces por defecto, primero configura tu ID de afiliado en', 'aliexpress-smart-ads'); ?>
                    <a href="<?php echo admin_url('admin.php?page=aliexpress-ads-affiliate'); ?>" class="button button-small" style="margin-left: 8px;">
                        <?php _e('🏷️ ID Afiliado', 'aliexpress-smart-ads'); ?>
                    </a>
                </p>
            </div>
            <?php endif; ?>

            <!-- Formulario para añadir nuevo banner HTML -->
            <div class="postbox" style="margin-bottom: 30px;">
                <div class="postbox-header">
                    <h2 class="hndle"><?php _e('➕ Añadir Nuevo Banner desde Código HTML', 'aliexpress-smart-ads'); ?></h2>
                </div>
                <div class="inside">
                    <form method="post" action="" id="html-banner-form">
                        <?php wp_nonce_field('ali_html_banner_nonce', 'ali_html_banner_nonce'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="html_code"><?php _e('Código HTML de AliExpress', 'aliexpress-smart-ads'); ?></label>
                                </th>
                                <td>
                                    <textarea id="html_code" name="html_code" rows="6" cols="80" class="large-text code" 
                                              placeholder="<a href='https://s.click.aliexpress.com/e/_DeFgHiJ?bz=728*90' target='_parent'><img width='728' height='90' src='https://ae-pic-a1.aliexpress-media.com/kf/Sa1b2c3d4e5f6789abc123def456ghij.jpg' /></a>"></textarea>
                                    <p class="description">
                                        <?php _e('Pega aquí el código HTML completo que copiaste desde tu panel de afiliados de AliExpress. El sistema automáticamente ajustará las dimensiones y añadirá el parámetro bz= según el tamaño seleccionado arriba.', 'aliexpress-smart-ads'); ?>
                                        <br><strong><?php _e('Ejemplo:', 'aliexpress-smart-ads'); ?></strong>
                                        <code>&lt;a href='https://s.click.aliexpress.com/e/_DeFgHiJ' target='_parent'&gt;&lt;img width='300' height='250' src='https://ae-pic-a1.aliexpress-media.com/kf/Sa1b2c3d4e5f6789abc123def456ghij.jpg' /&gt;&lt;/a&gt;</code>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="banner_title"><?php _e('Título del Banner', 'aliexpress-smart-ads'); ?> *</label>
                                </th>
                                <td>
                                    <input type="text" id="banner_title" name="banner_title" class="regular-text" 
                                           placeholder="<?php _e('Ej: Black Friday 2025 - Ofertas Tech', 'aliexpress-smart-ads'); ?>" required>
                                    <p class="description"><?php _e('Nombre interno para identificar este banner en el admin.', 'aliexpress-smart-ads'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="banner_category"><?php _e('Categoría', 'aliexpress-smart-ads'); ?></label>
                                </th>
                                <td>
                                    <select id="banner_category" name="banner_category">
                                        <option value="general"><?php _e('General', 'aliexpress-smart-ads'); ?></option>
                                        <option value="electronics"><?php _e('Electrónicos', 'aliexpress-smart-ads'); ?></option>
                                        <option value="fashion"><?php _e('Moda y Ropa', 'aliexpress-smart-ads'); ?></option>
                                        <option value="home"><?php _e('Hogar y Jardín', 'aliexpress-smart-ads'); ?></option>
                                        <option value="sports"><?php _e('Deportes', 'aliexpress-smart-ads'); ?></option>
                                        <option value="beauty"><?php _e('Belleza y Salud', 'aliexpress-smart-ads'); ?></option>
                                        <option value="automotive"><?php _e('Automóviles', 'aliexpress-smart-ads'); ?></option>
                                        <option value="toys"><?php _e('Juguetes', 'aliexpress-smart-ads'); ?></option>
                                    </select>
                                    <p class="description"><?php _e('Categoría para mostrar este banner en posts relacionados.', 'aliexpress-smart-ads'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="banner_placement"><?php _e('Ubicación', 'aliexpress-smart-ads'); ?></label>
                                </th>
                                <td>
                                    <select id="banner_placement" name="banner_placement">
                                        <option value=""><?php _e('Sin inserción automática', 'aliexpress-smart-ads'); ?></option>
                                        <option value="header"><?php _e('Header (Cabecera)', 'aliexpress-smart-ads'); ?></option>
                                        <option value="in_content"><?php _e('Dentro del contenido', 'aliexpress-smart-ads'); ?></option>
                                        <option value="sidebar"><?php _e('Sidebar (Barra lateral)', 'aliexpress-smart-ads'); ?></option>
                                        <option value="footer"><?php _e('Footer (Pie de página)', 'aliexpress-smart-ads'); ?></option>
                                        <option value="floating_bar"><?php _e('Barra flotante', 'aliexpress-smart-ads'); ?></option>
                                        <option value="between_articles"><?php _e('Entre artículos', 'aliexpress-smart-ads'); ?></option>
                                    </select>
                                    <p class="description"><?php _e('Dónde quieres que aparezca automáticamente este banner.', 'aliexpress-smart-ads'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="banner_size"><?php _e('Tamaño del Banner', 'aliexpress-smart-ads'); ?></label>
                                </th>
                                <td>
                                    <select id="banner_size" name="banner_size" class="regular-text">
                                        <optgroup label="<?php _e('📱 Banner Horizontal', 'aliexpress-smart-ads'); ?>">
                                            <option value="300*250" selected><?php _e('300×250 px - Rectángulo Medio', 'aliexpress-smart-ads'); ?></option>
                                            <option value="500*500"><?php _e('500×500 px - Cuadrado Grande', 'aliexpress-smart-ads'); ?></option>
                                            <option value="728*90"><?php _e('728×90 px - Banner Superior', 'aliexpress-smart-ads'); ?></option>
                                        </optgroup>
                                        <optgroup label="<?php _e('📏 Banner Vertical', 'aliexpress-smart-ads'); ?>">
                                            <option value="190*240"><?php _e('190×240 px - Vertical Pequeño', 'aliexpress-smart-ads'); ?></option>
                                            <option value="120*600"><?php _e('120×600 px - Rascacielos', 'aliexpress-smart-ads'); ?></option>
                                            <option value="160*600"><?php _e('160×600 px - Rascacielos Ancho', 'aliexpress-smart-ads'); ?></option>
                                            <option value="320*480"><?php _e('320×480 px - Banner Móvil', 'aliexpress-smart-ads'); ?></option>
                                        </optgroup>
                                    </select>
                                    <p class="description">
                                        <?php _e('Selecciona el tamaño de banner que prefieras. Esto automáticamente añadirá el parámetro bz= a la URL y ajustará las dimensiones width/height de la imagen.', 'aliexpress-smart-ads'); ?>
                                        <br><strong><?php _e('💡 Ejemplo:', 'aliexpress-smart-ads'); ?></strong>
                                        <code>?bz=300*250</code> <?php _e('se añadirá automáticamente a la URL del banner', 'aliexpress-smart-ads'); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="use_default_link"><?php _e('Tipo de Enlace', 'aliexpress-smart-ads'); ?></label>
                                </th>
                                <td>
                                    <div style="margin-bottom: 15px;">
                                        <label style="display: block; margin-bottom: 8px;">
                                            <input type="radio" name="link_type" value="html" checked> 
                                            <?php _e('🔗 Usar enlace del código HTML', 'aliexpress-smart-ads'); ?>
                                        </label>
                                        <label style="display: block;">
                                            <input type="radio" name="link_type" value="default"> 
                                            <?php _e('🛍️ Usar enlace por defecto de AliExpress', 'aliexpress-smart-ads'); ?>
                                        </label>
                                    </div>
                                    
                                    <div id="default-link-config" style="display: none; background: #f9f9f9; padding: 15px; border-radius: 5px; margin-top: 10px;">
                                        <label for="default_url"><?php _e('URL por Defecto:', 'aliexpress-smart-ads'); ?></label><br>
                                        <input type="url" id="default_url" name="default_url" class="regular-text" 
                                               placeholder="https://s.click.aliexpress.com/e/_DeFgHiJ" 
                                               value="<?php echo esc_attr(get_option('ali_ads_default_url', '')); ?>">
                                        <p class="description">
                                            <?php _e('Enlace por defecto cuando no uses código HTML. Tu ID de afiliado se añadirá automáticamente.', 'aliexpress-smart-ads'); ?><br>
                                            <strong><?php _e('Ejemplo:', 'aliexpress-smart-ads'); ?></strong> <code>https://s.click.aliexpress.com/e/_DeFgHiJ</code>
                                        </p>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="banner_dates"><?php _e('Fechas de Campaña', 'aliexpress-smart-ads'); ?></label>
                                </th>
                                <td>
                                    <div style="display: flex; gap: 20px; align-items: center;">
                                        <div>
                                            <label><?php _e('Desde:', 'aliexpress-smart-ads'); ?></label><br>
                                            <input type="date" id="start_date" name="start_date">
                                        </div>
                                        <div>
                                            <label><?php _e('Hasta:', 'aliexpress-smart-ads'); ?></label><br>
                                            <input type="date" id="end_date" name="end_date">
                                        </div>
                                    </div>
                                    <p class="description"><?php _e('Opcional: Programa cuándo debe mostrarse este banner (útil para campañas temporales).', 'aliexpress-smart-ads'); ?></p>
                                </td>
                            </tr>
                        </table>

                        <!-- Vista previa del banner -->
                        <div id="banner-preview" style="margin: 20px 0; padding: 20px; background: #f9f9f9; border: 1px dashed #ccc; border-radius: 8px; display: none;">
                            <h3><?php _e('📷 Vista Previa:', 'aliexpress-smart-ads'); ?></h3>
                            <div id="preview-content" style="text-align: center; margin: 15px 0;"></div>
                            <div id="extracted-info" style="background: white; padding: 15px; border-radius: 5px; margin-top: 15px;">
                                <h4><?php _e('📊 Información Extraída:', 'aliexpress-smart-ads'); ?></h4>
                                <div id="info-details"></div>
                            </div>
                        </div>

                        <p class="submit">
                            <input type="submit" name="save_html_banner" class="button-primary button-hero" 
                                   value="<?php _e('💾 Guardar Banner HTML', 'aliexpress-smart-ads'); ?>">
                            <button type="button" id="preview-banner" class="button button-secondary">
                                <?php _e('👁️ Vista Previa', 'aliexpress-smart-ads'); ?>
                            </button>
                        </p>
                    </form>
                </div>
            </div>

            <!-- Lista de banners HTML existentes -->
            <div class="postbox">
                <div class="postbox-header">
                    <h2 class="hndle"><?php _e('📋 Banners HTML Guardados', 'aliexpress-smart-ads'); ?></h2>
                </div>
                <div class="inside">
                    <?php if (empty($html_banners)): ?>
                        <p class="description"><?php _e('No hay banners HTML guardados aún. Añade el primero usando el formulario de arriba.', 'aliexpress-smart-ads'); ?></p>
                    <?php else: ?>
                        <div class="ali-html-banners-grid" style="display: grid; gap: 20px;">
                            <?php foreach ($html_banners as $banner): ?>
                                <div class="ali-html-banner-card" style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; background: white;">
                                    <div style="display: grid; grid-template-columns: 200px 1fr auto; gap: 15px; align-items: center;">
                                        
                                        <!-- Preview del banner -->
                                        <div class="banner-preview" style="text-align: center; border: 1px solid #eee; padding: 10px; border-radius: 5px;">
                                            <?php echo wp_kses_post($banner->iframe_code); ?>
                                        </div>
                                        
                                        <!-- Información del banner -->
                                        <div class="banner-info">
                                            <h4 style="margin: 0 0 8px 0;">
                                                <?php echo esc_html($banner->title); ?>
                                                <?php if ($banner->is_active): ?>
                                                    <span class="status-badge" style="background: #46b450; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; margin-left: 8px;">ACTIVO</span>
                                                <?php else: ?>
                                                    <span class="status-badge" style="background: #dc3232; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; margin-left: 8px;">INACTIVO</span>
                                                <?php endif; ?>
                                            </h4>
                                            <div style="font-size: 13px; color: #666;">
                                                <p><strong><?php _e('Categoría:', 'aliexpress-smart-ads'); ?></strong> <?php echo esc_html($banner->category ?: 'General'); ?></p>
                                                <p><strong><?php _e('Ubicación:', 'aliexpress-smart-ads'); ?></strong> <?php echo esc_html($banner->placement ?: 'Manual'); ?></p>
                                                <?php if ($banner->start_date || $banner->end_date): ?>
                                                    <p><strong><?php _e('Fechas:', 'aliexpress-smart-ads'); ?></strong> 
                                                        <?php echo $banner->start_date ? date('d/m/Y', strtotime($banner->start_date)) : ''; ?>
                                                        <?php echo ($banner->start_date && $banner->end_date) ? ' - ' : ''; ?>
                                                        <?php echo $banner->end_date ? date('d/m/Y', strtotime($banner->end_date)) : ''; ?>
                                                    </p>
                                                <?php endif; ?>
                                                <p><strong><?php _e('Creado:', 'aliexpress-smart-ads'); ?></strong> <?php echo date('d/m/Y H:i', strtotime($banner->created_at)); ?></p>
                                            </div>
                                        </div>
                                        
                                        <!-- Acciones -->
                                        <div class="banner-actions" style="text-align: right;">
                                            <button class="button button-small toggle-banner" 
                                                    data-banner-id="<?php echo $banner->id; ?>"
                                                    data-current-status="<?php echo $banner->is_active ? '1' : '0'; ?>">
                                                <?php echo $banner->is_active ? __('🔇 Desactivar', 'aliexpress-smart-ads') : __('🔊 Activar', 'aliexpress-smart-ads'); ?>
                                            </button>
                                            <br><br>
                                            <button class="button button-small edit-html-banner" 
                                                    data-banner-id="<?php echo $banner->id; ?>">
                                                <?php _e('✏️ Editar', 'aliexpress-smart-ads'); ?>
                                            </button>
                                            <br><br>
                                            <button class="button button-small button-link-delete delete-banner" 
                                                    data-banner-id="<?php echo $banner->id; ?>"
                                                    onclick="return confirm('<?php _e('¿Estás seguro de eliminar este banner?', 'aliexpress-smart-ads'); ?>')">
                                                <?php _e('🗑️ Eliminar', 'aliexpress-smart-ads'); ?>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Shortcode para uso manual -->
                                    <div style="margin-top: 15px; padding: 10px; background: #f0f0f1; border-radius: 5px;">
                                        <strong><?php _e('Shortcode:', 'aliexpress-smart-ads'); ?></strong>
                                        <code style="background: white; padding: 4px 8px; border-radius: 3px; margin-left: 8px;">[ali_banner id="<?php echo $banner->id; ?>"]</code>
                                        <button class="button button-small copy-shortcode" data-shortcode='[ali_banner id="<?php echo $banner->id; ?>"]' style="margin-left: 8px;">
                                            <?php _e('📋 Copiar', 'aliexpress-smart-ads'); ?>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- JavaScript para funcionalidad -->
        <script>
        jQuery(document).ready(function($) {
            
            // Toggle tipo de enlace
            $('input[name="link_type"]').change(function() {
                if ($(this).val() === 'default') {
                    $('#default-link-config').slideDown();
                    $('#html_code').removeAttr('required');
                    $('label[for="html_code"]').html('<?php _e('Código HTML de AliExpress', 'aliexpress-smart-ads'); ?>');
                } else {
                    $('#default-link-config').slideUp();
                    $('#html_code').attr('required', 'required');
                    $('label[for="html_code"]').html('<?php _e('Código HTML de AliExpress', 'aliexpress-smart-ads'); ?> *');
                }
            });

            // Validar formulario antes de enviar
            $('#html-banner-form').submit(function(e) {
                var linkType = $('input[name="link_type"]:checked').val();
                var htmlCode = $('#html_code').val().trim();
                var defaultUrl = $('#default_url').val().trim();

                if (linkType === 'html' && !htmlCode) {
                    alert('<?php _e('Por favor, pega el código HTML.', 'aliexpress-smart-ads'); ?>');
                    e.preventDefault();
                    return false;
                }

                if (linkType === 'default' && !defaultUrl) {
                    alert('<?php _e('Por favor, especifica una URL por defecto o configúrala en "ID Afiliado".', 'aliexpress-smart-ads'); ?>');
                    e.preventDefault();
                    return false;
                }

                return true;
            });
            
            // Vista previa del banner
            $('#preview-banner').click(function() {
                var htmlCode = $('#html_code').val().trim();
                var bannerSize = $('#banner_size').val();
                
                if (!htmlCode) {
                    alert('<?php _e('Por favor, pega el código HTML primero.', 'aliexpress-smart-ads'); ?>');
                    return;
                }
                
                // Actualizar HTML con el tamaño seleccionado
                var updatedHtml = updateHtmlWithBannerSize(htmlCode, bannerSize);
                
                // Mostrar vista previa
                $('#preview-content').html(updatedHtml);
                
                // Extraer información
                var info = extractBannerInfo(updatedHtml);
                displayExtractedInfo(info);
                
                $('#banner-preview').slideDown();
            });
            
            // Actualizar vista previa cuando cambia el tamaño
            $('#banner_size').change(function() {
                if ($('#banner-preview').is(':visible')) {
                    $('#preview-banner').click();
                }
            });
            
            // Función para actualizar HTML con nuevo tamaño de banner
            function updateHtmlWithBannerSize(html, bannerSize) {
                var $temp = $('<div>').html(html);
                var $link = $temp.find('a').first();
                var $img = $temp.find('img').first();
                
                if ($link.length && $img.length) {
                    // Actualizar dimensiones
                    var dimensions = bannerSize.split('*');
                    if (dimensions.length === 2) {
                        $img.attr('width', dimensions[0]);
                        $img.attr('height', dimensions[1]);
                    }
                    
                    // Actualizar URL con parámetro bz=
                    var currentUrl = $link.attr('href');
                    if (currentUrl) {
                        var updatedUrl = addBannerSizeToUrl(currentUrl, bannerSize);
                        $link.attr('href', updatedUrl);
                    }
                }
                
                return $temp.html();
            }
            
            // Función para añadir parámetro bz= a la URL
            function addBannerSizeToUrl(url, bannerSize) {
                // Si ya tiene bz=, reemplázalo
                if (url.includes('?bz=') || url.includes('&bz=')) {
                    return url.replace(/(\?|&)bz=[^&]*/, '$1bz=' + bannerSize);
                }
                
                // Añadir nuevo parámetro
                var separator = url.includes('?') ? '&' : '?';
                return url + separator + 'bz=' + bannerSize;
            }
            
            // Función para extraer información del HTML
            function extractBannerInfo(html) {
                var $temp = $('<div>').html(html);
                var $link = $temp.find('a').first();
                var $img = $temp.find('img').first();
                
                var info = {
                    linkUrl: $link.attr('href') || '',
                    imageUrl: $img.attr('src') || '',
                    width: $img.attr('width') || '',
                    height: $img.attr('height') || '',
                    target: $link.attr('target') || '_self'
                };
                
                // Extraer ID de tracking si existe
                if (info.linkUrl) {
                    var match = info.linkUrl.match(/\/e\/([^?]+)/);
                    if (match) {
                        info.trackingId = match[1];
                    }
                    
                    // Extraer parámetros de tamaño
                    var sizeMatch = info.linkUrl.match(/bz=(\d+)\*(\d+)/);
                    if (sizeMatch) {
                        info.suggestedWidth = sizeMatch[1];
                        info.suggestedHeight = sizeMatch[2];
                    }
                }
                
                return info;
            }
            
            // Mostrar información extraída
            function displayExtractedInfo(info) {
                var html = '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 13px;">';
                
                html += '<div>';
                html += '<strong>🔗 URL de Destino:</strong><br>';
                html += '<span style="word-break: break-all; color: #0073aa;">' + (info.linkUrl || 'No encontrada') + '</span><br><br>';
                
                html += '<strong>🖼️ URL de Imagen:</strong><br>';
                html += '<span style="word-break: break-all; color: #0073aa;">' + (info.imageUrl || 'No encontrada') + '</span><br><br>';
                
                html += '<strong>📏 Dimensiones:</strong><br>';
                html += (info.width && info.height ? info.width + ' x ' + info.height + ' px' : 'No especificadas');
                html += '</div>';
                
                html += '<div>';
                if (info.trackingId) {
                    html += '<strong>🎯 ID de Tracking:</strong><br>';
                    html += '<code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">' + info.trackingId + '</code><br><br>';
                }
                
                if (info.suggestedWidth && info.suggestedHeight) {
                    html += '<strong>📐 Tamaño Sugerido:</strong><br>';
                    html += info.suggestedWidth + ' x ' + info.suggestedHeight + ' px<br><br>';
                }
                
                html += '<strong>🎯 Target:</strong><br>';
                html += info.target;
                html += '</div>';
                
                html += '</div>';
                
                $('#info-details').html(html);
            }
            
            // Copiar shortcode
            $(document).on('click', '.copy-shortcode', function() {
                var shortcode = $(this).data('shortcode');
                navigator.clipboard.writeText(shortcode).then(function() {
                    alert('<?php _e('Shortcode copiado al portapapeles!', 'aliexpress-smart-ads'); ?>');
                });
            });
            
            // Toggle banner activo/inactivo
            $(document).on('click', '.toggle-banner', function() {
                var $btn = $(this);
                var bannerId = $btn.data('banner-id');
                var currentStatus = $btn.data('current-status');
                var newStatus = currentStatus === '1' ? '0' : '1';
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ali_toggle_banner',
                        banner_id: bannerId,
                        status: newStatus,
                        nonce: '<?php echo wp_create_nonce('ali_toggle_banner'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    }
                });
            });
            
            // Eliminar banner
            $(document).on('click', '.delete-banner', function() {
                var bannerId = $(this).data('banner-id');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ali_delete_banner',
                        banner_id: bannerId,
                        nonce: '<?php echo wp_create_nonce('ali_delete_banner'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    }
                });
            });
        });
        </script>

        <!-- Estilos CSS -->
        <style>
        .ali-html-banner-card {
            transition: box-shadow 0.3s ease;
        }
        
        .ali-html-banner-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .status-badge {
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .banner-preview img {
            max-width: 180px;
            height: auto;
            border-radius: 4px;
        }
        
        @media (max-width: 768px) {
            .ali-html-banner-card > div {
                grid-template-columns: 1fr !important;
                text-align: center;
            }
            
            .banner-actions {
                text-align: center !important;
            }
        }
        </style>
        <?php
    }

    /**
     * Procesar guardado de banner HTML
     */
    private function process_html_banner_save() {
        // Validar datos
        $title = sanitize_text_field($_POST['banner_title']);
        $category = sanitize_text_field($_POST['banner_category']);
        $placement = sanitize_text_field($_POST['banner_placement']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        $link_type = sanitize_text_field($_POST['link_type']);
        
        if (empty($title)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . __('El título es obligatorio.', 'aliexpress-smart-ads') . '</p></div>';
            });
            return;
        }

        $banner_data = array(
            'title' => $title,
            'category' => $category ?: 'general',
            'placement' => $placement,
            'is_active' => 1,
            'start_date' => $start_date ?: null,
            'end_date' => $end_date ?: null,
            'created_at' => current_time('mysql')
        );

        if ($link_type === 'default') {
            // Usar enlace por defecto
            $default_url = sanitize_text_field($_POST['default_url']) ?: get_option('ali_ads_default_url', '');
            $affiliate_id = get_option('ali_ads_affiliate_id', '');
            $default_text = get_option('ali_ads_default_text', '� ¡Ofertas Increíbles en AliExpress!');
            
            if (empty($default_url) || empty($affiliate_id)) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error"><p>' . __('Para usar enlace por defecto, primero configura tu ID de afiliado y URL base en "ID Afiliado".', 'aliexpress-smart-ads') . '</p></div>';
                });
                return;
            }

            // Crear URL completa con ID de afiliado
            $full_url = add_query_arg('aff_fcid', $affiliate_id, $default_url);
            
            // Crear código HTML por defecto
            $default_html = sprintf(
                '<a href="%s" target="_blank" rel="nofollow sponsored" style="display: inline-block; background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%); color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; box-shadow: 0 4px 15px rgba(255,107,53,0.3); transition: transform 0.2s ease;">%s</a>',
                esc_url($full_url),
                esc_html($default_text)
            );

            // Actualizar URL por defecto si se proporcionó una nueva
            if (!empty($_POST['default_url'])) {
                update_option('ali_ads_default_url', $default_url);
            }

            $banner_data['type'] = 'iframe';
            $banner_data['iframe_code'] = $default_html;
            $banner_data['click_url'] = $full_url;
            $banner_data['image_url'] = ''; // No hay imagen para enlace por defecto

        } else {
            // Usar código HTML proporcionado
            $html_code = wp_kses_post($_POST['html_code']);
            $banner_size = sanitize_text_field($_POST['banner_size'] ?? '300*250');
            
            if (empty($html_code)) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error"><p>' . __('El código HTML es obligatorio cuando seleccionas "Usar enlace del código HTML".', 'aliexpress-smart-ads') . '</p></div>';
                });
                return;
            }

            // Extraer información del HTML
            $extracted = $this->extract_banner_info_from_html($html_code);

            // Modificar la URL para incluir el parámetro bz= con el tamaño seleccionado
            if (!empty($extracted['click_url'])) {
                $extracted['click_url'] = $this->modify_url_with_banner_size($extracted['click_url'], $banner_size);
            }

            // Actualizar las dimensiones según el tamaño seleccionado
            $dimensions = explode('*', $banner_size);
            if (count($dimensions) == 2) {
                $extracted['width'] = $dimensions[0];
                $extracted['height'] = $dimensions[1];
            }

            // Reconstruir el HTML con las nuevas dimensiones y URL
            $html_code = $this->rebuild_html_with_banner_size($html_code, $extracted, $banner_size);

            $banner_data['type'] = 'iframe';
            $banner_data['iframe_code'] = $html_code;
            $banner_data['image_url'] = $extracted['image_url'];
            $banner_data['click_url'] = $extracted['click_url'];
        }

        $result = Ali_Banner::create_banner($banner_data);

        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>' . __('✅ Banner guardado correctamente!', 'aliexpress-smart-ads') . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . __('❌ Error al guardar el banner. Inténtalo de nuevo.', 'aliexpress-smart-ads') . '</p></div>';
            });
        }
    }

    /**
     * Extraer información del código HTML
     * @param string $html_code
     * @return array
     */
    private function extract_banner_info_from_html($html_code) {
        $info = array(
            'click_url' => '',
            'image_url' => '',
            'width' => '',
            'height' => ''
        );

        // Usar DOMDocument para parsear el HTML
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html_code, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        // Extraer enlace
        $links = $dom->getElementsByTagName('a');
        if ($links->length > 0) {
            $info['click_url'] = $links->item(0)->getAttribute('href');
        }

        // Extraer imagen
        $images = $dom->getElementsByTagName('img');
        if ($images->length > 0) {
            $img = $images->item(0);
            $info['image_url'] = $img->getAttribute('src');
            $info['width'] = $img->getAttribute('width');
            $info['height'] = $img->getAttribute('height');
        }

        return $info;
    }

    /**
     * Modificar URL para incluir el parámetro de tamaño de banner
     * @param string $url
     * @param string $banner_size
     * @return string
     */
    private function modify_url_with_banner_size($url, $banner_size) {
        // Si ya tiene el parámetro bz=, lo reemplaza
        if (strpos($url, '?bz=') !== false || strpos($url, '&bz=') !== false) {
            $url = preg_replace('/(\?|&)bz=[^&]*/', '$1bz=' . $banner_size, $url);
        } else {
            // Añadir el parámetro bz=
            $separator = (strpos($url, '?') !== false) ? '&' : '?';
            $url .= $separator . 'bz=' . $banner_size;
        }
        
        return $url;
    }

    /**
     * Reconstruir HTML con nuevo tamaño de banner
     * @param string $html_code
     * @param array $extracted
     * @param string $banner_size
     * @return string
     */
    private function rebuild_html_with_banner_size($html_code, $extracted, $banner_size) {
        $dimensions = explode('*', $banner_size);
        if (count($dimensions) != 2) {
            return $html_code;
        }

        $width = $dimensions[0];
        $height = $dimensions[1];

        // Usar DOMDocument para modificar el HTML
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html_code, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        // Actualizar enlaces
        $links = $dom->getElementsByTagName('a');
        if ($links->length > 0) {
            $links->item(0)->setAttribute('href', $extracted['click_url']);
        }

        // Actualizar imágenes
        $images = $dom->getElementsByTagName('img');
        if ($images->length > 0) {
            $img = $images->item(0);
            $img->setAttribute('width', $width);
            $img->setAttribute('height', $height);
        }

        return $dom->saveHTML();
    }

    /**
     * Obtener banners HTML existentes
     * @return array
     */
    private function get_html_banners() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aliexpress_ads';

        return $wpdb->get_results(
            "SELECT * FROM {$table_name} 
             WHERE type = 'iframe' AND iframe_code IS NOT NULL AND iframe_code != ''
             ORDER BY created_at DESC"
        );
    }

    /**
     * AJAX: Guardar banner HTML
     */
    public function save_html_banner() {
        check_ajax_referer('ali_html_banner_save', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción.', 'aliexpress-smart-ads'));
        }

        // Procesar datos igual que en process_html_banner_save
        $html_code = wp_kses_post($_POST['html_code']);
        $banner_size = sanitize_text_field($_POST['banner_size'] ?? '300*250');
        $title = sanitize_text_field($_POST['title']);
        $category = sanitize_text_field($_POST['category']);
        $placement = sanitize_text_field($_POST['placement']);

        if (empty($html_code) || empty($title)) {
            wp_send_json_error(__('El código HTML y el título son obligatorios.', 'aliexpress-smart-ads'));
        }

        $extracted = $this->extract_banner_info_from_html($html_code);

        // Modificar la URL para incluir el parámetro bz= con el tamaño seleccionado
        if (!empty($extracted['click_url'])) {
            $extracted['click_url'] = $this->modify_url_with_banner_size($extracted['click_url'], $banner_size);
        }

        // Actualizar las dimensiones según el tamaño seleccionado
        $dimensions = explode('*', $banner_size);
        if (count($dimensions) == 2) {
            $extracted['width'] = $dimensions[0];
            $extracted['height'] = $dimensions[1];
        }

        // Reconstruir el HTML con las nuevas dimensiones y URL
        $html_code = $this->rebuild_html_with_banner_size($html_code, $extracted, $banner_size);

        $banner_data = array(
            'title' => $title,
            'type' => 'iframe',
            'iframe_code' => $html_code,
            'image_url' => $extracted['image_url'],
            'click_url' => $extracted['click_url'],
            'category' => $category ?: 'general',
            'placement' => $placement,
            'is_active' => 1,
            'created_at' => current_time('mysql')
        );

        $result = Ali_Banner::create_banner($banner_data);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Banner guardado correctamente', 'aliexpress-smart-ads'),
                'banner_id' => $result
            ));
        } else {
            wp_send_json_error(__('Error al guardar el banner', 'aliexpress-smart-ads'));
        }
    }

    /**
     * Página de configuración de ID de Afiliado
     */
    public function affiliate_config_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para acceder a esta página.', 'aliexpress-smart-ads'));
        }

        // Procesar guardado de configuración
        if (isset($_POST['save_affiliate_config']) && check_admin_referer('ali_affiliate_config_nonce', 'ali_affiliate_config_nonce')) {
            $this->save_affiliate_config();
        }

        // Obtener configuración actual
        $affiliate_id = get_option('ali_ads_affiliate_id', '');
        $default_url = get_option('ali_ads_default_url', '');
        $default_text = get_option('ali_ads_default_text', '� ¡Ofertas Increíbles en AliExpress!');
        
        ?>
        <div class="wrap">
            <h1><?php _e('🏷️ Configuración de Afiliado AliExpress', 'aliexpress-smart-ads'); ?></h1>
            
            <div class="ali-affiliate-header" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); color: white; padding: 20px; border-radius: 10px; margin: 20px 0;">
                <h2 style="color: white; margin: 0;"><?php _e('💰 Configura tu ID de Afiliado', 'aliexpress-smart-ads'); ?></h2>
                <p style="margin: 10px 0 0 0; opacity: 0.9;"><?php _e('Configura tu ID de rastreo de AliExpress y enlaces por defecto para maximizar tus comisiones.', 'aliexpress-smart-ads'); ?></p>
            </div>

            <div class="ali-config-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                
                <!-- Formulario de configuración -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle"><?php _e('⚙️ Configuración Principal', 'aliexpress-smart-ads'); ?></h2>
                    </div>
                    <div class="inside">
                        <form method="post" action="">
                            <?php wp_nonce_field('ali_affiliate_config_nonce', 'ali_affiliate_config_nonce'); ?>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="affiliate_id"><?php _e('ID de Rastreo AliExpress', 'aliexpress-smart-ads'); ?> *</label>
                                    </th>
                                    <td>
                                        <input type="text" id="affiliate_id" name="affiliate_id" 
                                               value="<?php echo esc_attr($affiliate_id); ?>" 
                                               class="regular-text" 
                                               placeholder="<?php _e('Ej: XUYW3HAPBEN7D', 'aliexpress-smart-ads'); ?>" required>
                                        <p class="description">
                                            <?php _e('Tu ID de rastreo de AliExpress (Tracking ID). Lo encuentras en tu panel de afiliados.', 'aliexpress-smart-ads'); ?>
                                            <br><strong><?php _e('¿Dónde encontrarlo?', 'aliexpress-smart-ads'); ?></strong> 
                                            Portal de Afiliados AliExpress > Obtener enlace > "ID de rastreo"
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label for="default_url"><?php _e('URL Base por Defecto', 'aliexpress-smart-ads'); ?></label>
                                    </th>
                                    <td>
                                        <input type="url" id="default_url" name="default_url" 
                                               value="<?php echo esc_attr($default_url); ?>" 
                                               class="regular-text" 
                                               placeholder="https://s.click.aliexpress.com/e/_DDcXxY9Z">
                                        <p class="description">
                                            <?php _e('URL base que se usará cuando no especifiques código HTML. Tu ID de rastreo se añadirá automáticamente.', 'aliexpress-smart-ads'); ?>
                                            <br><strong><?php _e('Ejemplo:', 'aliexpress-smart-ads'); ?></strong> 
                                            <code>https://s.click.aliexpress.com/e/_DDcXxY9Z</code>
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label for="default_text"><?php _e('Texto del Enlace por Defecto', 'aliexpress-smart-ads'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="default_text" name="default_text" 
                                               value="<?php echo esc_attr($default_text); ?>" 
                                               class="regular-text">
                                        <p class="description">
                                            <?php _e('Texto que aparecerá en los enlaces por defecto cuando no uses código HTML.', 'aliexpress-smart-ads'); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p class="submit">
                                <input type="submit" name="save_affiliate_config" class="button-primary button-hero" 
                                       value="<?php _e('💾 Guardar Configuración', 'aliexpress-smart-ads'); ?>">
                            </p>
                        </form>
                    </div>
                </div>

                <!-- Panel de información -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle"><?php _e('📋 Información', 'aliexpress-smart-ads'); ?></h2>
                    </div>
                    <div class="inside">
                        <h4><?php _e('¿Cómo obtener tu ID de Afiliado?', 'aliexpress-smart-ads'); ?></h4>
                        <ol style="padding-left: 20px;">
                            <li><?php _e('Ve a tu panel de AliExpress Partners', 'aliexpress-smart-ads'); ?></li>
                            <li><?php _e('Haz clic en "Obtener enlace"', 'aliexpress-smart-ads'); ?></li>
                            <li><?php _e('Busca "ID de rastreo" en el formulario', 'aliexpress-smart-ads'); ?></li>
                            <li><?php _e('Copia el código (ej: XUYW3HAPBEN7D)', 'aliexpress-smart-ads'); ?></li>
                        </ol>

                        <hr style="margin: 20px 0;">

                        <h4><?php _e('🎯 ¿Cómo funciona?', 'aliexpress-smart-ads'); ?></h4>
                        <p style="font-size: 13px;">
                            <?php _e('Cuando no uses código HTML específico, el plugin creará enlaces automáticamente usando tu configuración:', 'aliexpress-smart-ads'); ?>
                        </p>
                        <div style="background: #f0f0f0; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px; margin: 10px 0;">
                            &lt;a href="<?php echo esc_html($default_url ?: 'https://s.click.aliexpress.com/e/_DDcXxY9Z'); ?>?aff_fcid=<?php echo esc_html($affiliate_id ?: 'XUYW3HAPBEN7D'); ?>" target="_blank" rel="nofollow sponsored"&gt;<br>
                            &nbsp;&nbsp;<?php echo esc_html($default_text); ?><br>
                            &lt;/a&gt;
                        </div>

                        <hr style="margin: 20px 0;">

                        <h4><?php _e('✅ Estado Actual:', 'aliexpress-smart-ads'); ?></h4>
                        <?php if ($affiliate_id): ?>
                            <p style="color: green;">
                                <strong>✅ <?php _e('ID configurado:', 'aliexpress-smart-ads'); ?></strong> 
                                <code><?php echo esc_html($affiliate_id); ?></code>
                            </p>
                        <?php else: ?>
                            <p style="color: red;">
                                <strong>❌ <?php _e('ID no configurado', 'aliexpress-smart-ads'); ?></strong>
                            </p>
                        <?php endif; ?>

                        <?php if ($default_url): ?>
                            <p style="color: green;">
                                <strong>✅ <?php _e('URL base configurada', 'aliexpress-smart-ads'); ?></strong>
                            </p>
                        <?php else: ?>
                            <p style="color: orange;">
                                <strong>⚠️ <?php _e('URL base no configurada', 'aliexpress-smart-ads'); ?></strong>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Vista previa del enlace generado -->
            <?php if ($affiliate_id && $default_url): ?>
            <div class="postbox" style="margin-top: 20px;">
                <div class="postbox-header">
                    <h2 class="hndle"><?php _e('👁️ Vista Previa del Enlace por Defecto', 'aliexpress-smart-ads'); ?></h2>
                </div>
                <div class="inside">
                    <p><?php _e('Así se verá tu enlace por defecto:', 'aliexpress-smart-ads'); ?></p>
                    <div style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 5px; margin: 15px 0;">
                        <?php
                        $preview_url = add_query_arg('aff_fcid', $affiliate_id, $default_url);
                        ?>
                        <a href="<?php echo esc_url($preview_url); ?>" target="_blank" rel="nofollow sponsored" 
                           style="display: inline-block; background: #ff6b35; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                            <?php echo esc_html($default_text); ?>
                        </a>
                    </div>
                    <p class="description">
                        <strong><?php _e('URL completa:', 'aliexpress-smart-ads'); ?></strong>
                        <br><code><?php echo esc_html($preview_url); ?></code>
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <style>
        @media (max-width: 768px) {
            .ali-config-grid {
                grid-template-columns: 1fr !important;
            }
        }
        </style>
        <?php
    }

    /**
     * Guardar configuración de afiliado
     */
    private function save_affiliate_config() {
        $affiliate_id = sanitize_text_field($_POST['affiliate_id']);
        $default_url = esc_url_raw($_POST['default_url']);
        $default_text = sanitize_text_field($_POST['default_text']);

        // Validar ID de afiliado
        if (empty($affiliate_id)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . __('El ID de rastreo es obligatorio.', 'aliexpress-smart-ads') . '</p></div>';
            });
            return;
        }

        // Guardar opciones
        update_option('ali_ads_affiliate_id', $affiliate_id);
        update_option('ali_ads_default_url', $default_url);
        update_option('ali_ads_default_text', $default_text);

        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>' . __('✅ Configuración de afiliado guardada correctamente!', 'aliexpress-smart-ads') . '</p></div>';
        });
    }

    /**
     * Validar opciones
     * @param array $input
     * @return array
     */
    public function validate_options($input) {
        // Aquí puedes agregar validaciones personalizadas
        return $input;
    }
}