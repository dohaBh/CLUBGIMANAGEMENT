<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Cellule;
use App\Enum\Niveau;
use App\Enum\Statut;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;
    
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }
    
    public function load(ObjectManager $manager): void
    {
        $plainPassword = 'test123'; // Mot de passe pour tous les utilisateurs de test
        
        // --- Création des responsables ---
        $responsable = $this->createUser(
            $manager,
            'bouchikhidoha2@gmail.com',
            $plainPassword,
            'Bouchikhi',
            'Doha',
            ['ROLE_RESPONSABLE', 'ROLE_USER'],
            Niveau::L2
        );
        
        $responsable1 = $this->createUser(
            $manager,
            'responsable1@club.com',
            $plainPassword,
            'Bouchikhi1',
            'Doha1',
            ['ROLE_RESPONSABLE', 'ROLE_USER'],
            Niveau::L2
        );
        
        $responsable2 = $this->createUser(
            $manager,
            'responsable2@club.com',
            $plainPassword,
            'Respo2',
            'Doha2',
            ['ROLE_RESPONSABLE', 'ROLE_USER'],
            Niveau::L3
        );
        
        // --- Création des cellules ---
        $cellule1 = new Cellule();
        $cellule1->setNom('Communication');
        $cellule1->setDescription('Cellule chargée de la communication interne et externe');
        $cellule1->setResponsable($responsable1);
        
        $cellule2 = new Cellule();
        $cellule2->setNom('Sponsoring');
        $cellule2->setDescription('Cellule chargée du financement du club');
        $cellule2->setResponsable($responsable);
        
        $cellule3 = new Cellule();
        $cellule3->setNom('Design');
        $cellule3->setDescription('Cellule chargée du visuel de la page');
        $cellule3->setResponsable($responsable2);
        
        // Lier les responsables
        $responsable->setCellule($cellule2);
        $responsable1->setCellule($cellule1);
        $responsable2->setCellule($cellule3);
        
        $manager->persist($cellule1);
        $manager->persist($cellule2);
        $manager->persist($cellule3);
        $manager->flush();
        
        // Mettre à jour le nombre de membres
        $cellule1->setNbMembres(1);
        $cellule2->setNbMembres(1);
        $cellule3->setNbMembres(1);
        
        // --- Création des membres pour chaque cellule ---
        
        // Cellule 1 : Communication
        for ($i = 1; $i <= 3; $i++) {
            $membre = $this->createUser(
                $manager,
                "membre{$i}@club.com",
                $plainPassword,
                "Nom{$i}",
                "Prenom{$i}",
                ['ROLE_USER'],
                Niveau::L1,
                "06111111{$i}"
            );
            $membre->setCellule($cellule1);
            $cellule1->setNbMembres($cellule1->getNbMembres() + 1);
        }
        
        // Cellule 2 : Sponsoring
        for ($i = 4; $i <= 6; $i++) {
            $membre = $this->createUser(
                $manager,
                "membre.sponsoring{$i}@club.com",
                $plainPassword,
                "Sponsor{$i}",
                "PrenomSponsor{$i}",
                ['ROLE_USER'],
                Niveau::L2,
                "06222222{$i}"
            );
            $membre->setCellule($cellule2);
            $cellule2->setNbMembres($cellule2->getNbMembres() + 1);
        }
        
        // Cellule 3 : Design
        for ($i = 7; $i <= 9; $i++) {
            $membre = $this->createUser(
                $manager,
                "membre.design{$i}@club.com",
                $plainPassword,
                "Design{$i}",
                "PrenomDesign{$i}",
                ['ROLE_USER'],
                Niveau::L3,
                "06333333{$i}"
            );
            $membre->setCellule($cellule3);
            $cellule3->setNbMembres($cellule3->getNbMembres() + 1);
        }
        
        // --- Utilisateurs sans cellule ---
        for ($i = 1; $i <= 3; $i++) {
            $this->createUser(
                $manager,
                "sans.cellule{$i}@club.com",
                $plainPassword,
                "SansCellule{$i}",
                "User{$i}",
                ['ROLE_USER'],
                Niveau::L1,
                "06444444{$i}"
            );
        }
        
        $manager->flush();
    }
    
    private function createUser(
        ObjectManager $manager,
        string $email,
        string $plainPassword,
        string $nom,
        string $prenom,
        array $roles,
        Niveau $niveau,
        string $telephone = '0600000000'
    ): User {
        $user = new User();
        $user->setEmail($email);
        
        // Hash le mot de passe correctement
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);
        
        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setRoles($roles);
        $user->setNiveau($niveau);
        $user->setStatut(Statut::ACTIF);
        $user->setTelephone($telephone);
        $user->setDateInscription(new \DateTime());
        $user->setIsPilotage(in_array('ROLE_RESPONSABLE', $roles));
        $user->setIsVerified(true); // IMPORTANT : marqué comme vérifié
        
        $manager->persist($user);
        
        return $user;
    }
}