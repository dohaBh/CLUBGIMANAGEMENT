<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;

#[Route('/event')]
class EventController extends AbstractController
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Helper method pour obtenir l'utilisateur managé par Doctrine
     */
    private function getManagedUser(UserRepository $userRepository)
    {
        $session = $this->requestStack->getSession();
        $userSession = $session->get('user');
        
        if (!$userSession) {
            return null;
        }

        // Récupérer l'User depuis la base de données (version managée par Doctrine)
        return $userRepository->find($userSession->getId());
    }

    #[Route('/', name: 'app_event_index', methods: ['GET'])]
    public function index(EventRepository $eventRepository, UserRepository $userRepository): Response
    {
        $session = $this->requestStack->getSession();
        $userSession = $session->get('user');
        
        // Authentification obligatoire
        if (!$userSession) {
            $this->addFlash('error', 'Accès réservé aux membres connectés.');
            return $this->redirectToRoute('app_login');
        }

        $user = $this->getManagedUser($userRepository);
        
        // Récupération de tous les événements
        $events = $eventRepository->findAll();
        
        // Convertir les événements en tableau pour JSON
        $eventsData = [];
        foreach ($events as $event) {
            $eventsData[] = [
                'id' => $event->getId(),
                'titre' => $event->getTitre(),
                'theme' => $event->getTheme(),
                'enCollab' => $event->isEnCollab(),
                'partenaire' => $event->getPartenaire(),
                'programme' => $event->getProgramme(),
                'date' => $event->getDate(),
                'lieu' => $event->getLieu(),
                'statutEvent' => [
                    'value' => $event->getStatutEvent()->value
                ],
                'nbOrganisateurs' => $event->getNbOrganisateurs()
            ];
        }
        
        // Vérifier si l'utilisateur est responsable pour la vue
        $isResponsable = false;
        if ($user) {
            $isResponsable = $user->isPilotage() || in_array('ROLE_RESPONSABLE', $user->getRoles());
        }
        
        return $this->render('event/index.html.twig', [
            'events' => $eventsData,
            'isResponsable' => $isResponsable,
        ]);
    }

    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response
    {
        $user = $this->getManagedUser($userRepository);
        
        // Vérifier si l'utilisateur est responsable
        if (!$user || (!$user->isPilotage() && !in_array('ROLE_RESPONSABLE', $user->getRoles()))) {
            $this->addFlash('error', 'Accès réservé aux responsables.');
            return $this->redirectToRoute('app_event_index');
        }

        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Initialiser le nombre d'organisateurs
            if ($event->getNbOrganisateurs() === null) {
                $event->setNbOrganisateurs(1);
            }
            
            $entityManager->persist($event);
            $entityManager->flush();

            $this->addFlash('success', 'L\'événement a été créé avec succès !');

            return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event/new.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_event_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Event $event, 
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response
    {
        $user = $this->getManagedUser($userRepository);
        
        if (!$user || (!$user->isPilotage() && !in_array('ROLE_RESPONSABLE', $user->getRoles()))) {
            $this->addFlash('error', 'Accès réservé aux responsables.');
            return $this->redirectToRoute('app_event_index');
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'L\'événement a été modifié avec succès !');

            return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event/edit.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_event_delete', methods: ['GET'])]
    public function delete(
        Event $event, 
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response
    {
        $user = $this->getManagedUser($userRepository);
        
        if (!$user || (!$user->isPilotage() && !in_array('ROLE_RESPONSABLE', $user->getRoles()))) {
            $this->addFlash('error', 'Accès réservé aux responsables.');
            return $this->redirectToRoute('app_event_index');
        }

        $entityManager->remove($event);
        $entityManager->flush();

        $this->addFlash('success', 'L\'événement a été supprimé avec succès !');

        return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
    }
}