<?php

namespace App\Entity;

use App\Repository\LogConnexionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogConnexionRepository::class)]
#[ORM\Table(name: 'log_connexion')]
class LogConnexion
{
    public const STATUT_SUCCESS   = 'success';
    public const STATUT_FAILURE   = 'failure';
    public const STATUT_SUSPENDED = 'suspended';
    public const STATUT_LOGOUT    = 'logout';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private string $statut;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ip_address = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $date_connexion;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    public function __construct(string $statut, ?string $ip = null, ?User $user = null)
    {
        $this->statut         = $statut;
        $this->ip_address     = $ip;
        $this->user           = $user;
        $this->date_connexion = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    public function getIpAddress(): ?string { return $this->ip_address; }
    public function setIpAddress(?string $ip): static { $this->ip_address = $ip; return $this; }

    public function getDateConnexion(): \DateTime { return $this->date_connexion; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }
}
