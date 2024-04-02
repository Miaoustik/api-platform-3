<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\DragonTreasure;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class DragonTreasureSetOwnerProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Security $security,
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): DragonTreasure
    {
        if ($data instanceof DragonTreasure && $data->getOwner() === null && $this->security->getUser() !== null) {
            $data->setOwner($this->security->getUser());
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
