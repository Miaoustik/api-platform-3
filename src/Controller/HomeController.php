<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class HomeController extends AbstractController
{
    #[Route('/')]
    public function homepage(NormalizerInterface $normalizer, #[CurrentUser] User $user = null): Response
    {
        return $this->render('homepage.html.twig', [
            'userData' => $normalizer->normalize($user, 'jsonld', [
                'groups' => 'user:read'
            ])
        ]);
    }
}