/**
 * JavaScript para AliExpress Smart Ads - Frontend
 * @package AliExpress_Smart_Ads
 */

(function($) {
    'use strict';
    
    // Objeto principal del plugin
    window.aliAds = {
        
        // Configuración
        config: {
            floatingBarCookieName: 'ali_ads_floating_closed',
            floatingBarCookieDays: 7,
            trackingEnabled: true,
            debug: false
        },
        
        // Inicialización
        init: function() {
            $(document).ready(function() {
                aliAds.setupFloatingBar();
                aliAds.setupClickTracking();
                aliAds.setupLazyLoading();
                aliAds.setupResponsive();
                aliAds.log('AliExpress Smart Ads initialized');
            });
        },
        
        // Configurar barra flotante
        setupFloatingBar: function() {
            var $floatingBars = $('.ali-placement-floating_bar');
            
            if ($floatingBars.length === 0) {
                return;
            }
            
            $floatingBars.each(function() {
                var $bar = $(this);
                
                // Verificar cookie de cierre
                if (aliAds.getCookie(aliAds.config.floatingBarCookieName)) {
                    $bar.hide();
                    return;
                }
                
                // Mostrar con animación
                $bar.addClass('slide-in');
                
                // Auto-hide después de 10 segundos si no hay interacción
                setTimeout(function() {
                    if (!$bar.hasClass('interacted')) {
                        aliAds.autoHideFloatingBar($bar);
                    }
                }, 10000);
                
                // Marcar como interactuado en hover
                $bar.on('mouseenter', function() {
                    $(this).addClass('interacted');
                });
            });
        },
        
        // Cerrar barra flotante
        closeFloatingBanner: function() {
            var $floatingBar = $('.ali-placement-floating_bar:visible');
            
            if ($floatingBar.length > 0) {
                $floatingBar.addClass('slide-out');
                
                setTimeout(function() {
                    $floatingBar.hide();
                }, 300);
                
                // Guardar cookie para no mostrar de nuevo
                aliAds.setCookie(aliAds.config.floatingBarCookieName, '1', aliAds.config.floatingBarCookieDays);
                
                aliAds.log('Floating banner closed by user');
            }
        },
        
        // Auto-ocultar barra flotante
        autoHideFloatingBar: function($bar) {
            if (!$bar || $bar.length === 0) {
                return;
            }
            
            $bar.fadeOut(500);
            aliAds.log('Floating banner auto-hidden');
        },
        
        // Configurar tracking de clics
        setupClickTracking: function() {
            if (!aliAds.config.trackingEnabled) {
                return;
            }
            
            // Tracking para banners de imagen
            $(document).on('click', '.ali-banner-link', function(e) {
                var $banner = $(this).closest('.ali-banner');
                var bannerId = $banner.data('banner-id');
                
                if (bannerId) {
                    aliAds.trackClick(bannerId);
                    aliAds.log('Click tracked for banner: ' + bannerId);
                }
            });
            
            // Tracking para iframes (limitado por CORS)
            $('.ali-banner iframe').each(function() {
                var $iframe = $(this);
                var $banner = $iframe.closest('.ali-banner');
                var bannerId = $banner.data('banner-id');
                
                if (bannerId) {
                    // Detectar clics en iframe usando overlay invisible
                    aliAds.setupIframeClickTracking($iframe, bannerId);
                }
            });
        },
        
        // Tracking de clics en iframe
        setupIframeClickTracking: function($iframe, bannerId) {
            var $overlay = $('<div>').css({
                position: 'absolute',
                top: 0,
                left: 0,
                width: '100%',
                height: '100%',
                background: 'transparent',
                zIndex: 1,
                cursor: 'pointer'
            });
            
            $iframe.parent().css('position', 'relative').append($overlay);
            
            $overlay.on('click', function() {
                aliAds.trackClick(bannerId);
                
                // Abrir enlace del iframe si es posible
                var iframeSrc = $iframe.attr('src');
                if (iframeSrc) {
                    window.open(iframeSrc, '_blank', 'noopener,noreferrer');
                }
            });
        },
        
        // Enviar tracking de clic
        trackClick: function(bannerId) {
            if (!bannerId || !ali_ajax) {
                return;
            }
            
            $.ajax({
                url: ali_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ali_track_click',
                    banner_id: bannerId,
                    nonce: ali_ajax.nonce
                },
                success: function(response) {
                    aliAds.log('Click tracking successful for banner: ' + bannerId);
                },
                error: function() {
                    aliAds.log('Click tracking failed for banner: ' + bannerId);
                }
            });
        },
        
        // Configurar lazy loading de imágenes
        setupLazyLoading: function() {
            // Solo si el navegador no soporta loading="lazy" nativo
            if ('loading' in HTMLImageElement.prototype) {
                return;
            }
            
            var $lazyImages = $('.ali-banner img[data-src]');
            
            if ($lazyImages.length === 0) {
                return;
            }
            
            // Intersection Observer para lazy loading
            if ('IntersectionObserver' in window) {
                var imageObserver = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            var img = entry.target;
                            var $img = $(img);
                            
                            img.src = img.dataset.src;
                            $img.removeClass('lazy').addClass('loaded');
                            imageObserver.unobserve(img);
                            
                            aliAds.log('Lazy loaded image: ' + img.src);
                        }
                    });
                });
                
                $lazyImages.each(function() {
                    imageObserver.observe(this);
                });
            } else {
                // Fallback para navegadores antiguos
                $(window).on('scroll resize', aliAds.throttle(function() {
                    $lazyImages.each(function() {
                        var $img = $(this);
                        if (aliAds.isInViewport($img)) {
                            this.src = this.dataset.src;
                            $img.removeClass('lazy').addClass('loaded');
                            $lazyImages = $lazyImages.not($img);
                        }
                    });
                }, 100));
            }
        },
        
        // Verificar si elemento está en viewport
        isInViewport: function($element) {
            var elementTop = $element.offset().top;
            var elementBottom = elementTop + $element.outerHeight();
            var viewportTop = $(window).scrollTop();
            var viewportBottom = viewportTop + $(window).height();
            
            return elementBottom > viewportTop && elementTop < viewportBottom;
        },
        
        // Configurar responsividad
        setupResponsive: function() {
            // Ajustar tamaño de banners en móvil
            $(window).on('resize', aliAds.debounce(function() {
                aliAds.adjustBannersForMobile();
            }, 250));
            
            // Ajuste inicial
            aliAds.adjustBannersForMobile();
        },
        
        // Ajustar banners para móvil
        adjustBannersForMobile: function() {
            var isMobile = $(window).width() <= 768;
            
            $('.ali-banner').each(function() {
                var $banner = $(this);
                var $img = $banner.find('img');
                
                if (isMobile) {
                    $banner.addClass('mobile-optimized');
                    
                    // Ajustar imágenes grandes en móvil
                    if ($img.length > 0) {
                        var maxWidth = Math.min($(window).width() - 40, 350);
                        $img.css('max-width', maxWidth + 'px');
                    }
                } else {
                    $banner.removeClass('mobile-optimized');
                    $img.css('max-width', '');
                }
            });
        },
        
        // Gestión de cookies
        setCookie: function(name, value, days) {
            var expires = '';
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = '; expires=' + date.toUTCString();
            }
            document.cookie = name + '=' + value + expires + '; path=/; SameSite=Lax';
        },
        
        getCookie: function(name) {
            var nameEQ = name + '=';
            var ca = document.cookie.split(';');
            
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) === ' ') {
                    c = c.substring(1, c.length);
                }
                if (c.indexOf(nameEQ) === 0) {
                    return c.substring(nameEQ.length, c.length);
                }
            }
            return null;
        },
        
        // Utilidades
        throttle: function(func, limit) {
            var inThrottle;
            return function() {
                var args = arguments;
                var context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(function() {
                        inThrottle = false;
                    }, limit);
                }
            };
        },
        
        debounce: function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this;
                var args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        },
        
        log: function(message) {
            if (aliAds.config.debug && console && console.log) {
                console.log('[AliExpress Smart Ads] ' + message);
            }
        },
        
        // API pública para desarrolladores
        api: {
            // Mostrar banner programáticamente
            showBanner: function(bannerId, placement) {
                // Implementar si es necesario
                aliAds.log('Show banner API called: ' + bannerId);
            },
            
            // Ocultar banner programáticamente
            hideBanner: function(bannerId) {
                var $banner = $('.ali-banner[data-banner-id="' + bannerId + '"]');
                $banner.fadeOut();
                aliAds.log('Hide banner API called: ' + bannerId);
            },
            
            // Configurar opciones
            setConfig: function(options) {
                $.extend(aliAds.config, options);
                aliAds.log('Configuration updated');
            }
        }
    };
    
    // Funciones globales para compatibilidad
    window.aliAdsCloseFloating = function() {
        aliAds.closeFloatingBanner();
    };
    
    // Inicializar cuando el DOM esté listo
    aliAds.init();
    
})(jQuery);

