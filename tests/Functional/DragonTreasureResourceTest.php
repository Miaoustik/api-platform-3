<?php

namespace App\Tests\Functional;

use App\Entity\ApiToken;
use App\Factory\ApiTokenFactory;
use App\Factory\DragonTreasureFactory;
use App\Factory\UserFactory;
use Zenstruck\Browser\HttpOptions;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class DragonTreasureResourceTest extends ApiTestCase
{
    use ResetDatabase;
    use Factories;

    public function testGetCollectionOfTreasures(): void
    {
        DragonTreasureFactory::createMany(5);

        $json = $this->browser()
            ->get('/api/treasures')
            ->assertJson()
            ->json();

        $this->assertSame(array_keys($json->decoded()['hydra:member'][0]), [
            "@id",
            "@type",
            "name",
            "description",
            "value",
            "coolFactor",
            "owner",
            "shortDescription",
            "plunderedAtAgo",
        ]);
    }

    public function testPostToCreateTreasure(): void
    {
        $user = UserFactory::createOne()->object();

        $this->browser()
            ->actingAs($user)
            ->post('/api/treasures', [
                'json' => []
            ])
            ->assertStatus(422)
            ->post('/api/treasures', HttpOptions::json([
                'name' => "A treasure",
                'description' => "Description of a Treasure",
                'coolFactor' => 5,
                'value' => 100000,
                'owner' => '/api/users/'. $user->getId()
            ]))
            ->assertStatus(201)
            ->assertJsonMatches('name', "A treasure")
        ;
    }

    public function testPostToCreateTreasureWithApiToken(): void
    {
        $user = UserFactory::createOne()->object();

        /** @var ApiToken $token */
        $token = ApiTokenFactory::createOne()->object();

        /**
         * Check ApiTokenFactory for the randomString
         */
        self::assertSame(ApiToken::PERSONAL_ACCESS_TOKEN_PREFIX.$token->getLocator().'.'.'251651ec82ed5144bd2e', $token->getTokenString());

        $this->browser()
            ->post('/api/treasures', HttpOptions::json([
                'name' => "A treasure",
                'description' => "Description of a Treasure",
                'coolFactor' => 5,
                'value' => 100000,
                'owner' => '/api/users/'. $user->getId()
            ])->withHeader('Authorization', 'Bearer FOO'))
            ->assertStatus(401)
            ->post('/api/treasures', HttpOptions::json([])
                ->withHeader('Authorization', 'Bearer '. $token->getTokenString()))
            ->assertStatus(422)
            ->post('/api/treasures', HttpOptions::json([
                'name' => "A treasure",
                'description' => "Description of a Treasure",
                'coolFactor' => 5,
                'value' => 100000,
                'owner' => '/api/users/'. $user->getId()
            ])->withHeader('Authorization', 'Bearer '. $token->getTokenString()))
            ->assertStatus(201)
        ;
    }
}