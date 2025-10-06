# AliExpress Smart Ads - Auto Banners for WordPress

Un plugin profesional de WordPress para insertar banners inteligentes de AliExpress de forma automática en zonas estratégicas de tu sitio web.

## 🚀 Características Principales

### 🤖 **NUEVA FUNCIONALIDAD: Ads Automáticos Estilo AdSense**
- 🧠 **Colocación automática inteligente** como Google AdSense
- 📊 **Algoritmos de optimización** que aprenden las mejores posiciones
- ⚙️ **Configuración de densidad** (baja, media, alta)
- 🎯 **Sistema de aprendizaje automático** para maximizar CTR
- 📱 **Ads sticky/flotantes** con posicionamiento responsive
- 📈 **Analytics de rendimiento** por posición automática

### ✨ **Funcionalidades Principales**
- ✅ **Inserción automática** en header, footer, contenido, sidebar y barra flotante
- 📊 **Estadísticas detalladas** de impresiones y clics 
- 🎯 **Algoritmo inteligente** de selección por categorías
- 📱 **Totalmente responsive** y compatible con móviles
- ⚙️ **Panel de administración completo** con dashboard visual
- 🔧 **Múltiples tipos**: imágenes con enlace o códigos iframe
- 🎨 **Widget personalizable** para sidebars
- 📋 **Shortcode [ali_banner]** para inserción manual
- 🔄 **Rotación automática** de banners
- 📈 **Tracking de rendimiento** con CTR
- 🌙 **Modo oscuro** y soporte de accesibilidad

## 📦 Estructura del Plugin

```
aliexpress-smart-ads/
├── aliexpress-smart-ads.php      # Archivo principal
├── includes/                     # Clases PHP
│   ├── helpers.php              # Funciones auxiliares
│   ├── class-ali-admin.php      # Panel de administración
│   ├── class-ali-banner.php     # Modelo de datos CRUD
│   ├── class-ali-display.php    # Lógica de inserción
│   └── class-ali-stats.php      # Sistema de estadísticas
├── assets/                      # Recursos frontend
│   ├── css/
│   │   ├── ali-banners.css      # Estilos frontend
│   │   └── ali-admin.css        # Estilos admin
│   ├── js/
│   │   ├── ali-banners.js       # JavaScript frontend
│   │   └── ali-admin.js         # JavaScript admin
│   └── images/                  # Imágenes del plugin
└── uninstall.php               # Limpieza en desinstalación
```

## 🛠️ Instalación

1. **Descarga** todos los archivos del plugin
2. **Sube la carpeta** `plugin-adAli` a `/wp-content/plugins/`
3. **Activa el plugin** desde el panel de WordPress
4. **Configura** desde `AliExpress Ads` en el menú del admin

## 📖 Guía de Uso Rápido

### 1. Crear tu primer banner

1. Ve a **AliExpress Ads > Añadir Banner**
2. Completa los campos:
   - **Título**: Nombre interno del banner
   - **Tipo**: Imagen con enlace o código iframe
   - **URL Imagen**: Enlace directo a la imagen del banner
   - **URL Destino**: Link de AliExpress (con tu código de afiliado)
   - **Categoría**: Para qué posts mostrar el banner
   - **Ubicación**: Dónde insertarlo automáticamente

### 2. Configurar inserción automática

1. Ve a **AliExpress Ads > Configuración**
2. Activa las ubicaciones donde quieres mostrar banners:
   - ✅ Header (cabecera)
   - ✅ Contenido de posts
   - ✅ Footer (pie de página)
   - ✅ Barra flotante
   - ✅ Entre artículos

### 3. Inserción manual

**Shortcode:**
```php
[ali_banner category="smartphones"]
[ali_banner id="123"]
```

**Widget:**
- Ve a **Apariencia > Widgets**
- Añade el widget "AliExpress Smart Banner"

**Función PHP:**
```php
echo Ali_Display::render_banner($banner, 'sidebar');
```

## 🤖 Ads Automáticos Estilo AdSense (NUEVO)

### ¿Qué son los Ads Automáticos?

