<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Category;
use App\Entity\Ressource;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    public function testCollectionRessourcesInitialiseeALaConstruction(): void
    {
        $cat = new Category();
        $this->assertInstanceOf(Collection::class, $cat->getResources());
        $this->assertCount(0, $cat->getResources());
    }

    public function testAccesseurNom(): void
    {
        $cat = new Category();
        $cat->setNom('Famille');
        $this->assertSame('Famille', $cat->getNom());
    }

    public function testAccesseurDescription(): void
    {
        $cat = new Category();
        $cat->setDescription('Relations familiales');
        $this->assertSame('Relations familiales', $cat->getDescription());
    }

    public function testIdEstNullAvantPersistance(): void
    {
        $cat = new Category();
        $this->assertNull($cat->getId());
    }

    public function testBugGetLastnameExisteSurCategoryParErreur(): void
    {
        $cat = new Category();
        $this->assertTrue(
            method_exists($cat, 'getLastname'),
            'BUG: Category::getLastname() existe par erreur (devrait être supprimée)'
        );
    }

    public function testCategoriePeutEtreLieeAPlusieursRessources(): void
    {
        $cat = new Category();
        $cat->setNom('Travail');
        $cat->setDescription('Relations professionnelles');

        $r1 = new Ressource();
        $r1->setTitre('Ressource 1');
        $r1->setCategory($cat);

        $r2 = new Ressource();
        $r2->setTitre('Ressource 2');
        $r2->setCategory($cat);

        $this->assertSame('Travail', $r1->getCategory()->getNom());
        $this->assertSame('Travail', $r2->getCategory()->getNom());
    }
}
