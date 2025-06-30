<?php

namespace Solinte\SdkPhp\Resources\Usuario;

use Solinte\SdkPhp\Client;

class UsuarioResource {
    private Client $client;

    public function __construct(Client $client) {
        $this->client = $client;
    }

    /**
     * Obtiene el email del usuario
     */
    public function email(): EmailResource {
        return new EmailResource($this->client);
    }

    /**
     * Obtiene el perfil del usuario
     */
    public function perfil(): PerfilResource {
        return new PerfilResource($this->client);
    }

    /**
     * Obtiene los roles del usuario
     */
    public function roles(): RolesResource {
        return new RolesResource($this->client);
    }
}
