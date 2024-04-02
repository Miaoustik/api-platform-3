<?php

namespace App\Validator;

use App\Entity\DragonTreasure;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class TreasuresAllowedOwnerChangeValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly AuthorizationCheckerInterface $checker
    )
    {
    }

    public function validate($value, Constraint $constraint)
    {
        /* @var TreasuresAllowedOwnerChange $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        assert($value instanceof Collection);

        $unitOfWork = $this->manager->getUnitOfWork();

        foreach ($value as $dragonTreasure) {
            assert($dragonTreasure instanceof DragonTreasure);

            $originalData = $unitOfWork->getOriginalEntityData($dragonTreasure);

            if (!$originalData['owner_id']) {
                continue;
            }

            if (
                !$this->checker->isGranted("ROLE_ADMIN") &&
                $originalData['owner_id'] !== $dragonTreasure->getOwner()->getId()
            ) {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();
            }
        }
    }
}
