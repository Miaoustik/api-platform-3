<?php

namespace App\ApiPlatform;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\DragonTreasure;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class DragonTreasureIsPublishedExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(private readonly Security $security)
    {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $this->addIsPublishedWhere($resourceClass, $queryBuilder);
    }

    public function addIsPublishedWhere(string $resourceClass, QueryBuilder $queryBuilder): void
    {
        if ($resourceClass !== DragonTreasure::class || $this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->andWhere(sprintf("%s.isPublished = :isPublished", $rootAlias))
            ->setParameter('isPublished', true);

        if (null !== $user = $this->security->getUser()) {
            $queryBuilder
                ->orWhere(sprintf("%s.owner = :owner", $rootAlias))
                ->setParameter('owner', $user);
        }
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, ?Operation $operation = null, array $context = []): void
    {
        $this->addIsPublishedWhere($resourceClass, $queryBuilder);
    }
}