<?php

namespace Solinte\SdkPhp\Resources\Usuario;

use Solinte\SdkPhp\Client;

class RolesResource {
    private Client $client;

    public function __construct(Client $client) {
        $this->client = $client;
    }

    /**
     * Obtiene el listado de roles del usuario
     */
    public function get(): array {
        return $this->client->request('GET', '/usuario/roles');
    }

    /**
     * Obtiene el saldo de un rol específico
     */
    public function saldo(string $rid, string $fecha = 'hoy'): array {
        return $this->client->request('GET', "/usuario/roles/saldo/{$rid}/{$fecha}");
    }

    /**
     * Incorpora un nuevo rol
     */
    public function incorpora(array $params): array {
        $requiredParams = ['descripcion', 'cuis', 'rol_codigo', 'rol_verificador'];
        
        foreach ($requiredParams as $param) {
            if (!isset($params[$param]))
                throw new \InvalidArgumentException("El parámetro '{$param}' es requerido");
        }

        return $this->client->request('POST', '/usuario/roles/incorpora', $params);
    }

    /**
     * Oculta un rol del listado
     */
    public function oculta(string $rid): array {
        return $this->client->request('POST', '/usuario/roles/oculta', ['rid' => $rid]);
    }

    /**
     * Renombra un rol
     */
    public function renombra(string $rid, string $descripcion): array {
        return $this->client->request('POST', '/usuario/roles/renombra', [
            'rid' => $rid,
            'descripcion' => $descripcion
        ]);
    }

    /**
     * Comparte un rol
     */
    public function comparte(string $rid, string $metodo, string $emails = ''): array {
        $params = [
            'rid' => $rid,
            'metodo' => $metodo
        ];

        if ($emails)
            $params['emails'] = $emails;

        return $this->client->request('POST', '/usuario/roles/comparte', $params);
    }
} 