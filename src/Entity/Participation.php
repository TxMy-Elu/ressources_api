<?php

namespace App\Entity;

use App\Repository\ParticipationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipationRepository::class)]
#[ORM\Table(name: 'participation')]
#[ORM\UniqueConstraint(name: 'uniq_participation_user_ressource', columns: ['user_id', 'ressource_id'])]
class Participation
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

    /** Date de la première consultation */
    #[ORM\Column(type: 'datetime')]
    private \DateTime $date_participation;

    /** Ressource mise de côté (bookmark) */
    #[ORM\Column(type: 'boolean')]
    private bool $mise_cote = false;

    /** Utilisateur invité à consulter cette ressource */
    #[ORM\Column(type: 'boolean')]
    private bool $invite = false;

    public function __construct(User $user, Ressource $ressource)
    {
        $this->user               = $user;
        $this->ressource          = $ressource;
        $this->date_participation = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): User { return $this->user; }

    public function getRessource(): Ressource { return $this->ressource; }

    public function getDateParticipation(): \DateTime { return $this->date_participation; }

    public function isMiseCote(): bool { return $this->mise_cote; }
    public function setMiseCote(bool $miseCote): static { $this->mise_cote = $miseCote; return $this; }

    public function isInvite(): bool { return $this->invite; }
    public function setInvite(bool $invite): static { $this->invite = $invite; return $this; }
}
