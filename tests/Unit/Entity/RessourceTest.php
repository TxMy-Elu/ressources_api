<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Category;
use App\Entity\Ressource;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class RessourceTest extends TestCase
{
    public function testStatutParDefautEstBrouillon(): void
    {
        $r = new Ressource();
        $this->assertSame(Ressource::STATUS_DRAFT, $r->getStatut());
    }

    public function testVisibiliteParDefautEstPublie(): void
    {
        $r = new Ressource();
        $this->assertSame(Ressource::VISIBILITE_PUBLIC, $r->getVisibilite());
    }

    public function testConstantesStatutOntLesValeursAttendues(): void
    {
        $this->assertSame('brouillon', Ressource::STATUS_DRAFT);
        $this->assertSame('en attente', Ressource::STATUS_PENDING);
        $this->assertSame('publie', Ressource::STATUS_PUBLISHED);
        $this->assertSame('suspendu', Ressource::STATUS_SUSPENDED);
    }

    public function testConstantesVisibiliteOntLesValeursAttendues(): void
    {
        $this->assertSame('private', Ressource::VISIBILITE_PRIVATE);
        $this->assertSame('partage', Ressource::VISIBILITE_SHARED);
        $this->assertSame('publie', Ressource::VISIBILITE_PUBLIC);
    }

    public function testCreatedAtDefiniALaConstruction(): void
    {
        $before = new \DateTimeImmutable();
        $r      = new Ressource();
        $after  = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $r->getCreatedAt());
        $this->assertLessThanOrEqual($after, $r->getCreatedAt());
    }

    public function testAcceseursMutateurs(): void
    {
        $r = new Ressource();
        $r->setTitre('Test resource');
        $r->setDescription('Une description');
        $r->setContenu('Contenu complet');
        $r->setTypeRessource('article');
        $r->setStatut(Ressource::STATUS_PENDING);
        $r->setVisibilite(Ressource::VISIBILITE_SHARED);
        $r->setEstPrivee(false);
        $r->setEstVerifie(false);

        $this->assertSame('Test resource', $r->getTitre());
        $this->assertSame('Une description', $r->getDescription());
        $this->assertSame('Contenu complet', $r->getContenu());
        $this->assertSame('article', $r->getTypeRessource());
        $this->assertSame(Ressource::STATUS_PENDING, $r->getStatut());
        $this->assertSame(Ressource::VISIBILITE_SHARED, $r->getVisibilite());
        $this->assertFalse($r->isEstPrivee());
        $this->assertFalse($r->isEstVerifie());
    }

    public function testCreateurPeutEtreAssigne(): void
    {
        $user = new User();
        $user->setEmail('user@test.fr');

        $r = new Ressource();
        $r->setCreateur($user);

        $this->assertSame($user, $r->getCreateur());
    }

    public function testCategoriePeutEtreAssignee(): void
    {
        $cat = new Category();
        $cat->setNom('Famille');
        $cat->setDescription('Relations familiales');

        $r = new Ressource();
        $r->setCategory($cat);

        $this->assertSame($cat, $r->getCategory());
        $this->assertSame('Famille', $r->getCategory()->getNom());
    }

    public function testCategorieAccepteNull(): void
    {
        $r = new Ressource();
        $r->setCategory(null);
        $this->assertNull($r->getCategory());
    }

    public function testMediaNullableParDefaut(): void
    {
        $r = new Ressource();
        $this->assertNull($r->getMedia());
    }

    public function testTransitionsStatutRessourcePublique(): void
    {
        $r = new Ressource();
        $r->setVisibilite(Ressource::VISIBILITE_PUBLIC);

        if ($r->getVisibilite() === Ressource::VISIBILITE_PUBLIC) {
            $r->setStatut(Ressource::STATUS_PENDING);
        }
        $this->assertSame(Ressource::STATUS_PENDING, $r->getStatut());

        $r->setStatut(Ressource::STATUS_PUBLISHED);
        $this->assertSame(Ressource::STATUS_PUBLISHED, $r->getStatut());
    }

    public function testRessourcePriveeResteBrouillon(): void
    {
        $r = new Ressource();
        $r->setVisibilite(Ressource::VISIBILITE_PRIVATE);

        if ($r->getVisibilite() === Ressource::VISIBILITE_PUBLIC) {
            $r->setStatut(Ressource::STATUS_PENDING);
        } else {
            $r->setStatut(Ressource::STATUS_DRAFT);
        }

        $this->assertSame(Ressource::STATUS_DRAFT, $r->getStatut());
    }
}
