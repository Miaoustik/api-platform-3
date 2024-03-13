<?php

namespace App\Entity;

use App\Repository\ApiTokenRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[ORM\Entity(repositoryClass: ApiTokenRepository::class)]
class ApiToken
{
    // tcp = treasure company personal
    public const PERSONAL_ACCESS_TOKEN_PREFIX = 'tcp_';

    public const SCOPE_USER_EDIT = 'ROLE_USER_EDIT';
    public const SCOPE_TREASURE_CREATE = 'ROLE_TREASURE_CREATE';
    public const SCOPE_TREASURE_EDIT = 'ROLE_TREASURE_EDIT';

    public const SCOPES = [
        self::SCOPE_USER_EDIT => 'Edit User',
        self::SCOPE_TREASURE_CREATE => 'Create Treasures',
        self::SCOPE_TREASURE_EDIT => 'Edit Treasures',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'apiTokens')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $ownedBy = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(length: 68)]
    private ?string $token = null;

    #[ORM\Column]
    private array $scopes = [];

    private string $tokenType;

    #[ORM\Column(length: 255)]
    private ?string $locator = null;

    private ?string $tokenString = null;

    /**
     * @return string|null
     */
    public function getTokenString(): ?string
    {
        return $this->tokenString;
    }

    /**
     * @param string $tokenType
     * Don't forget to call createToken method after creating a new ApiToken or doctrine will explode.
     * createToken will store the plain text token temporally in tokenString, u have to save it somewhere as you can't see it anymore.
     */
    public function __construct(string $tokenType = self::PERSONAL_ACCESS_TOKEN_PREFIX)
    {
        $this->tokenType = $tokenType;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwnedBy(): ?User
    {
        return $this->ownedBy;
    }

    public function setOwnedBy(?User $ownedBy): static
    {
        $this->ownedBy = $ownedBy;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function setScopes(array $scopes): static
    {
        $this->scopes = $scopes;

        return $this;
    }

    /**
     * @param User $user
     * @param UserPasswordHasherInterface $hasher
     * @param string|null $randomString
     * @return string
     * @throws \Exception
     * Don't forget to call createToken method after creating a new ApiToken or doctrine will explode.
     * createToken will store the plain text token temporally in tokenString, u have to save it somewhere as you can't see it anymore.
     */
    public function createToken(User $user, UserPasswordHasherInterface $hasher, string $randomString = null): void
    {
        if ($this->token) {
            throw new \LogicException('Token for this entity is already created.');
        }

        $string = $randomString ?? bin2hex(random_bytes(32));
        $this->token = $hasher->hashPassword($user, $string);
        $this->ownedBy = $user;
        $this->locator = $user->getId().'-'.bin2hex(random_bytes(5));

        $this->tokenString = $this->tokenType.$this->locator.'.'.$string;
    }

    public function isValid(): bool
    {
        return $this->expiresAt === null || $this->expiresAt > new \DateTimeImmutable();
    }

    public function getLocator(): ?string
    {
        return $this->locator;
    }

    public function setLocator(string $locator): static
    {
        $this->locator = $locator;

        return $this;
    }
}
