# API de Autenticación - Documentación

## Configuración Inicial

### 1. Crear las tablas en la base de datos
Ejecuta el archivo `auth_tables.sql`:
```bash
mysql -u tu_usuario -p tu_base_de_datos < auth_tables.sql
```

### 2. Usuario de prueba
Username: `admin`
Password: `admin123`

---

## Endpoints de Autenticación

### 1. Registrar un nuevo usuario
**POST** `/api/v1/register`

**Body:**
```json
{
  "username": "johndoe",
  "email": "john@example.com",
  "password": "securepassword123"
}
```

**Respuesta exitosa (201):**
```json
{
  "message": "Usuario registrado exitosamente",
  "user_id": "1"
}
```

---

### 2. Iniciar sesión
**POST** `/api/v1/login`

**Body:**
```json
{
  "username": "admin",
  "password": "admin123"
}
```

**Respuesta exitosa (200):**
```json
{
  "access_token": "abc123xyz456...",
  "expires_at": "2026-02-19 14:30:00"
}
```

**Errores posibles:**
- `401`: Credenciales inválidas
- `403`: Usuario inactivo
- `400`: Datos incompletos

---

### 3. Cerrar sesión
**POST** `/api/v1/logout`

**Headers:**
```
Authorization: Bearer {tu_token_aqui}
```

**Respuesta exitosa (200):**
```json
{
  "message": "Sesión cerrada exitosamente"
}
```

---

## Endpoints Protegidos

Todos los endpoints de usuarios y productos ahora requieren autenticación.

### Headers requeridos:
```
Authorization: Bearer {tu_token_aqui}
Content-Type: application/json
```

### Ejemplo con cURL:

#### Login:
```bash
curl -X POST http://localhost/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

#### Obtener productos (requiere token):
```bash
curl -X GET http://localhost/api/v1/productos \
  -H "Authorization: Bearer tu_token_aqui"
```

#### Crear producto:
```bash
curl -X POST http://localhost/api/v1/productos \
  -H "Authorization: Bearer tu_token_aqui" \
  -H "Content-Type: application/json" \
  -d '{
    "sku": "PROD001",
    "name": "Producto de prueba",
    "description": "Descripción del producto",
    "price": 99.99,
    "stock": 50
  }'
```

---

## Códigos de Estado HTTP

| Código | Descripción |
|--------|-------------|
| 200 | OK - Solicitud exitosa |
| 201 | Created - Recurso creado exitosamente |
| 400 | Bad Request - Datos incompletos o inválidos |
| 401 | Unauthorized - Token no proporcionado, inválido o expirado |
| 403 | Forbidden - Usuario inactivo |
| 404 | Not Found - Recurso no encontrado |
| 409 | Conflict - Recurso duplicado (username o email ya existe) |
| 500 | Internal Server Error - Error del servidor |
| 503 | Service Unavailable - No se pudo completar la operación |

---

## Características del Sistema

### Tokens
- **Duración**: 24 horas por defecto
- **Formato**: 64 caracteres hexadecimales
- **Revocación**: Los tokens pueden ser revocados al cerrar sesión
- **Validación**: Se verifica que el token no esté expirado ni revocado

### Seguridad
- Contraseñas hasheadas con bcrypt
- Validación de tokens en cada petición
- Usuarios pueden estar activos o inactivos
- Foreign keys con eliminación en cascada

### Endpoints protegidos:
**Usuarios:**
- GET `/api/v1/users`
- GET `/api/v1/users/{id}`
- POST `/api/v1/users`
- PUT `/api/v1/users/{id}`
- DELETE `/api/v1/users/{id}`

**Productos:**
- GET `/api/v1/productos`
- GET `/api/v1/productos/{id}`
- POST `/api/v1/productos`
- PUT `/api/v1/productos/{id}`
- DELETE `/api/v1/productos/{id}`

---

## Estructura de Archivos Creados

```
api/
├── models/
│   ├── ApiUser.php          # Modelo de usuarios de autenticación
│   ├── ApiToken.php         # Modelo de tokens
│   └── Producto.php         # Modelo de productos
├── resources/v1/
│   ├── LoginResource.php    # Endpoints de autenticación
│   ├── UserResource.php     # Endpoints de usuarios (protegidos)
│   └── ProductoResource.php # Endpoints de productos (protegidos)
├── middleware/
│   └── AuthMiddleware.php   # Middleware de autenticación
└── auth_tables.sql          # Script SQL para crear tablas
```

---

## Notas Importantes

1. **Tokens expirados**: Los tokens expiran después de 24 horas. El cliente debe manejar el error 401 y solicitar un nuevo login.

2. **CORS**: Los headers CORS ya están configurados para permitir requests desde cualquier origen.

3. **Producción**: En producción, asegúrate de:
   - Cambiar las credenciales de base de datos
   - Usar HTTPS
   - Configurar CORS para dominios específicos
   - Implementar rate limiting
   - Agregar logs de seguridad
