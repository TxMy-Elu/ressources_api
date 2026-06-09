<?php

namespace App\Entity;

use App\Repository\LogMessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogMessageRepository::class)]
#[ORM\Table(name: 'log_message')]
class LogMessage
{
    public const ACTION_MESSAGE_SEND   = 'message_send';
    public const ACTION_MESSAGE_REPLY  = 'message_reply';
    public const ACTION_MESSAGE_DELETE = 'message_delete';

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

    /** Auteur de l'action (null si compte supprimé) */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    /** Commentaire concerné (null si commentaire supprimé) */
    #[ORM\ManyToOne(targetEntity: Comment::class)]
    #[ORM\JoinColumn(name: 'comment_id', nullable: true, onDelete: 'SET NULL')]
    private ?Comment $comment = null;

    public function __construct(
        string   $action,
        ?User    $user    = null,
        ?Comment $comment = null,
        ?string  $details = null,
        ?string  $ip      = null
    ) {
        $this->action      = $action;
        $this->user        = $user;
        $this->comment     = $comment;
        $this->details     = $details;
        $this->ip_address  = $ip;
        $this->date_action = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getAction(): string { return $this->action; }

    public function getDetails(): ?string { return $this->details; }
    public function setDetails(?string $details): static { $this->details = $details; return $this; }

    public function getIpAddress(): ?string { return $this->ip_address; }

    public function getDateAction(): \DateTime { return $this->date_action; }

    public function getUser(): ?User { return $this->user; }

    public function getComment(): ?Comment { return $this->comment; }
}
