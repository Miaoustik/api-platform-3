<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\UserRepository;
use App\State\UserHashPasswordProcessor;
use App\Validator\TreasuresAllowedOwnerChange;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(
            security: 'is_granted("PUBLIC_ACCESS")',
            validationContext: [
                'groups' => ['Default', 'postValidation']
            ],
            processor: UserHashPasswordProcessor::class
        ),
        new Patch(
            security: 'is_granted("ROLE_USER_EDIT")'
        ),
        new Delete()
    ],
    normalizationContext: [
        'groups' => ['user:read']
    ],
    denormalizationContext: [
        'groups' => ['user:write']
    ],
    security: 'is_granted("ROLE_USER")'
)]
#[UniqueEntity(fields: 'email', message: "Cet email est déjà utilisé.")]
#[UniqueEntity(fields: 'username', message: "Ce nom d\'utilisateur est déjà pris.")]
#[ApiResource(
    uriTemplate: '/treasures/{treasure_id}/user.{_format}',
    operations: [
        new Get()
    ],
    uriVariables: [
        'treasure_id' => new Link(
            fromProperty: 'owner',
            fromClass: DragonTreasure::class
        )
    ],
    normalizationContext: [
        'groups' => ['user:read']
    ],
    security: 'is_granted("ROLE_USER")'
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(['user:read', 'user:write'])]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[Groups(['user:write'])]
    #[SerializedName('password')]
    #[Assert\NotBlank(groups: ['postValidation'])]
    private ?string $plainPassword = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['user:read', 'user:write', 'treasure:item:get', 'treasure:write', 'treasure:read'])]
    #[Assert\NotBlank]
    private ?string $username = null;

    // if a treasure get owner=null orphan will delete it
    #[ORM\OneToMany(targetEntity: DragonTreasure::class, mappedBy: 'owner', orphanRemoval: true)]
    #[Groups(['user:write'])]
    #[Assert\Valid]
    #[TreasuresAllowedOwnerChange]
    private Collection $dragonTreasures;

    #[ORM\OneToMany(targetEntity: ApiToken::class, mappedBy: 'ownedBy')]
    private Collection $apiTokens;

    /* Scopes given during API authentication */
    private ?array $accessTokenScopes = null;

    public function __construct()
    {
        $this->dragonTreasures = new ArrayCollection();
        $this->apiTokens = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        if (null === $this->accessTokenScopes) {
            $roles = $this->roles;
            $roles[] = 'ROLE_FULL_USER';
        } else {
            $roles = $this->accessTokenScopes;
        }

        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        $this->plainPassword = null;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return Collection<int, DragonTreasure>
     */
    public function getDragonTreasures(): Collection
    {
        return $this->dragonTreasures;
    }

    #[Groups(['user:read'])]
    #[SerializedName('dragonTreasures')]
    public function getPublishedDragonTreasures(): Collection
    {
        $criteria = Criteria::create()
            ->andWhere(Criteria::expr()->eq('isPublished', true));

        return $this->dragonTreasures->matching($criteria);
    }

    public function addDragonTreasure(DragonTreasure $dragonTreasure): static
    {
        if (!$this->dragonTreasures->contains($dragonTreasure)) {
            $this->dragonTreasures->add($dragonTreasure);
            $dragonTreasure->setOwner($this);
        }

        return $this;
    }

    public function removeDragonTreasure(DragonTreasure $dragonTreasure): static
    {
        if ($this->dragonTreasures->removeElement($dragonTreasure)) {
            // set the owning side to null (unless already changed)
            if ($dragonTreasure->getOwner() === $this) {
                $dragonTreasure->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ApiToken>
     */
    public function getApiTokens(): Collection
    {
        return $this->apiTokens;
    }

    public function addApiToken(ApiToken $apiToken): static
    {
        if (!$this->apiTokens->contains($apiToken)) {
            $this->apiTokens->add($apiToken);
            $apiToken->setOwnedBy($this);
        }

        return $this;
    }

    public function removeApiToken(ApiToken $apiToken): static
    {
        if ($this->apiTokens->removeElement($apiToken)) {
            // set the owning side to null (unless already changed)
            if ($apiToken->getOwnedBy() === $this) {
                $apiToken->setOwnedBy(null);
            }
        }

        return $this;
    }

    public function markAsTokenAuthenticated(array $scopes)
    {
        $this->accessTokenScopes = $scopes;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }
}
