# 🚀 PrestaShop REST API Module

API REST completa para PrestaShop con autenticación, documentación automática y endpoints para productos, categorías e imágenes.

## ✨ Características

- ✅ **Autenticación por API Key**
- ✅ **Documentación Swagger UI automática**
- ✅ **Endpoints CRUD completos** para productos
- ✅ **Gestión de imágenes** en todos los tamaños
- ✅ **Sistema de categorías**
- ✅ **CORS habilitado**
- ✅ **Logging extensivo**

## 📚 Endpoints Disponibles

### 📦 Productos
- `GET /api/v1/products` - Listar productos (con paginación)
- `GET /api/v1/products/{id}` - Obtener producto específico
- `GET /api/v1/products/{id}/images` - Imágenes del producto
- `GET /api/v1/products/featured` - Productos destacados
- `POST /api/v1/products` - Crear producto
- `PUT /api/v1/products/{id}` - Actualizar producto
- `DELETE /api/v1/products/{id}` - Eliminar producto

### 📁 Categorías
- `GET /api/v1/categories` - Listar categorías
- `GET /api/v1/categories/{id}/products` - Productos por categoría

### 📖 Documentación
- `GET /api/v1/docs` - Documentación interactiva
- `GET /api/v1/docs?json=1` - Especificación OpenAPI

## 🛠️ Instalación

1. Copiar módulo a `/modules/myapi/`
2. Configurar rutas en `.htaccess` principal
3. Generar API Key desde el panel de administración
4. ¡Listo!

## 🔐 Autenticación

```http
GET /api/v1/products
X-API-Key: tu_api_key
