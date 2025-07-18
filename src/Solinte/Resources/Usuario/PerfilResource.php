<?php

namespace Solinte\SdkPhp\Resources\Usuario;

use Solinte\SdkPhp\Client;

class PerfilResource {
    private Client $client;

    public function __construct(Client $client) {
        $this->client = $client;
    }

    /**
     * Obtiene el perfil completo del usuario
     */
    public function get(): array {
        return $this->client->request('GET', '/usuario/perfil');
    }
}