Los ads automáticos funcionan exactamente como **Google AdSense**, colocando banners de AliExpress de forma inteligente y automática en las mejores posiciones de tu sitio web, sin necesidad de configuración manual.

### Configuración de Ads Automáticos

1. **Ve a AliExpress Ads > Auto Ads**
2. **Activa los Ads Automáticos** con el interruptor principal
3. **Configura la densidad**:
   - 🟢 **Baja**: Pocos ads, experiencia limpia
   - 🟡 **Media**: Balance óptimo (recomendado)
   - 🔴 **Alta**: Máximos ingresos

### Tipos de Ads Automáticos

#### 📝 **Ads en Contenido de Posts**
- Se insertan automáticamente entre párrafos
- Algoritmo inteligente elige las mejores posiciones
- Configurable: frecuencia y número máximo por post

#### 📌 **Ads Sticky (Flotantes)**
- Aparecen después de X segundos
- Posiciones: arriba, abajo o lateral
- Botón de cerrar opcional

#### 📋 **Ads en Listados**
- Entre posts en página de inicio, categorías, etc.
- Frecuencia configurable (cada X posts)

#### ➡️ **Ads en Sidebar**
- Inserción automática en barras laterales
- Posición configurable (arriba, medio, abajo)

### 🧠 Sistema de Optimización Inteligente

El plugin **aprende automáticamente** cuáles son las mejores posiciones:

1. **Recopila datos** de impresiones y clics por posición
2. **Calcula CTR** para cada ubicación
3. **Optimiza automáticamente** futuras inserciones
4. **Mejora el rendimiento** con el tiempo

### Estadísticas de Ads Automáticos

En la página **Auto Ads** puedes ver:
- 📊 **Mejores posiciones por CTR**
- 📈 **Rendimiento histórico**
- 🎯 **Datos de optimización**
- 📱 **Análisis por dispositivo**

## 🎯 Tipos de Ubicación

| Ubicación | Descripción | Hook usado |
|-----------|-------------|------------|
| `header` | En la cabecera | `wp_head` |
| `footer` | En el pie | `wp_footer` |
| `in_content` | Dentro del post | `the_content` |
| `sidebar` | Barra lateral | Widget |
| `floating_bar` | Barra flotante | `wp_footer` |
| `between_articles` | Entre posts | `loop_end` |

## 📊 Sistema de Estadísticas

El plugin incluye un completo sistema de tracking:

- **Impresiones**: Cuántas veces se ha visto el banner
- **Clics**: Cuántas veces se ha clickeado
- **CTR**: Click Through Rate (porcentaje de efectividad)
- **Eventos detallados**: IP, user agent, referrer, etc.

### URLs de tracking
El plugin intercepta clics con URLs como:
```
https://tuweb.com/?ali_click=123
```

## ⚙️ Configuración Avanzada

### Opciones principales

```php
$options = array(
    'affiliate_id' => 'tu_id_afiliado',
    'auto_insert_content' => true,
    'content_position' => 'after_first_paragraph',
    'max_banners_per_page' => 3,
    'floating_bar_position' => 'bottom',
    'between_posts_interval' => 3
);
```

### Hooks para desarrolladores

```php
// Modificar HTML del banner
add_filter('ali_ads_banner_html', 'custom_banner_html', 10, 3);

// Controlar dónde mostrar banners
add_filter('ali_ads_should_show_banners', 'custom_banner_conditions');

// Personalizar algoritmo de selección
add_filter('ali_ads_banner_selection', 'custom_selection_algorithm');
```

## 🎨 Personalización CSS

### Clases CSS disponibles

```css
.ali-banner                    /* Banner general */
.ali-placement-header          /* Banner en header */
.ali-placement-footer          /* Banner en footer */
.ali-placement-in_content      /* Banner en contenido */
.ali-placement-sidebar         /* Banner en sidebar */
.ali-placement-floating_bar    /* Barra flotante */
.ali-placement-between_articles /* Entre artículos */
```

### Ejemplo de personalización

