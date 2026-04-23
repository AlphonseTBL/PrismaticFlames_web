# PrismaticFlames Web

Aplicación web de e-commerce para venta de libros, con catálogo, carrito, checkout, cuenta de usuario, wishlist y panel CRUD para administración.

## Tecnologías

- Frontend: HTML5, CSS3, JavaScript, Bootstrap, jQuery.
- Backend: PHP 8+ (estilo procedural con `mysqli`).
- Base de datos: MySQL/MariaDB.

## Estructura del proyecto

```text
PrismaticFlames_web/
├── *.html                 # Vistas principales (inicio, tienda, cuenta, checkout, admin)
├── css/                   # Estilos
├── js/                    # Lógica de UI y consumo de endpoints
├── images/                # Recursos estáticos
├── php/                   # Endpoints consumidos por el frontend (sesión + datos)
├── api/                   # Endpoints API alternos con respuestas JSON
└── lgunprmiuy_PrismaticFlames.sql  # Esquema y datos base
```

## Funcionalidades principales

- Exploración del catálogo de libros y búsqueda.
- Registro, inicio/cierre de sesión y gestión de perfil.
- Carrito de compras (agregar, actualizar, eliminar y vaciar).
- Checkout con creación de pedidos y acumulación de puntos.
- Historial de pedidos del usuario.
- Lista de deseos (wishlist).
- Panel administrativo con CRUD dinámico sobre tablas.

## Requisitos

- PHP 8.0 o superior.
- MySQL/MariaDB.
- Servidor web (Apache/Nginx) o servidor embebido de PHP.

## Configuración rápida (local)

1. Clona el repositorio y entra a la carpeta del proyecto.
2. Crea una base de datos MySQL/MariaDB.
3. Importa el dump:
   ```bash
   mysql -u TU_USUARIO -p TU_BASE < lgunprmiuy_PrismaticFlames.sql
   ```
4. Revisa y ajusta credenciales de conexión en archivos PHP (por ejemplo en `php/*.php` y `api/common.php`).
5. Levanta el servidor local:
   ```bash
   php -S 127.0.0.1:8000
   ```
6. Abre en navegador:
   - `http://127.0.0.1:8000/index.html`

## Endpoints principales (`php/`)

- `php/books.php`
  - `GET ?id={id}`: detalle de libro.
  - `GET ?q={texto}`: búsqueda.
  - `GET` sin parámetros: listado.
- `php/register.php` (`POST`): registro de usuario.
- `php/login.php` (`POST`): autenticación.
- `php/logout.php`: cierre de sesión.
- `php/session-info.php` (`GET`): estado de sesión.
- `php/profile.php`
  - `GET ?action=get`: obtener perfil.
  - `POST action=update|delete`: actualizar/eliminar cuenta.
- `php/cart.php`
  - `GET ?action=list`
  - `POST action=add|update|remove|clear`
- `php/checkout.php` (`POST`): generar pedido desde el carrito.
- `php/orders.php` (`GET`): listar pedidos del usuario autenticado.
- `php/wishlist.php`: operaciones de lista de deseos.
- `php/admin-crud.php`: operaciones administrativas de tablas.

## Base de datos (tablas clave)

- `usuarios`, `roles`, `usuarios_roles`
- `libros`, `autores`, `categorias`
- `libros_autores`, `libros_categorias`
- `carritos`, `carrito_items`
- `pedidos`, `pedido_items`
- `wishlist`

## Validación rápida

Para validar sintaxis PHP:

```bash
find api php -name '*.php' -print0 | xargs -0 -n1 php -l
```

## Notas

- El proyecto incluye endpoints en `php/` y `api/`; actualmente el frontend usa principalmente `php/`.
- Si vas a desplegar en producción, mueve credenciales de base de datos a configuración segura (variables de entorno o archivo fuera de versión).
