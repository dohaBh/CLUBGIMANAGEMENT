<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UserRepository;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;


final class DashboardController extends AbstractController
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        $session = $this->requestStack->getSession();
        $userSession = $session->get('user');
        
        // Authentification obligatoire
        if (!$userSession) {
            $this->addFlash('error', 'Accès réservé aux membres connectés.');
            return $this->redirectToRoute('app_login');
        }
        return $this->render('dashboard/index.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }
}
