# 📘 MegaSantiago — Actualización de Interfaz Web (UI/UX)
### *Librería & Papelería — Proyecto de Modernización Frontend*

## 📌 Descripción del Proyecto
Este repositorio contiene la actualización completa de la interfaz gráfica (frontend) del sitio web de la librería y papelería MegaSantiago, cuyo objetivo es modernizar su apariencia, mejorar la experiencia de usuario y optimizar la visualización de productos utilizando WordPress + WooCommerce.

El proyecto NO crea la tienda desde cero.
👉 Se centra en mejorar el diseño, navegación y funcionalidad visual del sitio.

## 📌 Objetivo General
Actualizar la interfaz de MegaSantiago para ofrecer una experiencia de compra moderna, consistente, clara y visualmente atractiva, manteniendo compatibilidad con WooCommerce.

## 📌 Objetivos Específicos
- Rediseñar la página principal (Home) con un aspecto moderno.
- Mejorar navegación por categorías.
- Optimizar resultados de búsqueda.
- Actualizar login y registro.
- Corregir rutas de imágenes.
- Modernizar el diseño del carrito.
- Unificar estilos, colores y tarjetas de productos.

## 📌 Tecnologías Utilizadas
- WordPress
- WooCommerce
- PHP
- JavaScript / jQuery
- HTML5
- CSS3
- GitHub

## 📌 Cambios Realizados
### 🔹 Home modernizado
- Banner promocional
- Sección de productos destacados

### 🔹 Categorías actualizadas
- Productos con tarjetas uniformes
- Mejor organización visual

### 🔹 Búsqueda mejorada
- Resultados más limpios
- Imágenes corregidas

### 🔹 Login y registro rediseñados
- Formularios minimalistas y responsivos

### 🔹 Carrito modernizado
- Interfaz limpia y organizada

### 🔹 Corrección de imágenes
Productos corregidos:
- Mochila juvenil
- Cartulina IRIS
- Cuaderno universitario 100 hojas
- Otros artículos escolares

## 📌 Conclusiones
- Interfaz moderna y clara
- Mejor experiencia de usuario
- Base visual lista para ventas reales
- Preparado para integración de métodos de pago

## 📌 Repositorio Oficial
🔗 https://github.com/AnthonyJoelRY/ProyectoMegaSantiagoFront
## link de wiframe: 
https://excalidraw.com/#json=_fcWCgIk4n3clVOwuzyCa,tix3LXE9W7AnRhXhoEdX8Q

## 📩 Autor
Anthony Joel Romero Yaguana
Proyecto de actualización frontend — UTPL


## PayPal Sandbox (carrito)

- Configura tus credenciales en `Model/paypal_credentials.php`.
- El carrito (`View/pages/carrito.html`) carga el SDK de PayPal dinámicamente y usa estos endpoints:
  - `Controller/paypalController.php?accion=config`
  - `Controller/paypalController.php?accion=create-order`
  - `Controller/paypalController.php?accion=capture-order`

