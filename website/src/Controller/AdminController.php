<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Entity\User;
use App\Form\UserType;
use App\Form\UserTypeMDP;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

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

        return $this->render('user/index.html.twig', [
            'users' => $appointments,
        ]);
    }

    #[Route('/user/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function user_edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/user/{id}/editmdp', name: 'app_user_change_mdp', methods: ['GET', 'POST'])]
    public function user_edit_mdp(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $form = $this->createForm(UserTypeMDP::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/editMDP.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/user/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/user/showSeriesLiked/{id}', name: 'app_user_show', methods: ['GET'])]
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


    #[Route('/faker', name: 'app_user_faker', methods: ['POST'])]
    public function userFaker(EntityManagerInterface $entityManager)
    {
        $faker = Factory::create();
        
        $em = $entityManager;
        if (isset($_POST['usergen'])) {
            $numUsers = $_POST['usergen'];
            $users = array();
            for ($i = 0; $i < $numUsers; $i++) {
                $user = new User();
                $tmpname = $faker->userName;
                $user->setName($tmpname);
                $user->setEmail($tmpname . $i . '@testwatchlist.fr');
                $user->setPassword($faker->password(6, 7));
                $users[] = $user;
                $em->persist($user);
            }
            $em->flush();
        }
        return $this->redirectToRoute('app_admin_index');
    }

    #[Route('/user/{id}/follow', name: 'app_user_like', methods: ['GET'])]
    public function like(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User */
        $user = $this->getUser();

        $targetUser = $entityManager->getRepository(User::class)->find($request->get('id'));

        $user->addUser($targetUser);

        $entityManager->flush();

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/user/{id}/unfollow', name: 'app_user_dislike', methods: ['GET'])]
    public function dislike(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User */
        $user = $this->getUser();

        $targetUser = $entityManager->getRepository(User::class)->find($request->get('id'));

        $user->removeUser($targetUser);

        $entityManager->flush();

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
