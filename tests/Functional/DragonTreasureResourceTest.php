<?php

namespace App\Tests\Functional;

use App\Entity\ApiToken;
use App\Factory\ApiTokenFactory;
use App\Factory\DragonTreasureFactory;
use App\Factory\UserFactory;
use Zenstruck\Browser\HttpOptions;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @method AppKernelBrowser browser(array $options = [], array $server = [])
 */
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

        $decoded = $json->decoded();

        $this->assertCount(5, $decoded['hydra:member']);

        $this->assertSame(array_keys($decoded['hydra:member'][0]), [
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

    public function testGetCollectionOfTreasuresAsAdmin(): void
    {
        DragonTreasureFactory::createMany(5, [
            'isPublished' => false
        ]);
        $admin = UserFactory::new()->asAdmin()->create();

        $json = $this->browser()
            ->actingAs($admin)
            ->get('/api/treasures')
            ->assertJson()
            ->json();

        $decoded = $json->decoded();

        $this->assertCount(5, $decoded['hydra:member']);
        $this->assertSame($decoded['hydra:member'][0]['isPublished'], false);

        $this->assertSame(array_keys($decoded['hydra:member'][0]), [
            "@id",
            "@type",
            "name",
            "description",
            "value",
            "coolFactor",
            "isPublished",
            "owner",
            "shortDescription",
            "plunderedAtAgo",
        ]);
    }

    public function testGetCollectionOfTreasuresAsOwner(): void
    {
        $user = UserFactory::createOne();

        DragonTreasureFactory::createMany(5, [
            'isPublished' => false,
            'owner' => $user
        ]);

        $json = $this->browser()
            ->actingAs($user)
            ->get('/api/treasures')
            ->assertJson()
            ->json();

        $decoded = $json->decoded();

        $this->assertCount(5, $decoded['hydra:member']);
        $this->assertSame($decoded['hydra:member'][0]['isPublished'], false);

        $this->assertSame(array_keys($decoded['hydra:member'][0]), [
            "@id",
            "@type",
            "name",
            "description",
            "value",
            "coolFactor",
            "isPublished",
            "owner",
            "shortDescription",
            "plunderedAtAgo",
        ]);
    }

    public function testPostToCreateTreasure(): void
    {
        $user = UserFactory::createOne();

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
                'owner' => '/api/users/'. $user->object()->getId()
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

    public function testPostToCreateTreasureDeniedScopeWithApiToken(): void
    {
        /** @var ApiToken $token */
        $token = ApiTokenFactory::createOne([
            'scopes' => []
        ])->object();

        $this->browser()
            ->post('/api/treasures', HttpOptions::json([
                'name' => "A treasure",
                'description' => "Description of a Treasure",
                'coolFactor' => 5,
                'value' => 100000,
                'owner' => '/api/users/'. $token->getOwnedBy()->getId()
            ])->withHeader('Authorization', 'Bearer '. $token->getTokenString()))
            ->assertStatus(403)
        ;
    }

    public function testPatchToUpdateTreasure(): void
    {
        $user = UserFactory::createOne();
        $treasure = DragonTreasureFactory::createOne([
            'owner' => $user
        ]);

        $this->browser()
            ->actingAs($user)
            ->patch('/api/treasures/'.$treasure->getId(), HttpOptions::json([
                'value' => 12345
            ]))
            ->assertStatus(200)
            ->assertJsonMatches('value', 12345);

        $user2 = UserFactory::createOne();
        $this->browser()
            ->actingAs($user2)
            ->patch('/api/treasures/'.$treasure->getId(), HttpOptions::json([
                'value' => 12345
            ]))
            ->assertStatus(403);

        $this->browser()
            ->actingAs($user)
            ->patch('/api/treasures/'.$treasure->getId(), HttpOptions::json([
                'value' => 12345,
                'owner' => '/api/users/'.$user2->object()->getId()
            ]))
            ->assertStatus(403);
    }

    public function testAdminCanPatchToEditTreasures(): void
    {
        $admin = UserFactory::new()->asAdmin()->create();

        $treasure = DragonTreasureFactory::createOne();

        $this->browser()
            ->actingAs($admin)
            ->patch('/api/treasures/'. $treasure->object()->getId(), HttpOptions::json([
                'value' => 456789
            ]))
            ->assertStatus(200)
            ->assertJsonMatches('value', 456789);
    }
}