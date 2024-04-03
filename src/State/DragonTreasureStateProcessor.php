<?php

namespace App\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Doctrine\Common\State\RemoveProcessor;
use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\DragonTreasure;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class DragonTreasureStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Security $security,
        #[Autowire(service: PersistProcessor::class)]
        private readonly ProcessorInterface $persistProcessor,
        #[Autowire(service: RemoveProcessor::class)]
        private readonly ProcessorInterface $removeProcessor,
        private readonly EntityManagerInterface $manager,
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        assert($data instanceof DragonTreasure);

        if ($operation instanceof DeleteOperationInterface) {
            return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
        }

        if (null !== $user = $this->security->getUser()) {

            if ($data->getOwner() === null) {
                $data->setOwner($user);
            }

            $data->setIsOwnedByAuthenticatedUser(
                $user === $data->getOwner()
            );
        }

        //set null if post request
        $previousData = $context['previous_data'] ?? null;

        if (
            $previousData instanceof DragonTreasure &&
            $data->getIsPublished() &&
            $data->getIsPublished() !== $previousData->getIsPublished()
        ) {
            $notification = (new Notification())
                ->setDragonTreasure($data)
                ->setMessage("Your dragon treasure has been published !");

            $this->manager->persist($notification);
            $this->manager->flush();
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
