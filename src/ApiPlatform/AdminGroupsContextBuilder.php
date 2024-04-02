<?php

namespace App\ApiPlatform;

use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use App\Entity\DragonTreasure;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[AsDecorator("api_platform.serializer.context_builder")]
class AdminGroupsContextBuilder implements SerializerContextBuilderInterface
{
    public function __construct(
        private readonly SerializerContextBuilderInterface $decorated,
        private readonly AuthorizationCheckerInterface $checker
    )
    {
    }

    public function createFromRequest(Request $request, bool $normalization, ?array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);
        $resourceClass = $context['resource_class'] ?? null;

        if ($resourceClass === DragonTreasure::class && isset($context['groups']) && $this->checker->isGranted('ROLE_ADMIN')) {
            $context['groups'][] = $normalization ? 'admin:read' : 'admin:write';
        }

        return $context;
    }
}