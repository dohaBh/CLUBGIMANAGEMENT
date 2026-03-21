<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\DemandeAdhesion;
use App\Enum\Statut;
use App\Enum\StatutDmd;
use App\Form\RegistrationFormType;
use App\Repository\CelluleRepository;
use App\Service\SimpleEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    private LoggerInterface $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request, 
        EntityManagerInterface $em,
        CelluleRepository $celluleRepository,
        SimpleEmailService $emailService,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier si l'email existe déjà
            $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
            
            if ($existingUser) {
                $this->addFlash('error', 'Cet email est déjà utilisé.');
                return $this->redirectToRoute('app_register');
            }
            
            // Récupérer le mot de passe en clair
            $plainPassword = $form->get('plainPassword')->getData();
            
            // Hacher le mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
            
            // Définir les valeurs par défaut
            $user->setStatut(Statut::EN_ATTENTE);
            $user->setDateInscription(new \DateTime());
            $user->setIsPilotage(false);
            $user->setIsVerified(false);
            
            // Générer un token de vérification
            $verificationToken = bin2hex(random_bytes(32));
            $user->setVerificationToken($verificationToken);
            
            // // LOGS DEBUG
            // $this->logger->info('=== DEBUG INSCRIPTION ===');
            // $this->logger->info('Email utilisateur: ' . $user->getEmail());
            // $this->logger->info('Token généré: ' . $verificationToken);
            // $this->logger->info('Longueur token: ' . strlen($verificationToken));
            // $this->logger->info('Token dans user object: ' . $user->getVerificationToken());
            
            // Créer la demande d'adhésion
            $demande = new DemandeAdhesion();
            $demande->setUser($user);
            $demande->setCellule($user->getCellule());
            $demande->setDateDemande(new \DateTime());
            $demande->setMotivation("Nouvelle inscription");
            $demande->setStatutDmd(StatutDmd::EN_ATTENTE);
            
            // Persist les deux
            $em->persist($user);
            $em->persist($demande);
            $em->flush();
            
            // // LOG après flush
            // $this->logger->info('=== APRÈS FLUSH ===');
            // $this->logger->info('Token toujours présent: ' . ($user->getVerificationToken() ? 'OUI' : 'NON'));
            
            // // LOG avant envoi email
            // $this->logger->info('=== AVANT ENVOI EMAIL ===');
            // $this->logger->info('Token passé au service: ' . $verificationToken);
            

            // 1. Envoyez l'email de vérification à l'utilisateur
            $emailService->sendVerificationEmail(
                $user->getEmail(),  // L'email de l'utilisateur qui s'inscrit
                $verificationToken,
                $user->getPrenom() . ' ' . $user->getNom()
            );
            
            // 2. Redirigez vers la page d'attente
            $this->addFlash('success', 
                'Inscription réussie ! Un email de vérification a été envoyé à ' . $user->getEmail()
            );
            
            return $this->redirectToRoute('app_verification_pending');
        }

        return $this->render('auth/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify-email/{token}', name: 'app_verify_email')]
    public function verifyEmail(string $token, EntityManagerInterface $em): Response
{
    $this->logger->info('=== VERIFY EMAIL DEBUG ===');
    $this->logger->info('Token reçu dans URL: ' . $token);
    $this->logger->info('Longueur token URL: ' . strlen($token));
    
    // 1. Cherchez l'utilisateur par token
    $user = $em->getRepository(User::class)->findOneBy(['verificationToken' => $token]);
    
    if (!$user) {
        // Le token n'existe plus, vérifiez si l'utilisateur est déjà vérifié
        // $this->logger->info('Token non trouvé, vérification si déjà vérifié...');
        
        // Cherchez TOUS les utilisateurs pour debug
        $allUsers = $em->getRepository(User::class)->findAll();
        $this->logger->info('Tous les utilisateurs:');
        foreach ($allUsers as $u) {
            $this->logger->info(' - ' . $u->getEmail() . ' : token=' . ($u->getVerificationToken() ?: 'NULL') . ', is_verified=' . ($u->isVerified() ? 'Oui' : 'Non'));
        }
        
        // Redirigez vers login avec un message approprié
        $this->addFlash('info', 'Ce lien de vérification a déjà été utilisé. Votre compte est peut-être déjà vérifié.');
        return $this->redirectToRoute('app_login');
    }
    
    $this->logger->info('Utilisateur trouvé: ' . $user->getEmail());
    $this->logger->info('is_verified avant: ' . ($user->isVerified() ? 'Oui' : 'Non'));
    
    if ($user->isVerified()) {
        // Déjà vérifié, redirigez simplement
        $this->addFlash('info', 'Votre compte est déjà vérifié.');
        return $this->redirectToRoute('app_login');
    }
    
    // Marquer comme vérifié et supprimer le token
    $user->setIsVerified(true);
    $user->setVerificationToken(null);
    $em->flush();
    
    // $this->logger->info('=== APRÈS VÉRIFICATION ===');
    // $this->logger->info('is_verified après: ' . ($user->isVerified() ? 'Oui' : 'Non'));
    // $this->logger->info('Token après: ' . ($user->getVerificationToken() ?: 'NULL'));
    
    $this->addFlash('success', 
        'Email vérifié ! Votre compte est en attente d\'approbation par le pilotage.'
    );
    
    return $this->redirectToRoute('app_login');
}

    #[Route('/verification-pending', name: 'app_verification_pending')]
    public function verificationPending(): Response
    {
        return $this->render('auth/verification_pending.html.twig');
    }
}