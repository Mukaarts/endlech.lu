<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const REFERENCE_ADMIN = 'user-admin';
    public const REFERENCE_USER = 'user-test';
    public const REFERENCE_UNVERIFIED = 'user-unverified';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $users = [
            [
                'name'       => 'Admin User',
                'email'      => 'admin@endlech.lu',
                'roles'      => ['ROLE_ADMIN'],
                'password'   => 'admin123',
                'isVerified' => true,
                'reference'  => self::REFERENCE_ADMIN,
            ],
            [
                'name'       => 'Test User',
                'email'      => 'user@endlech.lu',
                'roles'      => [],
                'password'   => 'user123',
                'isVerified' => true,
                'reference'  => self::REFERENCE_USER,
            ],
            [
                'name'       => 'Unverified User',
                'email'      => 'unverified@endlech.lu',
                'roles'      => [],
                'password'   => 'unverified123',
                'isVerified' => false,
                'reference'  => self::REFERENCE_UNVERIFIED,
            ],
        ];

        foreach ($users as $data) {
            $user = new User();
            $user->setName($data['name']);
            $user->setEmail($data['email']);
            $user->setRoles($data['roles']);
            $user->setIsVerified($data['isVerified']);

            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);

            $manager->persist($user);
            $this->addReference($data['reference'], $user);
        }

        $manager->flush();
    }
}
