<?php

namespace App\Tests\Functional;

use App\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Browser\HttpOptions;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @method AppKernelBrowser browser(array $options = [], array $server = [])
 */
class UserResourceTest extends ApiTestCase
{
    use Factories;
    use ResetDatabase;

    public function testPostToCreateUser()
    {
        $this->browser()
            ->post('/api/users', HttpOptions::json([
                'email' => 'testemail@test.fr',
                'password' => 'test',
                'username' => 'tester'
            ]))
            ->assertStatus(Response::HTTP_CREATED)
            ->post('/login', HttpOptions::json([
                'email' => 'testemail@test.fr',
                'password' => 'test'
            ]))
            ->assertSuccessful()
        ;
    }

    public function testPatchToUpdateUser()
    {
        $user = UserFactory::createOne();

        $this->browser()
            ->actingAs($user)
            ->apiPatch('/api/users/'.$user->object()->getId(), [
                'username' => 'changed'
            ])
            ->assertStatus(Response::HTTP_OK)
            ->get('/api/users/'.$user->object()->getId())
            ->assertJsonMatches("username", 'changed');
    }
}