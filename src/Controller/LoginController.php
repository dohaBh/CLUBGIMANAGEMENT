<?php

namespace App\Controller;

use App\Form\LoginFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use App\Enum\Statut;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(
        Request $request, 
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        // Si l'utilisateur est déjà connecté
        if ($this->getUser()) {
            $this->addFlash('info', 'Vous êtes déjà connecté.');
            return $this->redirectToRoute('app_dashboard');
        }
        
        // Créer le formulaire
        $form = $this->createForm(LoginFormType::class);
        $form->handleRequest($request);
        
        // Si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $email = $data['email'];
            $password = $data['password'];
            
            // Rechercher l'utilisateur
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            
            if (!$user) {
                $this->addFlash('error', 'Email ou mot de passe incorrect.');
                return $this->redirectToRoute('app_login');
            }
            
            // Vérifier si l'email est vérifié
            if (!$user->isVerified()) {
                $this->addFlash('error', 'Veuillez vérifier votre email avant de vous connecter. Vérifiez votre boîte mail.');
                return $this->redirectToRoute('app_login');
            }
            
            // ⭐⭐ AJOUTÉ : Vérifier le statut ACTIF
            if ($user->getStatut() !== Statut::ACTIF) {
                $this->addFlash('error', 
                    'Votre compte est en attente d\'approbation par le pilotage.'
                );
                return $this->redirectToRoute('app_login');
            }
            
            // Vérifier le mot de passe
            if (!$passwordHasher->isPasswordValid($user, $password)) {
                $this->addFlash('error', 'Email ou mot de passe incorrect.');
                return $this->redirectToRoute('app_login');
            }
            
            // Stocker l'utilisateur dans la session
            $session = $request->getSession();
            $session->set('user_id', $user->getId());
            $session->set('user_email', $user->getEmail());
            $session->set('user', $user);
            
            $this->addFlash('success', 'Connexion réussie ! Bienvenue ' . $user->getPrenom());
            
            // Rediriger vers la page d'accueil
            return $this->redirectToRoute('app_dashboard');
        }
        
        // Afficher les erreurs de validation du formulaire
        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
        }
        
        return $this->render('auth/login.html.twig', [
            'loginForm' => $form->createView(),
        ]);
    }
    
    #[Route('/logout', name: 'app_logout')]
    public function logout(Request $request): Response
    {
        $session = $request->getSession();
        $session->clear();
        
        $this->addFlash('success', 'Vous avez été déconnecté avec succès.');
        return $this->redirectToRoute('app_login');
    }
}