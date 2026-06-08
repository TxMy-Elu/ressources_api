<?php

namespace App\Entity;

use App\Repository\RessourceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: RessourceRepository::class)]
class Ressource
{
    const STATUS_DRAFT = 'brouillon';
    const STATUS_PENDING = 'en attente';
    const STATUS_PUBLISHED = 'publie';
    const STATUS_SUSPENDED = 'suspendu';

    const VISIBILITE_PRIVATE = 'private';
    const VISIBILITE_SHARED = 'partage';
    const VISIBILITE_PUBLIC = 'publie';
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contenu = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $media = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $lien = null;

    #[ORM\Column(length: 50)]
    private ?string $type_ressource = null;

    #[ORM\Column(length: 20)]
    private string $statut = self::STATUS_DRAFT;

    #[ORM\Column(length: 20)]
    private string $visibilite = self::VISIBILITE_PUBLIC;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $date_publication = null;

    #[ORM\Column]
    private ?bool $est_privee = null;

    #[ORM\Column]
    private ?bool $est_verifie = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createur = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: "resources")]
    private ?Category $category = null;

    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'resource')]
    private Collection $comments;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMedia(): ?string
    {
        return $this->media;
    }

    /**
     * @param string|null $media
     */
    public function setMedia(?string $media): static
    {
        $this->media = $media;

        return $this;
    }

    public function getLien(): ?string
    {
        return $this->lien;
    }

    public function setLien(?string $lien): static
    {
        $this->lien = $lien;
        return $this;
    }

    public function getTypeRessource(): ?string
    {
        return $this->type_ressource;
    }

    public function setTypeRessource(string $type_ressource): static
    {
        $this->type_ressource = $type_ressource;

        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getDatePublication(): ?\DateTimeImmutable
    {
        return $this->date_publication;
    }

    public function setDatePublication(\DateTimeImmutable $date_publication): static
    {
        $this->date_publication = $date_publication;

        return $this;
    }

    public function isEstPrivee(): ?bool
    {
        return $this->est_privee;
    }

    public function setEstPrivee(bool $est_privee): static
    {
        $this->est_privee = $est_privee;

        return $this;
    }

    public function isEstVerifie(): ?bool
    {
        return $this->est_verifie;
    }

    public function setEstVerifie(bool $est_verifie): static
    {
        $this->est_verifie = $est_verifie;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getCreateur(): ?User
    {
        return $this->createur;
    }

    /**
     * @param User|null $createur
     */
    public function setCreateur(?User $u): void
    {
        $this->createur = $u;
    }

    /**
     * @return Category|null
     */
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * @param Category|null $category
     */
    public function setCategory(?Category $category): void
    {
        $this->category = $category;
    }

    public function getVisibilite(): string
    {
        return $this->visibilite;
    }

    public function setVisibilite(string $visibilite): static
    {
        $this->visibilite = $visibilite;
        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setResource($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getResource() === $this) {
                $comment->setResource(null);
            }
        }

        return $this;
    }
}
