<?php

namespace App\Security;

use App\Entity\ApiToken;
use App\Repository\ApiTokenRepository;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class ApiTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(private readonly ApiTokenRepository $repository)
    {
    }

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {

        $exploded = explode('.', $accessToken, 2);
        $locator = substr($exploded[0], strlen(ApiToken::PERSONAL_ACCESS_TOKEN_PREFIX));

        $accessToken = $this->repository->findOneBy([
            'locator' => $locator,
        ]);

        if (null === $accessToken) {
            throw new BadCredentialsException();
        }

        if (!$accessToken->isValid()) {
            throw new CustomUserMessageAuthenticationException("Token expired.");
        }

        $accessToken->getOwnedBy()->markAsTokenAuthenticated($accessToken->getScopes());

        return new UserBadge($accessToken->getOwnedBy()->getUserIdentifier());
    }
}