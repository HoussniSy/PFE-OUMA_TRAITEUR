<?php

namespace App\ApiResource;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\OpenApi;

/**
 * Décorateur OpenApi pour ajouter le schéma de sécurité JWT Bearer.
 * Cela permet à Swagger UI de transmettre le token JWT via le header Authorization.
 */
class JwtOpenApiDecorator implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        // Ajouter le schéma de sécurité JWT Bearer
        $securitySchemes = $openApi->getComponents()->getSecuritySchemes() ?: new \ArrayObject();
        $securitySchemes['JWT'] = new \ArrayObject([
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
            'description' => 'Entrez votre token JWT obtenu via POST /api/login',
        ]);

        // Appliquer la sécurité globalement à toutes les opérations
        $openApi = $openApi->withSecurity([['JWT' => []]]);

        return $openApi;
    }
}
