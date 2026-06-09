<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Ressource;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        // Catégories
        $cats = ['Famille', 'Couple', 'Travail', 'Amis', 'Développement personnel'];
        $categories = [];
        foreach ($cats as $name) {
            $cat = new Category();
            $cat->setNom($name);
            $cat->setDescription("Ressources liées à : $name");
            $manager->persist($cat);
            $categories[] = $cat;
        }

        // Super Admin
        $admin = new User();
        $admin->setEmail('admin@resources.fr');
        $admin->setLastname('Admin');
        $admin->setFirstname('Super');
        $admin->setPassword($this->hasher->hashPassword($admin, 'Admin1234!'));
        $admin->setRoles(['ROLE_SUPER_ADMIN']);
        $admin->setIsAvaible(true);
        $manager->persist($admin);

        // Modérateur
        $modo = new User();
        $modo->setEmail('moderateur@resources.fr');
        $modo->setLastname('Moderateur');
        $modo->setFirstname('Jean');
        $modo->setPassword($this->hasher->hashPassword($modo, 'Modo1234!'));
        $modo->setRoles(['ROLE_MODERATOR']);
        $modo->setIsAvaible(true);
        $manager->persist($modo);

        // Citoyen
        $user = new User();
        $user->setEmail('citoyen@resources.fr');
        $user->setLastname('Dupont');
        $user->setFirstname('Jean');
        $user->setPassword($this->hasher->hashPassword($user, 'User1234!'));
        $user->setRoles(['ROLE_USER']);
        $user->setIsAvaible(true);
        $manager->persist($user);

        // Ressource de test
        $resource = new Ressource();
        $resource->setTitre('Comment améliorer sa communication en couple');
        $resource->setDescription('Guide pratique pour mieux communiquer.');
        $resource->setContenu('Lorem ipsum — contenu complet de la ressource...');
        $resource->setTypeRessource('article');
        $resource->setStatut(Ressource::STATUS_PUBLISHED);
        $resource->setVisibilite(Ressource::VISIBILITE_PUBLIC);
        $resource->setCreateur($admin);
        $resource->setCategory($categories[1]);
        $resource->setEstPrivee(false);
        $resource->setEstVerifie(true);
        $resource->setCreatedAt(new \DateTimeImmutable());
        $resource->setDatePublication(new \DateTimeImmutable());
        $manager->persist($resource);

        $manager->flush();
    }
}
