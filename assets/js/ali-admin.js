/**
 * JavaScript para AliExpress Smart Ads - Admin Panel
 * @package AliExpress_Smart_Ads
 */

(function($) {
    'use strict';
    
    window.aliAdsAdmin = {
        
        init: function() {
            $(document).ready(function() {
                aliAdsAdmin.setupBannerForm();
                aliAdsAdmin.setupImageUploader();
                aliAdsAdmin.setupToggleButtons();
                aliAdsAdmin.setupDeleteConfirmation();
                aliAdsAdmin.setupPreviewModal();
                aliAdsAdmin.setupStatsCharts();
                aliAdsAdmin.setupBulkActions();
            });
        },
        
        // Configurar formulario de banner
        setupBannerForm: function() {
            // Toggle entre imagen e iframe
            $('input[name="banner_type"]').on('change', function() {
                var type = $(this).val();
                
                if (type === 'image') {
                    $('.image-fields').show();
                    $('.iframe-fields').hide();
                    aliAdsAdmin.validateImageFields();
                } else {
                    $('.image-fields').hide();
                    $('.iframe-fields').show();
                    aliAdsAdmin.validateIframeFields();
                }
            });
            
            // Validación en tiempo real
            $('#image_url').on('input', aliAdsAdmin.previewImage);
            $('#iframe_code').on('input', aliAdsAdmin.validateIframe);
            $('#target_url').on('input', aliAdsAdmin.validateUrl);
            
            // Auto-completar título basado en URL
            $('#target_url').on('blur', aliAdsAdmin.suggestTitle);
        },
        
        // Configurar uploader de imágenes
        setupImageUploader: function() {
            $('.upload-image-button').on('click', function(e) {
                e.preventDefault();
                
                var button = $(this);
                var inputField = button.siblings('input[type="url"]');
                
                var frame = wp.media({
                    title: 'Seleccionar imagen del banner',
                    button: {
                        text: 'Usar esta imagen'
                    },
                    multiple: false,
                    library: {
                        type: 'image'
                    }
                });
                
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    inputField.val(attachment.url);
                    aliAdsAdmin.previewImage.call(inputField[0]);
                });
                
                frame.open();
            });
        },
        
        // Vista previa de imagen
        previewImage: function() {
            var $input = $(this);
            var imageUrl = $input.val();
            var $preview = $input.siblings('.image-preview');
            
            if (!$preview.length) {
                $preview = $('<div class="image-preview"></div>');
                $input.parent().append($preview);
            }
            
            if (imageUrl && aliAdsAdmin.isValidImageUrl(imageUrl)) {
                var $img = $('<img>').attr('src', imageUrl).css({
                    'max-width': '200px',
                    'max-height': '150px',
                    'margin-top': '10px',
                    'border': '1px solid #ddd',
                    'border-radius': '3px'
                });
                
                $preview.html($img);
                
                // Verificar si la imagen carga correctamente
                $img.on('error', function() {
                    $preview.html('<p style="color: #dc3232;">Error: No se pudo cargar la imagen</p>');
                });
                
            } else {
                $preview.empty();
            }
        },
        
        // Validar URL de imagen
        isValidImageUrl: function(url) {
            var imageRegex = /\.(jpg|jpeg|png|gif|webp|svg)(\?.*)?$/i;
            return imageRegex.test(url);
        },
        
        // Validar iframe
        validateIframe: function() {
            var $textarea = $(this);
            var iframeCode = $textarea.val();
            var $feedback = $textarea.siblings('.iframe-feedback');
            
            if (!$feedback.length) {
                $feedback = $('<div class="iframe-feedback"></div>');
                $textarea.parent().append($feedback);
            }
            
            if (iframeCode.trim()) {
                if (aliAdsAdmin.isValidIframe(iframeCode)) {
                    $feedback.html('<span style="color: #46b450;">✓ Código iframe válido</span>');
                } else {
                    $feedback.html('<span style="color: #dc3232;">✗ Código iframe inválido</span>');
                }
            } else {
                $feedback.empty();
            }
        },
        
        // Verificar si el iframe es válido
        isValidIframe: function(code) {
            var iframeRegex = /<iframe[^>]+src=["\']([^"\']*)["\'][^>]*>/i;
            var match = code.match(iframeRegex);
            
            if (!match) return false;
            
            var src = match[1];
            var allowedDomains = [
                'aliexpress.com',
                'affiliates.aliexpress.com',
                's.click.aliexpress.com'
            ];
            
            return allowedDomains.some(function(domain) {
                return src.indexOf(domain) !== -1;
            });
        },
        
        // Validar URL
        validateUrl: function() {
            var $input = $(this);
            var url = $input.val();
            var $feedback = $input.siblings('.url-feedback');
            
            if (!$feedback.length) {
                $feedback = $('<div class="url-feedback"></div>');
                $input.parent().append($feedback);
            }
            
            if (url.trim()) {
                if (aliAdsAdmin.isValidAliExpressUrl(url)) {
                    $feedback.html('<span style="color: #46b450;">✓ URL de AliExpress válida</span>');
                } else {
                    $feedback.html('<span style="color: #ffa500;">⚠ Advertencia: No parece ser una URL de AliExpress</span>');
                }
            } else {
                $feedback.empty();
            }
        },
        
        // Verificar URL de AliExpress
        isValidAliExpressUrl: function(url) {
            var aliDomains = [
                'aliexpress.com',
                's.click.aliexpress.com',
                'affiliates.aliexpress.com'
            ];
            
            return aliDomains.some(function(domain) {
                return url.indexOf(domain) !== -1;
            });
        },
        
        // Sugerir título basado en URL
        suggestTitle: function() {
            var $urlInput = $(this);
            var $titleInput = $('#title');
            var url = $urlInput.val();
            
            // Solo sugerir si el título está vacío
            if ($titleInput.val().trim() || !url) {
                return;
            }
            
            // Extraer información de la URL para sugerir título
            if (url.indexOf('aliexpress.com') !== -1) {
                $titleInput.val('Banner AliExpress - ' + new Date().toLocaleDateString());
            }
        },
        
        // Validar campos de imagen
        validateImageFields: function() {
            var $imageUrl = $('#image_url');
            var $targetUrl = $('#target_url');
            
            $imageUrl.prop('required', true);
            $targetUrl.prop('required', true);
            $('#iframe_code').prop('required', false);
        },
        
        // Validar campos de iframe
        validateIframeFields: function() {
            var $iframeCode = $('#iframe_code');
            
            $iframeCode.prop('required', true);
            $('#image_url').prop('required', false);
            $('#target_url').prop('required', false);
        },
        
        // Configurar botones toggle
        setupToggleButtons: function() {
            $('.ali-toggle-banner').on('click', function() {
                var $btn = $(this);
                var bannerId = $btn.data('banner-id');
                var currentActive = $btn.data('active');
                var newActive = currentActive ? 0 : 1;
                
                $btn.prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ali_toggle_banner',
                        banner_id: bannerId,
                        active: newActive,
                        nonce: $('#_wpnonce').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            // Actualizar botón
                            $btn.data('active', newActive);
                            $btn.text(newActive ? 'Desactivar' : 'Activar');
                            $btn.toggleClass('active inactive');
                            
                            // Mostrar notificación
                            aliAdsAdmin.showNotice(
                                'Banner ' + (newActive ? 'activado' : 'desactivado') + ' correctamente',
                                'success'
                            );
                        } else {
                            aliAdsAdmin.showNotice('Error al cambiar estado del banner', 'error');
                        }
                    },
                    error: function() {
                        aliAdsAdmin.showNotice('Error de conexión', 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });
        },
        
        // Configurar confirmación de eliminación
        setupDeleteConfirmation: function() {
            $('.delete-banner').on('click', function(e) {
                e.preventDefault();
                
                var $link = $(this);
                var bannerTitle = $link.closest('tr').find('.banner-title').text();
                
                if (confirm('¿Estás seguro de eliminar el banner "' + bannerTitle + '"?\n\nEsta acción no se puede deshacer.')) {
                    window.location.href = $link.attr('href');
                }
            });
        },
        
        // Modal de vista previa
        setupPreviewModal: function() {
            $('.preview-banner').on('click', function(e) {
                e.preventDefault();
                
                var bannerId = $(this).data('banner-id');
                aliAdsAdmin.showBannerPreview(bannerId);
            });
        },
        
        // Mostrar vista previa del banner
        showBannerPreview: function(bannerId) {
            // Crear modal
            var $modal = $('<div class="ali-modal-overlay"><div class="ali-modal"><div class="ali-modal-header"><h3>Vista Previa del Banner</h3><button class="ali-modal-close">&times;</button></div><div class="ali-modal-body"><div class="ali-loading">Cargando...</div></div></div></div>');
            
            $('body').append($modal);
            
            // Cargar vista previa
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ali_preview_banner',
                    banner_id: bannerId,
                    nonce: $('#_wpnonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        $modal.find('.ali-modal-body').html(response.data.html);
                    } else {
                        $modal.find('.ali-modal-body').html('<p>Error al cargar la vista previa</p>');
                    }
                },
                error: function() {
                    $modal.find('.ali-modal-body').html('<p>Error de conexión</p>');
                }
            });
            
            // Cerrar modal
            $modal.on('click', '.ali-modal-close, .ali-modal-overlay', function(e) {
                if (e.target === this) {
                    $modal.remove();
                }
            });
        },
        
        // Configurar gráficos de estadísticas
        setupStatsCharts: function() {
            // Solo si estamos en la página de estadísticas
            if (!$('.ali-stats-chart').length) {
                return;
            }
            
            // Implementar gráficos con Chart.js si está disponible
            if (typeof Chart !== 'undefined') {
                aliAdsAdmin.renderStatsCharts();
            } else {
                // Fallback sin gráficos
                $('.ali-stats-chart').html('<p>Gráficos no disponibles</p>');
            }
        },
        
        // Renderizar gráficos
        renderStatsCharts: function() {
            var $chartContainer = $('.ali-stats-chart');
            
            if (!$chartContainer.length) return;
            
            // Obtener datos de estadísticas
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ali_get_chart_data',
                    nonce: $('#_wpnonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        aliAdsAdmin.createChart($chartContainer, response.data);
                    }
                }
            });
        },
        
        // Crear gráfico
        createChart: function($container, data) {
            var ctx = $container.find('canvas')[0].getContext('2d');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Clics',
                        data: data.clicks,
                        borderColor: '#0073aa',
                        tension: 0.1
                    }, {
                        label: 'Impresiones',
                        data: data.impressions,
                        borderColor: '#46b450',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        },
        
        // Acciones masivas
        setupBulkActions: function() {
            $('.ali-bulk-action').on('click', function() {
                var action = $(this).data('action');
                var $checkedBoxes = $('.banner-checkbox:checked');
                
                if ($checkedBoxes.length === 0) {
                    alert('Selecciona al menos un banner');
                    return;
                }
                
                var bannerIds = [];
                $checkedBoxes.each(function() {
                    bannerIds.push($(this).val());
                });
                
                if (confirm('¿Aplicar la acción "' + action + '" a ' + bannerIds.length + ' banner(s)?')) {
                    aliAdsAdmin.executeBulkAction(action, bannerIds);
                }
            });
            
            // Checkbox maestro
            $('.select-all-banners').on('change', function() {
                $('.banner-checkbox').prop('checked', $(this).is(':checked'));
            });
        },
        
        // Ejecutar acción masiva
        executeBulkAction: function(action, bannerIds) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ali_bulk_action',
                    bulk_action: action,
                    banner_ids: bannerIds,
                    nonce: $('#_wpnonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        aliAdsAdmin.showNotice('Acción ejecutada correctamente', 'success');
                        location.reload();
                    } else {
                        aliAdsAdmin.showNotice('Error al ejecutar la acción', 'error');
                    }
                },
                error: function() {
                    aliAdsAdmin.showNotice('Error de conexión', 'error');
                }
            });
        },
        
        // Mostrar notificación
        showNotice: function(message, type) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap h1').after($notice);
            
            // Auto-hide después de 5 segundos
            setTimeout(function() {
                $notice.fadeOut();
            }, 5000);
            
            // Botón de cerrar
            $notice.on('click', '.notice-dismiss', function() {
                $notice.fadeOut();
            });
        }
    };
    
    // Inicializar
    aliAdsAdmin.init();
    
})(jQuery);

// CSS para modal y notificaciones
jQuery(document).ready(function($) {
    var modalStyles = `
        .ali-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .ali-modal {
            background: white;
            border-radius: 8px;
            max-width: 600px;
            width: 90%;
            max-height: 80%;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .ali-modal-header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .ali-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        
        .ali-modal-body {
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .ali-loading {
            text-align: center;
            padding: 40px;
        }
    `;
    
    $('<style>').html(modalStyles).appendTo('head');
});