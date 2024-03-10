<?php

namespace App\Controller;

use ApiPlatform\Api\IriConverterInterface;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['POST'])]
    public function login(IriConverterInterface $converter, #[CurrentUser] User $user = null): Response
    {

        if (!$user) {
            return $this->json([
                'error' => 'Set Content-Type to application/json'
            ], 401);
        }

        return new Response(null, 204, [
            'Location' => $converter->getIriFromResource($user)
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \Exception('This should never be reached.');
    }
}