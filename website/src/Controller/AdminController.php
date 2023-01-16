<?php

namespace App\Controller;

use Faker\Factory;
use App\Entity\User;
use App\Entity\Rating;
use App\Form\UserType;
use App\Entity\Series;
use App\Form\UserTypeMDP;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Class AdminController
 * @package App\Controller
 * 
 * This controller handle the routes for the admin panel, it handle the listing of user, edition of user and change password of a user.
 * 
 */
class AdminController extends AbstractController
{
    /**
     * Handle the route '/admin' to list the user
     * @Route("/admin", name="app_admin_index", methods={"GET"})
     * 
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param PaginatorInterface $paginator
     * 
     * @return Response
     */
    #[Route('/admin', name: 'app_admin_index', methods: ['GET'])]
    public function admin_panel(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        $users = $entityManager->getRepository(User::class);

        $users = $users->createQueryBuilder('u')
            ->orderBy('u.registerDate', 'DESC')
            ->where('u.email LIKE :search')
            ->setParameter('search', '%' . $request->query->get('email') . '%')
            ->getQuery();

        $appointments = $paginator->paginate($users, $request->query->getInt('page', 1), 10);

        return $this->render('admin/index.html.twig', [
            'users' => $appointments,
        ]);
    }

    /**
     * Handle the route '/user' to list the user
     * @Route("/user", name="app_user_index", methods={"GET"})
     *
     * @param Request $request
     * @param EntitiesManagerInterface $entityManager
     * @param PaginatorInterface $paginator
     *
     * @return Response
     */
    #[Route('/user', name: 'app_user_index', methods: ['GET'])]
    public function user_panel(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        $users = $entityManager->getRepository(User::class);

        $users = $users->createQueryBuilder('u')
            ->orderBy('u.registerDate', 'DESC')
            ->where('u.email LIKE :search')
            ->setParameter('search', '%' . $request->query->get('email') . '%')
            ->getQuery();

        $appointments = $paginator->paginate($users, $request->query->getInt('page', 1), 10);

        return $this->render('user/index.html.twig', [
            'users' => $appointments,
        ]);
    }

    #[Route('/user/edit/{id}', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function user_edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

    #[Route('/user/editmdp/{id}', name: 'app_user_change_mdp', methods: ['GET', 'POST'])]
    public function user_edit_mdp(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $form = $this->createForm(UserTypeMDP::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->flush();

            return $this->redirectToRoute('app_admin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/editMDP.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/admin/promouvoir/{id}', name: 'app_admin_promouvoir', methods: ['GET', 'POST'])]
    public function promouvoirUser(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setAdmin(1);

        $entityManager->flush();

        return $this->redirectToRoute('app_admin_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/admin/destituer/{id}', name: 'app_admin_destituer', methods: ['GET', 'POST'])]
    public function destituerUser(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setAdmin(0);

        $entityManager->flush();

        return $this->redirectToRoute('app_admin_index', [], Response::HTTP_SEE_OTHER);
    }


    #[Route('/admin/ban/{id}', name: 'app_admin_ban', methods: ['GET', 'POST'])]
    public function banUser(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setBan(1);

        $entityManager->flush();

        return $this->redirectToRoute('app_admin_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/admin/unban/{id}', name: 'app_admin_unban', methods: ['GET', 'POST'])]
    public function unbanUser(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setBan(0);

        $entityManager->flush();

        return $this->redirectToRoute('app_admin_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/user/profile/{id}', name: 'app_show_user_profile', methods: ['GET', 'POST'])]
    public function showUserProfile(User $user, EntityManagerInterface $entityManager): Response
    {
        $series = $user->getSeries();

        $rates = $entityManager->getRepository(Rating::class)->createQueryBuilder('r')
            ->where('r.user = :user')
            ->setParameter('user', $user->getId())
            ->getQuery()
            ->getResult();

        $entityManager->flush();

        return $this->render('user/user_profile.html.twig', [
            'rates' => $rates,
            'series' => $series
        ]);
    }

    #[Route('/user/follow/{id}', name: 'app_user_like', methods: ['GET'])]
    public function userLike(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User */
        $user = $this->getUser();

        $targetUser = $entityManager->getRepository(User::class)->find($request->get('id'));

        $user->addUser($targetUser);

        $entityManager->flush();

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/user/unfollow/{id}', name: 'app_user_dislike', methods: ['GET'])]
    public function userDislike(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User */
        $user = $this->getUser();

        $targetUser = $entityManager->getRepository(User::class)->find($request->get('id'));

        $user->removeUser($targetUser);

        $entityManager->flush();

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
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

    #[Route('/rategen', name: 'app_rate_gen', methods: ['POST'])]
    public function rateGen(Request $request, EntityManagerInterface $entityManager)
    {
        $faker = Factory::create();
        $em = $entityManager;
        $users = $em->getRepository(User::class)->findAll();
        $seriesIds = range(1, 234);
        foreach ($users as $u) {
            if (substr($u->getEmail(), -17) != '@testwatchlist.fr') {
                continue;
            }
            $tempSeriesIds = $seriesIds;
            shuffle($tempSeriesIds);
            $tempSeriesIds = array_slice($tempSeriesIds, 0, rand(1, 10));
            foreach ($tempSeriesIds as $id) {
                $series = $em->getRepository(Series::class)->findOneBy(['id' => $id]);
                if (!$series) continue;
                $rating = new Rating();
                $rating->setSeries($series);
                $rating->setUser($u);
                $rating->setValue(rand(0, 10));
                $rating->setComment($faker->text(200));
                $series->addRating($rating);
                $ratings[] = $rating;
                $em->persist($rating);
                $u->addSeries($series);
            }
            $em->flush();
        }
        return $this->redirectToRoute('app_admin_index');
    }
}