// CSS adicional dinámico si es necesario
(function() {
    'use strict';
    
    // Añadir estilos dinámicos para estados especiales
    function addDynamicStyles() {
        var styles = `
            .ali-banner.loading::before {
                content: "";
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255,255,255,0.6), transparent);
                animation: shimmer 1.5s infinite;
            }
            
            @keyframes shimmer {
                0% { left: -100%; }
                100% { left: 100%; }
            }
            
            .ali-banner.mobile-optimized {
                margin: 10px auto;
            }
            
            .ali-banner.mobile-optimized img {
                border-radius: 5px;
            }
        `;
        
        var styleSheet = document.createElement('style');
        styleSheet.textContent = styles;
        document.head.appendChild(styleSheet);
    }
    
    // Añadir estilos cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', addDynamicStyles);
    } else {
        addDynamicStyles();
    }
})();

// Funciones para Ads Automáticos
window.aliAds = window.aliAds || {};

// Extender el objeto aliAds con funcionalidad automática
jQuery.extend(window.aliAds, {
    
    // Cerrar ads sticky automáticos
    closeAutoSticky: function(element) {
        var $container = jQuery(element).closest('.ali-auto-sticky-container');
        $container.fadeOut(300, function() {
            $container.remove();
        });
        
        // Guardar preferencia para no mostrar por un tiempo
        if (typeof Storage !== 'undefined') {
            localStorage.setItem('ali_auto_sticky_closed', Date.now());
        }
    },

    // Tracking mejorado para ads automáticos
    trackAutoAdEvent: function(bannerId, eventType, element) {
        var $element = jQuery(element);
        var $container = $element.closest('.ali-auto-ad');
        
        // Calcular posición relativa en el contenido
        var positionPercentage = 0;
        if ($container.hasClass('ali-auto-ad-content')) {
            var $content = jQuery('article, .post-content, .entry-content').first();
            if ($content.length > 0) {
                var contentHeight = $content.height();
                var contentTop = $content.offset().top;
                var adTop = $container.offset().top;
                var adPosition = adTop - contentTop;
                positionPercentage = contentHeight > 0 ? Math.max(0, Math.min(1, adPosition / contentHeight)) : 0;
            }
        }

        jQuery.ajax({
            url: aliAdsAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'ali_track_auto_ad_performance',
                banner_id: bannerId,
                event_type: eventType,
                position_percentage: positionPercentage,
                nonce: aliAdsAjax.nonce
            }
        });

        // También llamar al tracking normal
        if (this.trackEvent) {
            this.trackEvent(bannerId, eventType, element);
        }
    },

    // Verificar si ads sticky deben mostrarse
    shouldShowAutoSticky: function() {
        if (typeof Storage === 'undefined') {
            return true;
        }
        
        var lastClosed = localStorage.getItem('ali_auto_sticky_closed');
        if (!lastClosed) {
            return true;
        }
        
        // No mostrar si se cerró hace menos de 1 hora
        var hoursSinceClosed = (Date.now() - parseInt(lastClosed)) / (1000 * 60 * 60);
        return hoursSinceClosed >= 1;
    },

    // Inicializar ads automáticos
    initAutoAds: function() {
        // Verificar si ads sticky deben mostrarse
        if (!this.shouldShowAutoSticky()) {
            jQuery('.ali-auto-sticky-container').remove();
            return;
        }

        // Setup de tracking automático para ads en contenido
        jQuery(document).on('click', '.ali-auto-ad-content a', function(e) {
            var $banner = jQuery(this).closest('.ali-auto-ad');
            var bannerId = $banner.find('img').data('banner-id') || $banner.data('banner-id');
            
            if (bannerId) {
                aliAds.trackAutoAdEvent(bannerId, 'click', this);
            }
        });

        // Setup de tracking de impresiones para ads automáticos
        if ('IntersectionObserver' in window) {
            var autoAdObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting && entry.intersectionRatio > 0.5) {
                        var $banner = jQuery(entry.target);
                        var bannerId = $banner.find('img').data('banner-id') || $banner.data('banner-id');
                        
                        if (bannerId && !$banner.hasClass('impression-tracked')) {
                            $banner.addClass('impression-tracked');
                            aliAds.trackAutoAdEvent(bannerId, 'impression', entry.target);
                        }
                    }
                });
            }, {
                threshold: 0.5,
                rootMargin: '50px'
            });

            jQuery('.ali-auto-ad').each(function() {
                autoAdObserver.observe(this);
            });
        }

        // Añadir estilos adicionales para ads automáticos
        this.addAutoAdStyles();
    },

    // Añadir estilos CSS dinámicos para ads automáticos
    addAutoAdStyles: function() {
        if (document.getElementById('ali-auto-ads-styles')) {
            return; // Ya se añadieron
        }

        var autoStyles = `
            .ali-auto-sticky-container {
                position: fixed;
                z-index: 999999;
                width: 100%;
            }
            
            .ali-auto-sticky-ad {
                position: relative;
                background: white;
                padding: 10px;
                text-align: center;
                border-top: 2px solid #eee;
            }
            
            .ali-auto-sticky-ad.position-bottom {
                bottom: 0;
                left: 0;
                right: 0;
            }
            
            .ali-auto-sticky-ad.position-top {
                top: 0;
                left: 0;
                right: 0;
                border-top: none;
                border-bottom: 2px solid #eee;
            }
            
            .ali-auto-sticky-ad .ali-close {
                position: absolute;
                top: 5px;
                right: 10px;
                background: rgba(0,0,0,0.7);
                color: white;
                border: none;
                border-radius: 50%;
                width: 25px;
                height: 25px;
                cursor: pointer;
                font-size: 14px;
                line-height: 1;
                z-index: 1000;
            }
            
            .ali-auto-sticky-ad .ali-close:hover {
                background: rgba(0,0,0,0.9);
            }
            
            .ali-auto-ad-content {
                animation: fadeInUp 0.6s ease-out;
            }
            
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .ali-auto-ad-loop {
                animation: slideInFromLeft 0.8s ease-out;
            }
            
            @keyframes slideInFromLeft {
                from {
                    opacity: 0;
                    transform: translateX(-30px);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }
            
            @media (max-width: 768px) {
                .ali-auto-sticky-ad {
                    padding: 8px;
                }
                
                .ali-auto-ad-content {
                    margin: 15px auto;
                    padding: 10px;
                }
            }
        `;
        
        var autoStyleSheet = document.createElement('style');
        autoStyleSheet.id = 'ali-auto-ads-styles';
        autoStyleSheet.textContent = autoStyles;
        document.head.appendChild(autoStyleSheet);
    }
});

// Inicializar ads automáticos cuando el DOM esté listo
jQuery(document).ready(function() {
    if (window.aliAds && window.aliAds.initAutoAds) {
        window.aliAds.initAutoAds();
    }
});

// Soporte para AMP (Accelerated Mobile Pages)
(function() {
    'use strict';
    
    // Detectar si estamos en una página AMP
    if (document.querySelector('html[amp]') || document.querySelector('html[⚡]')) {
        // Versión simplificada para AMP
        window.aliAds = window.aliAds || {};
        window.aliAds.isAMP = true;
        
        // Solo funcionalidad básica en AMP
        window.aliAdsCloseFloating = function() {
            var floatingBars = document.querySelectorAll('.ali-placement-floating_bar');
            floatingBars.forEach(function(bar) {
                bar.style.display = 'none';
            });
        };
        
        window.aliAdsCloseAutoSticky = function() {
            var stickyAds = document.querySelectorAll('.ali-auto-sticky-container');
            stickyAds.forEach(function(ad) {
                ad.style.display = 'none';
            });
        };
    }
})();