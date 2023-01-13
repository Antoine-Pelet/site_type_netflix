<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Knp\Component\Pager\PaginatorInterface;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {

        $users = $entityManager->getRepository(User::class);

        $users = $users->createQueryBuilder('u')
        ->orderBy('u.registerDate', 'DESC')
        ->where('u.email LIKE :search')
        ->setParameter('search', '%' . $request->query->get('email') . '%')
        ->getQuery();

        $appointments = $paginator->paginate(
            // Doctrine Query, not results
            $users,
            // Define the page parameter
            $request->query->getInt('page', 1),
            // Items per page
            10
        );

        return $this->render('admin/index.html.twig', [
            'users' => $appointments,
        ]);
    }

    #[Route('/user', name: 'app_user_index', methods: ['GET'])]
    public function user(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        $users = $entityManager->getRepository(User::class);

        $users = $users->createQueryBuilder('u')
        ->orderBy('u.registerDate', 'DESC')
        ->where('u.name LIKE :search')
        ->setParameter('search', '%' . $request->query->get('name') . '%')
        ->getQuery();
        
        $appointments = $paginator->paginate(
            // Doctrine Query, not results
            $users,
            // Define the page parameter
            $request->query->getInt('page', 1),
            // Items per page
            10
        );

        return $this->render('user/index.html.twig', [
            'users' => $appointments,
        ]);
    }

    #[Route('/user/showSeriesViewed/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(Request $request, EntityManagerInterface $entityManager, User $user): Response
    {    
        $series = $user->getSeries();

        return $this->render('user/show.html.twig', [
            'users' => $user,
            'series' => $series
        ]);
    }

    #[Route('/admin/{id}/edit', name: 'app_admin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {

        $user->setAdmin(1);

        $entityManager->flush();

        return $this->redirectToRoute('app_admin_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/remove', name: 'app_admin_remove', methods: ['GET', 'POST'])]
    public function edit2(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {

        $user->setAdmin(0);

        $entityManager->flush();

        return $this->redirectToRoute('app_admin_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/showRating', name: 'app_user_showRating', methods: ['GET', 'POST'])]
    public function showRates(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $rates = $entityManager->getRepository(Rating::class)->createQueryBuilder('r')
        ->where('r.user = :user')
        ->setParameter('user', $user->getId())
        ->getQuery()
        ->getResult();

        $entityManager->flush();

        return $this->render('user/comments.html.twig', [
            'rates' => $rates,
        ]);
    }
}