```css
/* Personalizar barra flotante */
.ali-placement-floating_bar {
    background: linear-gradient(45deg, #ff6b6b, #feca57);
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
}

/* Banners en contenido con sombra */
.ali-placement-in_content img {
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.ali-placement-in_content:hover img {
    transform: scale(1.05);
}
```

## 📱 Responsive y Móvil

El plugin es completamente responsive:

- Las imágenes se adaptan automáticamente
- La barra flotante se reposiciona en móvil  
- Los banners se optimizan para pantallas pequeñas
- Soporte para gestos táctiles

## 🔧 Base de Datos

### Tabla principal: `wp_aliexpress_ads`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT | ID único del banner |
| `title` | VARCHAR(255) | Título del banner |
| `image_url` | TEXT | URL de la imagen |
| `target_url` | TEXT | URL de destino |
| `iframe_code` | TEXT | Código iframe alternativo |
| `category` | VARCHAR(100) | Categoría asociada |
| `placement` | VARCHAR(50) | Ubicación del banner |
| `active` | BOOLEAN | Activo o inactivo |
| `impressions` | INT | Contador de impresiones |
| `clicks` | INT | Contador de clics |
| `created_at` | DATETIME | Fecha de creación |
| `updated_at` | DATETIME | Fecha de actualización |

### Tabla de eventos: `wp_aliexpress_ads_events`

Registra clics detallados para análisis avanzado.

## 🚨 Troubleshooting

### Problema: Los banners no se muestran

1. Verifica que el banner esté **activo**
2. Comprueba la **configuración de inserción automática**
3. Revisa que la **categoría del post** coincida
4. Verifica que no hayas alcanzado el **máximo de banners por página**

### Problema: Las estadísticas no funcionan

1. Verifica que **JavaScript esté habilitado**
2. Comprueba la **configuración de AJAX**
3. Revisa los **permisos de base de datos**

### Problema: La barra flotante no se cierra

1. Verifica que **jQuery esté cargado**
2. Comprueba **conflictos con otros plugins**
3. Revisa la **consola del navegador** por errores

## 🔐 Seguridad

El plugin incluye múltiples capas de seguridad:

- ✅ **Validación de nonces** en todas las acciones AJAX
- ✅ **Sanitización** de todos los datos de entrada
- ✅ **Verificación de permisos** de usuario
- ✅ **Validación de URLs** de AliExpress
- ✅ **Sanitización de códigos iframe**
- ✅ **Escape de salida HTML**

## 📈 Optimización de Rendimiento

- **Lazy loading** de imágenes
- **Caché** de consultas de banners
- **Minificación** de CSS/JS
- **Carga condicional** de assets
- **Cleanup automático** de datos antiguos

## 🌍 Compatibilidad

- **WordPress**: 6.0+
- **PHP**: 7.4+
- **Navegadores**: Chrome, Firefox, Safari, Edge
- **Temas**: Compatible con todos los temas estándar
- **Plugins**: Gutenberg, WooCommerce, SEO plugins
- **Multisite**: Totalmente compatible

## 📞 Soporte y Desarrollo

### Logs de debug
Para habilitar logs de debug, añade en `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Los logs se guardarán en `/wp-content/debug.log`

### Estructura de desarrollo

```bash
# Instalar dependencias de desarrollo
npm install

# Compilar assets
npm run build

# Modo desarrollo con watch
npm run dev

# Linter de código
npm run lint
```

## 📝 Changelog

### Version 1.0.0
- ✅ Lanzamiento inicial
- ✅ Sistema completo de banners
- ✅ Panel de administración
- ✅ Inserción automática
- ✅ Estadísticas detalladas
- ✅ Soporte responsive
- ✅ Shortcodes y widgets

## 📄 Licencia

Este plugin está licenciado bajo GPL v2 o posterior.

## 🤝 Contribuir

¡Las contribuciones son bienvenidas!

1. Fork del repositorio
2. Crea una rama para tu feature
3. Realiza tus cambios
4. Envía un pull request

---

**¡Monetiza tu sitio WordPress con banners inteligentes de AliExpress! 🚀**#   p l u g i n - a d - a l i - w p  
 