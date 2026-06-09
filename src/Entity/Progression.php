<?php

namespace App\Entity;

use App\Repository\ProgressionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProgressionRepository::class)]
#[ORM\Table(name: 'progression')]
#[ORM\UniqueConstraint(name: 'uniq_progression_user_ressource', columns: ['user_id', 'ressource_id'])]
class Progression
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Ressource::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Ressource $ressource;

    /** false = en cours, true = terminé */
    #[ORM\Column(type: 'boolean')]
    private bool $statut = false;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updated_at;

    public function __construct(User $user, Ressource $ressource)
    {
        $this->user       = $user;
        $this->ressource  = $ressource;
        $this->updated_at = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): User { return $this->user; }

    public function getRessource(): Ressource { return $this->ressource; }

    public function isStatut(): bool { return $this->statut; }
    public function setStatut(bool $statut): static
    {
        $this->statut     = $statut;
        $this->updated_at = new \DateTime();
        return $this;
    }

    public function getUpdatedAt(): \DateTime { return $this->updated_at; }
}
