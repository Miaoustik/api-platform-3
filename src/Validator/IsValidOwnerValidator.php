<?php

namespace App\Validator;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IsValidOwnerValidator extends ConstraintValidator
{
    public function __construct(
        private readonly Security $security
    )
    {
    }

    public function validate($value, Constraint $constraint)
    {
        /* @var IsValidOwner $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user) {
            throw new \LogicException("IsValidOwnerValidator should only be called when an user is logged in.");
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        if ($user !== $value) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
