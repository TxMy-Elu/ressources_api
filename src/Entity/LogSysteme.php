<?php

namespace App\Entity;

use App\Repository\LogSystemeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogSystemeRepository::class)]
#[ORM\Table(name: 'log_systeme')]
class LogSysteme
{
    public const NIVEAU_INFO     = 'info';
    public const NIVEAU_WARNING  = 'warning';
    public const NIVEAU_ERROR    = 'error';
    public const NIVEAU_CRITICAL = 'critical';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private string $niveau;

    #[ORM\Column(length: 255)]
    private string $message;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contexte = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $date_log;

    public function __construct(string $niveau, string $message, ?string $contexte = null)
    {
        $this->niveau   = $niveau;
        $this->message  = $message;
        $this->contexte = $contexte;
        $this->date_log = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getNiveau(): string { return $this->niveau; }
    public function setNiveau(string $niveau): static { $this->niveau = $niveau; return $this; }

    public function getMessage(): string { return $this->message; }
    public function setMessage(string $message): static { $this->message = $message; return $this; }

    public function getContexte(): ?string { return $this->contexte; }
    public function setContexte(?string $contexte): static { $this->contexte = $contexte; return $this; }

    public function getDateLog(): \DateTime { return $this->date_log; }
}
