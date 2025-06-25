![image](https://solinte.net/si_home/assets/solinte-hero-1.webp)

# SDK de Solinte para PHP

¡Bienvenido desarrolladores!

Esta es una pequeña librería que te permite integrar fácilmente la plataforma Solinte en tus desarrollos.

## 💡 Requisitos

- PHP 8.2 o superior
- Composer

## 💻 Instalación

```bash
composer require solinte-net/sdk-php
```

¡Así de simple!

## 🛠️ Configuración

Para comenzar a usar el SDK, necesitás obtener las credenciales de aplicación desde el soporte de Solinte. Para más información podés consultar [documentación de la API](https://solinte.net/api.v1/).

```php
use SolinteNet\SdkPhp\Client;

$client = new Client([
    'client_id' => 'tu_client_id',
    'client_secret' => 'tu_client_secret',
    'redirect_uri' => 'tu_redirect_uri'
]);
```

## 🔑 Autenticación OAuth 2.0

El SDK utiliza OAuth 2.0 para la autenticación. Necesitarás implementar el flujo de autorización:

1. **Redirigir al usuario a la página de autorización**
2. **Obtener el código de autorización**
3. **Intercambiar el código por un access_token**

```php
// URL de autorización
$authUrl = $client->getAuthorizationUrl([
    'scope' => 'basic perfil roles'
]);

// Después de la autorización, intercambiar el código por token
$token = $client->exchangeCodeForToken($code);
```

## 🤓 Uso Básico

### Obtener información del usuario

```php
// Obtener email del usuario
$email = $client->usuario()->email()->get();

// Obtener perfil completo del usuario
$perfil = $client->usuario()->perfil()->get();
```

### Obtener roles del usuario

```php
// Listar todos los roles del usuario
$roles = $client->usuario()->roles()->get();

// Obtener saldo de un rol específico
$saldo = $client->usuario()->roles()->saldo($rid, 'hoy');
// o con fecha específica
$saldo = $client->usuario()->roles()->saldo($rid, '2024-01-15');
```

## 📍 Scopes Disponibles

- `basic` - Solo lectura, acceso básico a /usuario
- `perfil` - Solo lectura, acceso al perfil completo del usuario
- `roles` - Solo lectura, acceso al listado de roles del usuario
- `admin_rol` - Lectura, creación y modificación de roles
- `contactos` - Solo lectura, acceso a contactos del usuario
- `admin_contacto` - Lectura, creación y modificación de contactos
- `mensajes` - Solo lectura, acceso a mensajes y comunicaciones
- `admin_mensaje` - Lectura, creación y modificación de mensajes

## ❌ Manejo de Errores

```php
try {
    $perfil = $client->usuario()->perfil()->get();
} catch (SolinteNet\SdkPhp\Exceptions\ApiException $e) {
    echo "Error de API: " . $e->getMessage();
} catch (SolinteNet\SdkPhp\Exceptions\OAuthException $e) {
    echo "Error de autenticación: " . $e->getMessage();
}
```

## 📚 Documentación de la API

Para más información sobre los endpoints disponibles, podés consultar la [documentación oficial de Solinte](https://solinte.net/api.v1/).

## 🤗 Colección de Postman

Podés probar la API directamente visitando nuestra [colección de Postman](https://www.postman.com/asadfa-6721/solinte/overview).

## ❤️ Soporte

Para soporte técnico, podés contactarnos a través de la [página de soporte](https://solinte.net/).

## ⚖️ Licencia

```
MIT license. Copyright (c) 2025 - Solinte (SOLSOFT SOLUCIONES INTEGRALES S.A.)
Para más información, verifique el archivo LICENSE.
```