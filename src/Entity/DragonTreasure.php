<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\DragonTreasureRepository;
use App\State\DragonTreasureStateProcessor;
use App\State\DragonTreasureStateProvider;
use App\Validator\IsValidOwner;
use Carbon\Carbon;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;
use function Symfony\Component\String\u;

#[ORM\Entity(repositoryClass: DragonTreasureRepository::class)]
#[ApiResource(
    shortName: "Treasure",
    description: 'A rare and valuable treasure.',
    operations: [
        new Get(
            normalizationContext: [
                'groups' => ['treasure:read', 'treasure:item:get']
            ]
        ),
        new GetCollection(),
        new Post(
            security: 'is_granted("ROLE_TREASURE_CREATE")'
        ),
        new Patch(
            security: 'is_granted("EDIT", object)',
        ),
    ],
    formats: [
        'jsonld',
        'json',
        'csv' => 'text/csv'
    ],
    normalizationContext: [
        'groups' => ['treasure:read']
    ],
    denormalizationContext: [
        'groups' => ['treasure:write']
    ],
    paginationItemsPerPage: 10,
    provider: DragonTreasureStateProvider::class,
    processor: DragonTreasureStateProcessor::class
)]
#[ApiResource(
    uriTemplate: '/users/{user_id}/treasures.{_format}',
    shortName: "Treasure",
    operations: [
        new GetCollection()
    ],
    uriVariables: [
        'user_id' => new Link(
            fromProperty: 'dragonTreasures',
            fromClass: User::class
        )
    ],
    normalizationContext: [
        'groups' => ['treasure:read']
    ],
    provider: DragonTreasureStateProvider::class,
    processor: DragonTreasureStateProcessor::class
)]
#[ApiFilter(SearchFilter::class, properties: ['owner.username' => 'partial'])]
class DragonTreasure
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['treasure:read', 'treasure:write', 'user:read'])]
    #[ApiFilter(SearchFilter::class, strategy: 'partial')]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50, maxMessage: 'Describe your loot in 50 chars or less.')]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['treasure:read'])]
    #[ApiFilter(SearchFilter::class, strategy: 'partial')]
    private ?string $description = null;

    /**
     * @var int|null
     * The estimated value of this treasure, in gold coins.
     */
    #[ORM\Column]
    #[Groups(['treasure:read', 'treasure:write', 'user:read'])]
    #[ApiFilter(RangeFilter::class)]
    #[Assert\GreaterThanOrEqual(0)]
    private ?int $value = 0;

    #[ORM\Column]
    #[Groups(['treasure:read', 'treasure:write'])]
    #[Assert\GreaterThanOrEqual(0)]
    #[Assert\LessThanOrEqual(10)]
    private ?int $coolFactor = 0;

    #[ORM\Column]
    private \DateTimeImmutable $plunderedAt;

    #[ORM\Column]
    #[ApiFilter(BooleanFilter::class)]
    #[Groups(['admin:read', 'admin:write', 'owner:read', 'treasure:write'])]
    private ?bool $isPublished = false;

    #[ORM\ManyToOne(inversedBy: 'dragonTreasures')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['treasure:read', 'treasure:write'])]
    #[Assert\Valid]
    #[ApiFilter(SearchFilter::class, strategy: 'exact')]
    #[IsValidOwner]
    private ?User $owner = null;

    private bool $isOwnedByAuthenticatedUser;

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'dragonTreasure', orphanRemoval: true)]
    private Collection $notifications;

    public function __construct(string $name = null)
    {
        $this->name = $name;
        $this->plunderedAt = new \DateTimeImmutable();
        $this->notifications = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /*public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }*/

    public function getDescription(): ?string
    {
        return $this->description;
    }

    #[Groups(['treasure:read'])]
    public function getShortDescription(): ?string
    {
        return u($this->description)->truncate(40, '...');
    }

    #[Groups(['treasure:write'])]
    #[SerializedName('description')]
    public function setTextDescription(string $description): static
    {
        $this->description = nl2br($description);

        return $this;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function setValue(int $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getCoolFactor(): ?int
    {
        return $this->coolFactor;
    }

    public function setCoolFactor(int $coolFactor): static
    {
        $this->coolFactor = $coolFactor;

        return $this;
    }

    public function getPlunderedAt(): \DateTimeImmutable
    {
        return $this->plunderedAt;
    }

    public function setPlunderedAt(\DateTimeImmutable $plunderedAt): static
    {
        $this->plunderedAt = $plunderedAt;

        return $this;
    }

    /**
     * A human-readable representation of when this treasure was plundered.
     */
    #[Groups(['treasure:read'])]
    public function getPlunderedAtAgo(): string
    {
        return Carbon::instance($this->plunderedAt)->diffForHumans();
    }

    public function getIsPublished(): ?bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    #[Groups(['treasure:read'])]
    #[SerializedName('isMine')]
    public function getIsOwnedByAuthenticatedUser(): bool
    {
        if (!isset($this->isOwnedByAuthenticatedUser)) {
            throw new \LogicException("You must call setIsOwnedByAuthenticatedUser() before getIsOwnedByAuthenticatedUser().");
        }

        return $this->isOwnedByAuthenticatedUser;
    }

    /**
     * @param bool $isOwnedByAuthenticatedUser
     * @return DragonTreasure
     */
    public function setIsOwnedByAuthenticatedUser(bool $isOwnedByAuthenticatedUser): DragonTreasure
    {
        $this->isOwnedByAuthenticatedUser = $isOwnedByAuthenticatedUser;
        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setDragonTreasure($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getDragonTreasure() === $this) {
                $notification->setDragonTreasure(null);
            }
        }

        return $this;
    }


}
