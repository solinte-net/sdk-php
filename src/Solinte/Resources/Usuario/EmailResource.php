<?php

namespace Solinte\SdkPhp\Resources\Usuario;

use Solinte\SdkPhp\Client;

class EmailResource {
    private Client $client;

    public function __construct(Client $client) {
        $this->client = $client;
    }

    /**
     * Obtiene el email del usuario
     */
    public function get(): array {
        return $this->client->request('GET', '/usuario/email');
    }
} 