<?php

namespace App\DataFixtures;

use App\Entity\ApiToken;
use App\Factory\ApiTokenFactory;
use App\Factory\DragonTreasureFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        UserFactory::createOne([
            'email' => 'test@test.fr',
            'password' => 'pass',
            'username' => 'Miaoustik',
            'roles' => ['ROLE_USER'],
        ]);

        UserFactory::createMany(10);

        DragonTreasureFactory::createMany(40, function () {
            return [
                'owner' => UserFactory::random()
            ];
        });

        //test token is tcp_{token.locator}.251651ec82ed5144bd2e
        ApiTokenFactory::createMany(30);
    }
}
