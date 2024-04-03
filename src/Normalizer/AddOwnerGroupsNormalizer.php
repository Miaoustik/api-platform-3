<?php

namespace App\Normalizer;

use App\Entity\DragonTreasure;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

class AddOwnerGroupsNormalizer implements NormalizerInterface, SerializerAwareInterface, DenormalizerInterface
{

    private DenormalizerInterface|NormalizerInterface $decorated;

    public function __construct(
        NormalizerInterface $decorated,
        private readonly Security $security
    )
    {
        if (!$decorated instanceof DenormalizerInterface) {
            throw new \InvalidArgumentException(sprintf('The decorated normalizer must implement the %s.', DenormalizerInterface::class));
        }

        $this->decorated = $decorated;
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): float|int|bool|\ArrayObject|array|string|null
    {
        if ($object instanceof DragonTreasure && $object->getOwner() === $this->security->getUser()) {
            $context['groups'][] = 'owner:read';
        }

        return $this->decorated->normalize($object, $format, $context);
    }

    public function supportsNormalization(mixed $data, ?string $format = null): bool
    {
        return $this->decorated->supportsNormalization($data, $format);
    }

    public function setSerializer(SerializerInterface $serializer)
    {
        if($this->decorated instanceof SerializerAwareInterface) {
            $this->decorated->setSerializer($serializer);
        }
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = [])
    {
        return $this->decorated->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null): bool
    {
        return $this->decorated->supportsDenormalization($data, $type, $format);
    }
}