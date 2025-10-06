# 📐 Guía de Tamaños de Banners de AliExpress

## ¿Qué son los tamaños de banners?

AliExpress utiliza diferentes tamaños de banners para optimizar la visualización en distintos espacios de tu sitio web. Cada tamaño tiene un parámetro específico que debe incluirse en la URL.

## Tamaños Disponibles

### 📱 Banners Horizontales
- **300×250 px** - Rectángulo Medio (`bz=300*250`)
- **500×500 px** - Cuadrado Grande (`bz=500*500`)  
- **728×90 px** - Banner Superior (`bz=728*90`)

### 📏 Banners Verticales
- **190×240 px** - Vertical Pequeño (`bz=190*240`)
- **120×600 px** - Rascacielos (`bz=120*600`)
- **160×600 px** - Rascacielos Ancho (`bz=160*600`)
- **320×480 px** - Banner Móvil (`bz=320*480`)

## Cómo Funciona

### 1. Entrada Original
```html
<a href='https://s.click.aliexpress.com/e/_c3UyHPoF' target='_parent'>
  <img width='300' height='250' src='https://ae-pic-a1.aliexpress-media.com/kf/S603b84d5f09149db87dad570811eaaa98.jpg' />
</a>
```

### 2. Después del Procesamiento (728×90)
```html
<a href='https://s.click.aliexpress.com/e/_c3UyHPoF?bz=728*90' target='_parent'>
  <img width='728' height='90' src='https://ae-pic-a1.aliexpress-media.com/kf/S603b84d5f09149db87dad570811eaaa98.jpg' />
</a>
```

## Ventajas del Sistema

✅ **Automático**: Solo selecciona el tamaño y el sistema hace el resto  
✅ **Consistente**: URLs siempre tienen el parámetro correcto  
✅ **Optimizado**: Dimensiones ajustadas automáticamente  
✅ **Compatible**: Funciona con cualquier código HTML de AliExpress

## Ejemplo Práctico

1. **Copia** el código HTML desde tu panel de AliExpress
2. **Selecciona** el tamaño deseado (ej: 728×90 px)
3. **Pega** el código en el formulario
4. **Guarda** - El sistema automáticamente:
   - Añade `?bz=728*90` a la URL
   - Cambia width='728' height='90' en la imagen
   - Mantiene todos los demás parámetros

## Notas Técnicas

- El parámetro `bz=` le dice a AliExpress qué tamaño de imagen servir
- Las dimensiones de la imagen se actualizan para coincidir
- Si la URL ya tiene `bz=`, se reemplaza con el nuevo valor
- Compatible con URLs que ya tienen otros parámetros