<?php

namespace Solinte\SdkPhp;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use Solinte\SdkPhp\Exceptions\ApiException;
use Solinte\SdkPhp\Exceptions\OAuthException;
use Solinte\SdkPhp\Resources\Usuario\UsuarioResource;

class Client {
    private HttpClient $httpClient;
    private Config $config;
    private OAuth2Client $oauthClient;

    public function __construct(array $config = []) {
        $this->config = new Config($config);
        $this->oauthClient = new OAuth2Client($this->config);
        
        $this->httpClient = new HttpClient([
            'timeout' => $this->config->getTimeout(),
            'verify' => $this->config->getVerify(),
            'headers' => [
                'User-Agent' => $this->config->getUserAgent(),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Obtiene la URL de autorización OAuth 2.0
     */
    public function getAuthorizationUrl(array $params = []): string {
        return $this->oauthClient->getAuthorizationUrl($params);
    }

    /**
     * Intercambia el código de autorización por un access token
     */
    public function exchangeCodeForToken(string $code, ?string $state = null): array {
        return $this->oauthClient->exchangeCodeForToken($code, $state);
    }

    /**
     * Refresca el access token usando el refresh token
     */
    public function refreshAccessToken(string $refreshToken, array $scopes = []): array {
        return $this->oauthClient->refreshAccessToken($refreshToken, $scopes);
    }

    /**
     * Obtiene un token usando el flujo de password
     */
    public function getTokenWithPassword(string $username, string $password, array $scopes = []): array {
        return $this->oauthClient->getTokenWithPassword($username, $password, $scopes);
    }

    /**
     * Establece el access token para las siguientes peticiones
     */
    public function setAccessToken(string $accessToken, ?int $expiresIn = null): void {
        $this->oauthClient->setAccessToken($accessToken, $expiresIn);
    }

    /**
     * Establece el refresh token
     */
    public function setRefreshToken(string $refreshToken): void {
        $this->oauthClient->setRefreshToken($refreshToken);
    }

    /**
     * Obtiene el access token actual
     */
    public function getAccessToken(): ?string {
        return $this->oauthClient->getAccessToken();
    }

    /**
     * Obtiene el refresh token actual
     */
    public function getRefreshToken(): ?string {
        return $this->oauthClient->getRefreshToken();
    }

    /**
     * Verifica si el token ha expirado
     */
    public function isTokenExpired(): bool {
        return $this->oauthClient->isTokenExpired();
    }

    /**
     * Refresca el token si ha expirado
     */
    public function refreshTokenIfExpired(): bool {
        return $this->oauthClient->refreshTokenIfExpired();
    }

    /**
     * Limpia todos los tokens
     */
    public function clearTokens(): void {
        $this->oauthClient->clearTokens();
    }

    /**
     * Obtiene información del token actual
     */
    public function getTokenInfo(): array {
        return $this->oauthClient->getTokenInfo();
    }

    /**
     * Obtiene la configuración
     */
    public function getConfig(): Config {
        return $this->config;
    }

    /**
     * Obtiene el cliente OAuth2
     */
    public function getOAuthClient(): OAuth2Client {
        return $this->oauthClient;
    }

    /**
     * Realiza una petición a la API de Solinte
     */
    public function request(string $method, string $endpoint, array $params = []): array {
        $accessToken = $this->oauthClient->getAccessToken();
        
        if (!$accessToken)
            throw new OAuthException('Access token no configurado. Usa setAccessToken() o exchangeCodeForToken()');

        // Verificar si el token ha expirado y refrescarlo si es necesario
        $this->oauthClient->refreshTokenIfExpired();

        $url = $this->config->getApiBaseUrl() . $endpoint;
        $options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->oauthClient->getAccessToken(),
            ],
        ];

        if ($method === 'GET' && !empty($params))
            $url .= '?' . http_build_query($params);
        elseif (in_array($method, ['POST', 'PUT', 'PATCH']))
            $options['json'] = $params;

        try {
            $response = $this->httpClient->request($method, $url, $options);
            $data = json_decode($response->getBody()->getContents(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE)
                throw new ApiException('Error al decodificar respuesta JSON: ' . json_last_error_msg());

            return $data;
        } catch (RequestException $e) {
            $this->handleRequestException($e);
        }
    }

    /**
     * Acceso al recurso de usuario
     */
    public function usuario(): UsuarioResource {
        return new UsuarioResource($this);
    }

    /**
     * Maneja las excepciones de peticiones HTTP
     */
    private function handleRequestException(RequestException $e): void {
        $response = $e->getResponse();
        
        if (!$response)
            throw new ApiException('Error de conexión: ' . $e->getMessage());

        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        
        try {
            $errorData = json_decode($body, true);
            $errorMessage = $errorData['error'] ?? $errorData['message'] ?? 'Error desconocido';
        } catch (\Exception $jsonException) {
            $errorMessage = $body ?: 'Error HTTP ' . $statusCode;
        }

        if ($statusCode >= 400 && $statusCode < 500)
            throw new OAuthException($errorMessage, $statusCode);
        else
            throw new ApiException($errorMessage, $statusCode);
    }
}
