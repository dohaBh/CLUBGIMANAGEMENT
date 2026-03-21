<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RequestStack;

final class ProfileController extends AbstractController
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    #[Route('/profile', name: 'app_profile')]
    public function index(UserRepository $userRepository): Response
    {
        $session = $this->requestStack->getSession();
        $userSession = $session->get('user');
        
        if (!$userSession) {
            $this->addFlash('error', 'Veuillez vous connecter pour accéder à votre profil.');
            return $this->redirectToRoute('app_login');
        }

        // Récupérer l'utilisateur depuis la base de données
        $user = $userRepository->find($userSession->getId());
        
        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $session = $this->requestStack->getSession();
        $userSession = $session->get('user');
        
        if (!$userSession) {
            $this->addFlash('error', 'Veuillez vous connecter pour modifier votre profil.');
            return $this->redirectToRoute('app_login');
        }

        // Récupérer l'utilisateur depuis la base de données
        $user = $userRepository->find($userSession->getId());
        
        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si le mot de passe est modifié, il faut l'encoder
            $plainPassword = $user->getPlainPassword();
            if ($plainPassword) {
                // Dans un vrai projet, utilisez un password encoder
                // $encodedPassword = $passwordEncoder->encodePassword($user, $plainPassword);
                // $user->setPassword($encodedPassword);
                $user->setPassword($plainPassword); // À remplacer par un encoder
            }

            $entityManager->flush();

            // Mettre à jour la session
            $session->set('user', $user);

            $this->addFlash('success', 'Votre profil a été mis à jour avec succès !');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}