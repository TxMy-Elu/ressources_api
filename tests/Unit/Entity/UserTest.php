<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testRoleParDefautALaConstruction(): void
    {
        $user = new User();
        $this->assertContains('ROLE_CONNECTED', $user->getRoles());
    }

    public function testRolesContiennentToujoursRoleConnected(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);
        $this->assertContains('ROLE_CONNECTED', $user->getRoles());
    }

    public function testRolesSontUniques(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_CONNECTED', 'ROLE_MODERATOR']);
        $roles = $user->getRoles();
        $this->assertSame($roles, array_unique($roles));
    }

    public function testGetUserIdentifierRetourneEmail(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $this->assertSame('test@example.com', $user->getUserIdentifier());
    }

    public function testCreatedAtDefiniALaConstruction(): void
    {
        $before = new \DateTime();
        $user   = new User();
        $after  = new \DateTime();

        $this->assertGreaterThanOrEqual($before, $user->getCreatedAt());
        $this->assertLessThanOrEqual($after, $user->getCreatedAt());
    }

    public function testIsAvaibleFauxParDefaut(): void
    {
        $user = new User();
        $this->assertFalse($user->isAvaible());
    }

    public function testAcceseursMutateurs(): void
    {
        $user = new User();
        $user->setFirstname('Jean');
        $user->setLastname('Dupont');
        $user->setEmail('jean.dupont@mail.fr');
        $user->setIsAvaible(true);

        $this->assertSame('Jean', $user->getFirstname());
        $this->assertSame('Dupont', $user->getLastname());
        $this->assertSame('jean.dupont@mail.fr', $user->getEmail());
        $this->assertTrue($user->isAvaible());
    }

    public function testDateNaissanceNullableParDefaut(): void
    {
        $user = new User();
        $this->assertNull($user->getBornDate());
    }

    public function testDateNaissancePeutEtreDefinie(): void
    {
        $user = new User();
        $date = new \DateTime('1990-06-15');
        $user->setBornDate($date);
        $this->assertSame('1990-06-15', $user->getBornDate()->format('Y-m-d'));
    }

    public function testDateActivationNullableParDefaut(): void
    {
        $user = new User();
        $this->assertNull($user->getDateActivation());
    }

    public function testMotDePassePeutEtreDefini(): void
    {
        $user = new User();
        $user->setPassword('$2y$13$hashed_password_here');
        $this->assertSame('$2y$13$hashed_password_here', $user->getPassword());
    }

    public function testRoleSuperAdminInclusTousLesRoles(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_SUPER_ADMIN']);
        $this->assertContains('ROLE_SUPER_ADMIN', $user->getRoles());
        $this->assertContains('ROLE_CONNECTED', $user->getRoles());
    }
}
