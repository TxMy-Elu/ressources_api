<?php

namespace App\Entity;

use App\Repository\LogActionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogActionRepository::class)]
#[ORM\Table(name: 'log_action')]
class LogAction
{
    // Actions utilisateur — auth
    public const ACTION_REGISTER                 = 'register';
    public const ACTION_PASSWORD_RESET_REQUEST   = 'password_reset_request';
    public const ACTION_PASSWORD_RESET           = 'password_reset';

    // Actions utilisateur — ressources
    public const ACTION_RESOURCE_VIEW     = 'resource_view';
    public const ACTION_RESOURCE_CREATE   = 'resource_create';
    public const ACTION_RESOURCE_UPDATE   = 'resource_update';
    public const ACTION_RESOURCE_DELETE   = 'resource_delete';
    public const ACTION_RESOURCE_SAVE     = 'resource_save';
    public const ACTION_RESOURCE_UNSAVE   = 'resource_unsave';
    public const ACTION_COMMENT_ADD       = 'comment_add';

    // Actions participation / progression
    public const ACTION_RESOURCE_PARTICIPATE = 'resource_participate';
    public const ACTION_RESOURCE_PROGRESS    = 'resource_progress';

    // Actions utilisateur — profil
    public const ACTION_USER_UPDATE  = 'user_update';
    public const ACTION_USER_DELETE  = 'user_delete';

    // Actions contact
    public const ACTION_CONTACT = 'contact';

    // Actions modération / admin
    public const ACTION_MODERATE_VALIDATE = 'moderate_validate';
    public const ACTION_MODERATE_SUSPEND  = 'moderate_suspend';
    public const ACTION_USER_PROMOTE      = 'user_promote';
    public const ACTION_USER_SUSPEND      = 'user_suspend';
    public const ACTION_USER_ACTIVATE     = 'user_activate';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $action;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $details = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ip_address = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $date_action;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Ressource::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Ressource $ressource = null;

    public function __construct(
        string    $action,
        ?User     $user     = null,
        ?Ressource $ressource = null,
        ?string   $details  = null,
        ?string   $ip       = null
    ) {
        $this->action      = $action;
        $this->user        = $user;
        $this->ressource   = $ressource;
        $this->details     = $details;
        $this->ip_address  = $ip;
        $this->date_action = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getAction(): string { return $this->action; }
    public function setAction(string $action): static { $this->action = $action; return $this; }

    public function getDetails(): ?string { return $this->details; }
    public function setDetails(?string $details): static { $this->details = $details; return $this; }

    public function getIpAddress(): ?string { return $this->ip_address; }
    public function setIpAddress(?string $ip): static { $this->ip_address = $ip; return $this; }

    public function getDateAction(): \DateTime { return $this->date_action; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getRessource(): ?Ressource { return $this->ressource; }
    public function setRessource(?Ressource $ressource): static { $this->ressource = $ressource; return $this; }
}
