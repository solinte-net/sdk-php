<?php

namespace Solinte\SdkPhp;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use Solinte\SdkPhp\Exceptions\OAuthException;
use Solinte\SdkPhp\Exceptions\ValidationException;

class OAuth2Client {
    private HttpClient $httpClient;
    private Config $config;
    private ?string $accessToken = null;
    private ?string $refreshToken = null;
    private ?int $expiresAt = null;

    public function __construct(Config $config) {
        $this->config = $config;
        $this->httpClient = new HttpClient([
            'timeout' => $config->getTimeout(),
            'verify' => $config->getVerify(),
            'headers' => [
                'User-Agent' => $config->getUserAgent(),
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ]);
    }

    /**
     * Obtiene la URL de autorización OAuth 2.0
     */
    public function getAuthorizationUrl(array $params = []): string {
        $defaultParams = [
            'response_type' => 'code',
            'client_id' => $this->config->getClientId(),
            'state' => $this->generateState(),
            'scope' => 'basic',
        ];

        $params = array_merge($defaultParams, $params);
        
        // Validar scopes si se proporcionan
        if (isset($params['scope']) && is_string($params['scope'])) {
            $scopes = explode(' ', $params['scope']);
            Config::validateScopes($scopes);
        }

        return $this->config->getOAuthBaseUrl() . '/Autorizar?' . http_build_query($params);
    }

    /**
     * Intercambia el código de autorización por un access token
     */
    public function exchangeCodeForToken(string $code, ?string $state = null): array {
        $params = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->config->getClientId(),
            'client_secret' => $this->config->getClientSecret(),
            'code' => $code,
        ];

        if ($this->config->getRedirectUri()) {
            $params['redirect_uri'] = $this->config->getRedirectUri();
        }

        if ($state) {
            $params['state'] = $state;
        }

        return $this->requestToken($params);
    }

    /**
     * Refresca el access token usando el refresh token
     */
    public function refreshAccessToken(string $refreshToken, array $scopes = []): array {
        $params = [
            'grant_type' => 'refresh_token',
            'client_id' => $this->config->getClientId(),
            'client_secret' => $this->config->getClientSecret(),
            'refresh_token' => $refreshToken,
        ];

        if (!empty($scopes)) {
            Config::validateScopes($scopes);
            $params['scope'] = implode(' ', $scopes);
        }

        return $this->requestToken($params);
    }

    /**
     * Obtiene un token usando el flujo de password (para aplicaciones confidenciales)
     */
    public function getTokenWithPassword(string $username, string $password, array $scopes = []): array {
        $params = [
            'grant_type' => 'password',
            'client_id' => $this->config->getClientId(),
            'client_secret' => $this->config->getClientSecret(),
            'username' => $username,
            'password' => $password,
        ];

        if (!empty($scopes)) {
            Config::validateScopes($scopes);
            $params['scope'] = implode(' ', $scopes);
        }

        return $this->requestToken($params);
    }

    /**
     * Establece el access token manualmente
     */
    public function setAccessToken(string $accessToken, ?int $expiresIn = null): void {
        $this->accessToken = $accessToken;
        
        if ($expiresIn)
            $this->expiresAt = time() + $expiresIn;
    }

    /**
     * Establece el refresh token
     */
    public function setRefreshToken(string $refreshToken): void {
        $this->refreshToken = $refreshToken;
    }

    /**
     * Obtiene el access token actual
     */
    public function getAccessToken(): ?string {
        return $this->accessToken;
    }

    /**
     * Obtiene el refresh token actual
     */
    public function getRefreshToken(): ?string {
        return $this->refreshToken;
    }

    /**
     * Verifica si el token ha expirado
     */
    public function isTokenExpired(): bool {
        if (!$this->expiresAt)
            return false; // Mmm, no sabemos si expiró, asumimos que no...

        return time() >= $this->expiresAt;
    }

    /**
     * Refresca el token si ha expirado
     */
    public function refreshTokenIfExpired(): bool {
        if ($this->isTokenExpired() && $this->refreshToken) {
            try {
                $this->refreshAccessToken($this->refreshToken);
                return true;
            } catch (OAuthException $e) {
                // Token de refresh también expiró
                $this->clearTokens();
                return false;
            }
        }

        return false;
    }

    /**
     * Limpia todos los tokens
     */
    public function clearTokens(): void {
        $this->accessToken = null;
        $this->refreshToken = null;
        $this->expiresAt = null;
    }

    /**
     * Obtiene información del token actual
     */
    public function getTokenInfo(): array {
        return [
            'access_token' => $this->accessToken ? '***' . substr($this->accessToken, -4) : null,
            'refresh_token' => $this->refreshToken ? '***' . substr($this->refreshToken, -4) : null,
            'expires_at' => $this->expiresAt,
            'is_expired' => $this->isTokenExpired(),
        ];
    }

    /**
     * Genera un estado aleatorio para OAuth
     */
    private function generateState(): string {
        return bin2hex(random_bytes(16));
    }

    /**
     * Realiza la petición de token
     */
    private function requestToken(array $params): array {
        try {
            $response = $this->httpClient->post($this->config->getOAuthBaseUrl() . '/Token', [
                'form_params' => $params,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE)
                throw new OAuthException('Error al decodificar respuesta JSON del token: ' . json_last_error_msg());

            if (isset($data['error']))
                throw new OAuthException('Error OAuth: ' . ($data['error_description'] ?? $data['error']));

            // Guardar tokens
            if (isset($data['access_token']))
                $this->accessToken = $data['access_token'];

            if (isset($data['refresh_token']))
                $this->refreshToken = $data['refresh_token'];

            if (isset($data['expires_in']))
                $this->expiresAt = time() + $data['expires_in'];

            return $data;
        } catch (RequestException $e) {
            $this->handleRequestException($e);
        }
    }

    /**
     * Maneja las excepciones de peticiones HTTP
     */
    private function handleRequestException(RequestException $e): void {
        $response = $e->getResponse();
        
        if (!$response)
            throw new OAuthException('Error de conexión: ' . $e->getMessage());

        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        
        try {
            $errorData = json_decode($body, true);
            $errorMessage = $errorData['error'] ?? $errorData['error_description'] ?? $errorData['message'] ?? 'Error OAuth desconocido';
        } catch (\Exception $jsonException) {
            $errorMessage = $body ?: 'Error HTTP ' . $statusCode;
        }

        throw new OAuthException($errorMessage, $statusCode);
    }
}
