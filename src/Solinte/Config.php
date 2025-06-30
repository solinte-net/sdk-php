<?php

namespace Solinte\SdkPhp;

use Solinte\SdkPhp\Exceptions\ValidationException;

class Config {
    private array $config;

    // Configuración por defecto
    private const DEFAULT_CONFIG = [
        'client_id' => null,
        'client_secret' => null,
        'redirect_uri' => null,
        'timeout' => 30,
        'verify' => true,
        'user_agent' => 'Solinte-PHP-SDK/1.0',
        'oauth_base_url' => 'https://solinte.net/OAuth2',
        'api_base_url' => 'https://solinte.net/api.v1',
    ];

    // Scopes disponibles
    public const SCOPES = [
        'basic' => 'Solo lectura, acceso básico a /usuario',
        'perfil' => 'Solo lectura, acceso al perfil completo del usuario',
        'roles' => 'Solo lectura, acceso al listado de roles del usuario',
        'admin_rol' => 'Lectura, creación y modificación de roles',
        'contactos' => 'Solo lectura, acceso a contactos del usuario',
        'admin_contacto' => 'Lectura, creación y modificación de contactos',
        'mensajes' => 'Solo lectura, acceso a mensajes y comunicaciones',
        'admin_mensaje' => 'Lectura, creación y modificación de mensajes',
    ];

    public function __construct(array $config = []) {
        $this->config = array_merge(self::DEFAULT_CONFIG, $config);
        $this->validate();
    }

    /**
     * Obtiene un valor de configuración
     */
    public function get(string $key, $default = null) {
        return $this->config[$key] ?? $default;
    }

    /**
     * Establece un valor de configuración
     */
    public function set(string $key, $value): void {
        $this->config[$key] = $value;
    }

    /**
     * Obtiene toda la configuración
     */
    public function all(): array {
        return $this->config;
    }

    /**
     * Verifica si existe una clave de configuración
     */
    public function has(string $key): bool {
        return isset($this->config[$key]);
    }

    /**
     * Obtiene el client_id
     */
    public function getClientId(): ?string {
        return $this->config['client_id'];
    }

    /**
     * Obtiene el client_secret
     */
    public function getClientSecret(): ?string {
        return $this->config['client_secret'];
    }

    /**
     * Obtiene el redirect_uri
     */
    public function getRedirectUri(): ?string {
        return $this->config['redirect_uri'];
    }

    /**
     * Obtiene el timeout
     */
    public function getTimeout(): int {
        return $this->config['timeout'];
    }

    /**
     * Obtiene si debe verificar SSL
     */
    public function getVerify(): bool {
        return $this->config['verify'];
    }

    /**
     * Obtiene el user agent
     */
    public function getUserAgent(): string {
        return $this->config['user_agent'];
    }

    /**
     * Obtiene la URL base de OAuth
     */
    public function getOAuthBaseUrl(): string {
        return $this->config['oauth_base_url'];
    }

    /**
     * Obtiene la URL base de la API
     */
    public function getApiBaseUrl(): string {
        return $this->config['api_base_url'];
    }

    /**
     * Obtiene los scopes disponibles
     */
    public static function getAvailableScopes(): array {
        return self::SCOPES;
    }

    /**
     * Valida si un scope es válido
     */
    public static function isValidScope(string $scope): bool {
        return array_key_exists($scope, self::SCOPES);
    }

    /**
     * Valida múltiples scopes
     */
    public static function validateScopes(array $scopes): array {
        $invalidScopes = [];
        
        foreach ($scopes as $scope) {
            if (!self::isValidScope($scope))
                $invalidScopes[] = $scope;
        }

        if (!empty($invalidScopes))
            throw new ValidationException(
                'Scopes inválidos: ' . implode(', ', $invalidScopes),
                ['invalid_scopes' => $invalidScopes]
            );

        return $scopes;
    }

    /**
     * Valida la configuración
     */
    private function validate(): void {
        $errors = [];

        // Validar client_id
        if (empty($this->config['client_id']))
            $errors['client_id'] = 'El client_id es requerido';

        // Validar client_secret
        if (empty($this->config['client_secret']))
            $errors['client_secret'] = 'El client_secret es requerido';

        // Validar redirect_uri
        if (empty($this->config['redirect_uri']))
            $errors['redirect_uri'] = 'El redirect_uri es requerido';
        elseif (!filter_var($this->config['redirect_uri'], FILTER_VALIDATE_URL))
            $errors['redirect_uri'] = 'El redirect_uri debe ser una URL válida';

        // Validar timeout
        if (!is_numeric($this->config['timeout']) || $this->config['timeout'] <= 0)
            $errors['timeout'] = 'El timeout debe ser un número positivo';

        // Validar URLs base
        if (!filter_var($this->config['oauth_base_url'], FILTER_VALIDATE_URL))
            $errors['oauth_base_url'] = 'La URL base de OAuth debe ser válida';

        if (!filter_var($this->config['api_base_url'], FILTER_VALIDATE_URL))
            $errors['api_base_url'] = 'La URL base de la API debe ser válida';

        if (!empty($errors))
            throw new ValidationException('Configuración inválida', $errors);
    }
}
