<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'utilisateur')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read'])]
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read'])]
    private ?string $firstname = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['user:read'])]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $created_at = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $born_date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $date_activation = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?bool $is_avaible = false;

    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    //#[ORM\Column(mappedBy: 'createur', targetEntity: Ressource::class)]
    private Collection $ressources;

    #[ORM\ManyToMany(targetEntity: Ressource::class)]
    #[ORM\JoinTable(name: 'user_favorites')]
    private Collection $favorites;

    public function __construct()
    {
        $this->ressources = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->created_at = new \DateTime();
        $this->roles = ['ROLE_CONNECTED'];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = strtolower(trim($email));

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTime $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getBornDate(): ?\DateTime
    {
        return $this->born_date;
    }

    public function setBornDate(\DateTime $born_date): static
    {
        $this->born_date = $born_date;

        return $this;
    }

    public function getDateActivation(): ?\DateTime
    {
        return $this->date_activation;
    }

    public function setDateActivation(\DateTime $date_activation): static
    {
        $this->date_activation = $date_activation;

        return $this;
    }

    public function isAvaible(): ?bool
    {
        return $this->is_avaible;
    }

    public function setIsAvaible(bool $is_avaible): static
    {
        $this->is_avaible = $is_avaible;

        return $this;
    }

    public function getRoles(): array
    {
        // TODO: Implement getRoles() method.
        $roles = $this->roles;
        $roles[] = 'ROLE_CONNECTED';
        return array_unique($roles);
    }

    /**
     * @param array $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        // TODO: Implement getUserIdentifier() method.
        return (string) $this->email;
    }

    /**
     * @return Collection<int, Ressource>
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(Ressource $favorite): static
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites->add($favorite);
        }

        return $this;
    }

    public function removeFavorite(Ressource $favorite): static
    {
        $this->favorites->removeElement($favorite);

        return $this;
    }
}
