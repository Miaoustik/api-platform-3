<?php

namespace App\State;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\DragonTreasure;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;


class DragonTreasureStateProvider implements ProviderInterface
{
    public function __construct(
        #[Autowire(service: ItemProvider::class)]
        private readonly ProviderInterface $itemProvider,
        #[Autowire(service: CollectionProvider::class)]
        private readonly ProviderInterface $collectionProvider,
        private readonly Security $security
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $treasures = $this->collectionProvider->provide($operation, $uriVariables, $context);

            foreach ($treasures as $treasure) {
                $this->setTreasureIsOwnedByAuthenticatedUser($treasure);
            }
            return $treasures;
        }

        $treasure = $this->itemProvider->provide($operation, $uriVariables, $context);

        if ($treasure !== null) {
            $this->setTreasureIsOwnedByAuthenticatedUser($treasure);
        }

        return $treasure;
    }

    public function setTreasureIsOwnedByAuthenticatedUser(DragonTreasure $treasure): void
    {
        $treasure->setIsOwnedByAuthenticatedUser(
            $this->security->getUser() === $treasure->getOwner()
        );
    }
}
