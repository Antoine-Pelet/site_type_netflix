<?php

namespace App\Controller;

use App\Entity\Rating;
use App\Entity\User;
use App\Form\UserType;
use App\Entity\Series;
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

            return $this->redirectToRoute('app_admin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/editMDP.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/user/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
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

    #[Route('/admin/{id}/ban', name: 'app_admin_ban', methods: ['GET', 'POST'])]
    public function ban(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setBan(1);

        $entityManager->flush();

        return $this->redirectToRoute('app_admin_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/admin/{id}/unban', name: 'app_admin_unban', methods: ['GET', 'POST'])]
    public function unban(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setBan(0);

        $entityManager->flush();

        return $this->redirectToRoute('app_admin_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/showRating', name: 'app_user_showRating', methods: ['GET', 'POST'])]
    public function showRates(Request $request, User $user, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {

        $stringWhere = '';

        $res = self::filtreRates($request, $entityManager, $paginator, $user, $stringWhere);

        return $this->render('user/comments.html.twig', [
            'rates' => $res['rates'],
            'ratesFiltre' => $res['ratesFiltre'],
            'years' => $res['years'],
        ]);
    }

    public static function donneStringWhere(EntityManagerInterface $entityManager, Request $request, string $stringWhere): string
    {
        $serie = "'%" . $request->query->get('serie') . "%'";
        $date = $request->query->get('date');
        $rateMin = $request->query->get('rateMin');
        $rateMax = $request->query->get('rateMax');

        $stringWhere .= ' r.user = :user';

        if ($serie != "%''%") {
            $stringWhere .= ' AND s.title LIKE ' . $serie;
        }
        if ($date != '') {
            $stringWhere .= ' AND r.date >= ' . $date;
        }
        if ($rateMin != '') {
            $stringWhere .= ' AND r.value/2 >= ' . $rateMin;
        }
        if ($rateMax != '') {
            $stringWhere .= ' AND r.value/2 <= ' . $rateMax;
        }

        return $stringWhere;
    }

    public static function filtreRates(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator, User $user, string $stringWhere)
    {

        $stringWhere .= self::donneStringWhere($entityManager, $request, $stringWhere);

        $rates = $entityManager->getRepository(Rating::class)->createQueryBuilder('r')
            ->join('r.series', 's')
            ->where('' . $stringWhere)
            ->setParameter('user', $user->getId())
            ->getQuery();

        return self::donneVariables($rates, $paginator, $request);
    }

    public static function donneVariables($rates, PaginatorInterface $paginator, Request $request)
    {
        $appointments = $paginator->paginate(
            // Doctrine Query, not results
            $rates,
            // Define the page parameter
            $request->query->getInt('page', 1),
            // Items per page
            25
        );

        $years = array();

        for ($i = 1900; $i < 2022; $i++) {
            $years[] = $i;
        }

        $ratesFiltre = array();

        for ($i = 0; $i <= 5; $i = $i + 0.5) {
            $ratesFiltre[] = $i;
        }

        $res['rates'] = $appointments;
        $res['ratesFiltre'] = $ratesFiltre;
        $res['years'] = $years;

        return $res;
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
