<?php

namespace App\Controller;

use Faker\Factory;
use App\Entity\User;
use App\Entity\Rating;
use App\Form\UserType;
use App\Entity\Series;
use App\Entity\Country;
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
 * This controller handle the routes for the admin panel,
 * it handle the listing of user, edition of user and change password of a user.
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
    public function adminPanel(
        Request $request,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator
    ): Response {

        $stringWhere = self::createStringWhere($request);

        $users = self::createSQLRequete($entityManager, $stringWhere, $request, $this->getUser());

        $country = $entityManager->getRepository(Country::class)->findAll();

        /** @var App\Entity\User */
        $user = $this->getUser();

        if (!$user->isAdmin()) {
            return $this->redirectToRoute('app_default', [], Response::HTTP_SEE_OTHER);
        }

        $appointments = $paginator->paginate($users, $request->query->getInt('page', 1), 10);

        return $this->render('admin/index.html.twig', [
            'users' => $appointments,
            'countries' => $country,
        ]);
    }

    public static function createSQLRequete(EntityManagerInterface $entityManager, string $stringWhere, Request $request, User $user)
    {
        $users = $entityManager->getRepository(User::class);

        if ($request->query->get('follow') != '') {
            $users = $users->createQueryBuilder('u')
                ->join('u.users', 'us')
                ->where($stringWhere)
                ->setParameter('user', $user->getUsers())
                ->getQuery();
        } else {
            $users = $users->createQueryBuilder('u')
                ->where($stringWhere)
                ->getQuery();
        }


        return $users;
    }

    public static function createStringWhere(Request $request): string
    {
        $stringWhere = '1 = 1';

        $mail = "'%" . $request->query->get('email') . "%'";
        $nom = "'%" . $request->query->get('nom') . "%'";
        $ban = $request->query->get('ban');
        $admin = $request->query->get('admin');
        $country = $request->query->get('country');

        $follow = $request->query->get('follow');

        if ($mail != "'%%'") {
            $stringWhere .= " AND u.email LIKE " . $mail;
        }
        if ($nom != "'%%'") {
            $stringWhere .= " AND u.name LIKE " . $nom;
        }
        if ($ban != '') {
            $stringWhere .= " AND u.ban = " . $ban;
        }
        if ($follow != '') {
            if ($follow == '1') {
                $stringWhere .= " AND u.id IN (:user)";
            } else {
                $stringWhere .= " AND u.id NOT IN (:user)";
            }
        }
        if ($country != '') {
            $stringWhere .= " AND u.country = " . $country;
        }
        if ($admin != '') {
            $stringWhere .= " AND u.admin = " . $admin;
        }

        return $stringWhere;
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
    public function userPanel(
        Request $request,
        EntityManagerInterface $entityManage,
        PaginatorInterface $paginator
    ): Response {

        $stringWhere = self::createStringWhere($request);

        $users = self::createSQLRequete($entityManage, $stringWhere, $request, $this->getUser());

        $country = $entityManage->getRepository(Country::class)->findAll();

        $appointments = $paginator->paginate($users, $request->query->getInt('page', 1), 10);

        return $this->render('user/index.html.twig', [
            'users' => $appointments,
            'countries' => $country,
        ]);
    }

    #[Route('/user/edit/{id}', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function userEdit(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $userPasswordHasher
    ): Response {
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
    public function userEditMdp(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $userPasswordHasher
    ): Response {
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
    public function promouvoirUser(User $user, EntityManagerInterface $entityManager, Request $request): Response
    {
        $user->setAdmin(1);

        $entityManager->flush();

        $page = $request->query->get('page');

        return $this->redirectToRoute('app_admin_index', ['page' => $page], Response::HTTP_SEE_OTHER);
    }

    #[Route('/admin/destituer/{id}', name: 'app_admin_destituer', methods: ['GET', 'POST'])]
    public function destituerUser(User $user, EntityManagerInterface $entityManager, Request $request): Response
    {
        $user->setAdmin(0);

        $entityManager->flush();

        $page = $request->query->get('page');

        return $this->redirectToRoute('app_admin_index', ['page' => $page], Response::HTTP_SEE_OTHER);
    }


    #[Route('/admin/ban/{id}', name: 'app_admin_ban', methods: ['GET', 'POST'])]
    public function banUser(User $user, EntityManagerInterface $entityManager, Request $request): Response
    {
        $user->setBan(1);

        $entityManager->flush();

        $page = $request->query->get('page');

        return $this->redirectToRoute('app_admin_index', ['page' => $page], Response::HTTP_SEE_OTHER);
    }

    #[Route('/admin/unban/{id}', name: 'app_admin_unban', methods: ['GET', 'POST'])]
    public function unbanUser(User $user, EntityManagerInterface $entityManager, Request $request): Response
    {
        $user->setBan(0);

        $entityManager->flush();

        $page = $request->query->get('page');

        return $this->redirectToRoute('app_admin_index', ['page' => $page], Response::HTTP_SEE_OTHER);
    }

    #[Route('/user/profile/{id}', name: 'app_show_user_profile', methods: ['GET', 'POST'])]
    public function showUserProfile(User $user, EntityManagerInterface $entityManager, PaginatorInterface $paginator, Request $request): Response
    {

        $stringWhere = SeriesController::stringWhere($request);

        $res = SeriesController::getEpisodeVu($entityManager, $stringWhere, $user);

        $result = SeriesController::requeteFiltred($res['seriesView'], $entityManager, $request, $paginator, 15);

        $stringRates = self::donneStringWhere($request, 'r.user = :user');

        $rates = $entityManager->getRepository(Rating::class)->createQueryBuilder('r')
            ->join('r.series', 's')
            ->where('' . $stringRates)
            ->setParameter('user', $user->getId())
            ->getQuery()
            ->getResult();

        $entityManager->flush();

        return $this->render('user/user_profile.html.twig', [
            'rates' => $rates,
            'series' => $result['series'],
            'genres' => $result['genres'],
            'years' => $result['years'],
            'ratesFiltre' => $result['rates'],
            'user' => $user
        ]);
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

    public static function donneStringWhere(Request $request, string $stringWhere): string
    {
        if ($stringWhere == '') {
            $stringWhere .= '1=1';
        }
        $serie = "'%" . $request->query->get('serie') . "%'";
        $date = $request->query->get('date');
        $rateMin = $request->query->get('rateMin');
        $rateMax = $request->query->get('rateMax');

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
        $stringWhere .= ' r.user = :user';

        $stringWhere .= self::donneStringWhere($request, $stringWhere);

        $rates = $entityManager->getRepository(Rating::class)->createQueryBuilder('r')
            ->join('r.series', 's')
            ->where('' . $stringWhere)
            ->setParameter('user', $user->getId())
            ->getQuery();

        return self::donneVariables($rates, $paginator, $request, 8);
    }

    #[Route('/user/follow/{id}', name: 'app_user_like', methods: ['GET'])]
    public function userLike(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User */
        $user = $this->getUser();

        $targetUser = $entityManager->getRepository(User::class)->find($request->get('id'));

        $user->addUser($targetUser);

        $entityManager->flush();

        $page = $request->query->get('page');

        return $this->redirectToRoute('app_user_index', ['page' => $page], Response::HTTP_SEE_OTHER);
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
    public static function donneVariables($rates, PaginatorInterface $paginator, Request $request, int $nb)
    {
        $appointments = $paginator->paginate(
            // Doctrine Query, not results
            $rates,
            // Define the page parameter
            $request->query->getInt('page', 1),
            // Items per page
            $nb
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
    public function userFaker(Request $request, EntityManagerInterface $entityManager)
    {
        $faker = Factory::create();
        $em = $entityManager;
        $numUsers = $request->request->get('usergen');

        $users = array();
        $batchSize = 1000; // adjust as needed

        for ($i = 0; $i < $numUsers; $i++) {
            $user = new User();
            $tmpname = $faker->userName;
            $user->setName($tmpname);
            $user->setEmail($tmpname . $i . '@genwatchlist.fr');
            $user->setPassword($faker->password(6, 7));
            $users[] = $user;
            $em->persist($user);
            if (($i % $batchSize) === 0) {
                $em->flush();
                $em->clear();
            }
        }
        $em->flush();
        return $this->redirectToRoute('app_admin_index');
    }

    #[Route('/rategen', name: 'app_rate_gen', methods: ['POST'])]
    public function rateGen(Request $request, EntityManagerInterface $entityManager)
    {
        $faker = Factory::create();
        $em = $entityManager;

        $users = array();
        $batchSize = 1000; // adjust as needed

        $q = $em->createQuery("DELETE FROM App\Entity\Rating r JOIN r.user u WHERE u.email LIKE '%@ratewatchlist.fr'");
        $q->execute();

        $q = $em->createQuery("DELETE FROM App\Entity\User u WHERE u.email LIKE '%@ratewatchlist.fr'");
        $q->execute();


        for ($i = 0; $i < 100; $i++) {
            $user = new User();
            $tmpname = $faker->userName;
            $user->setName($tmpname);
            $user->setEmail($tmpname . $i . '@ratewatchlist.fr');
            $user->setPassword($faker->password(6, 7));
            $users[] = $user;
            $em->persist($user);
            if (($i % $batchSize) === 0) {
                $em->flush();
                $em->clear();
            }
        }
        $em->flush();

        $users = $em->getRepository(User::class)->findAll();
        $seriesIds = range(1, 234);
        foreach ($users as $u) {
            if (substr($u->getEmail(), -17) != '@ratewatchlist.fr') {
                continue;
            }
            $tempSeriesIds = $seriesIds;
            shuffle($tempSeriesIds);
            $tempSeriesIds = array_slice($tempSeriesIds, 0, rand(50, 100));
            foreach ($tempSeriesIds as $id) {
                $series = $em->getRepository(Series::class)->findOneBy(['id' => $id]);
                if (!$series) {
                    continue;
                }
                $rating = new Rating();
                $rating->setSeries($series);
                $rating->setUser($u);
                $rating->setValue(rand(0, 10));
                $rating->setComment($faker->text(200));
                $rating->setValide(1);
                $series->addRating($rating);
                $ratings[] = $rating;
                $em->persist($rating);
                $u->addSeries($series);
            }
            $em->flush();
        }
        return $this->redirectToRoute('app_admin_index');
    }


    #[Route('/comments', name: 'app_comments_moderate', methods: ['GET', 'POST'])]
    public function moderateComments(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        $query = $entityManager->createQuery("SELECT r FROM App\Entity\Rating r WHERE r.valide = 0 ORDER BY r.id DESC");
        $pagination = $paginator->paginate($query, $request->query->getInt('page', 1), 15);
        return $this->render('admin/comments.html.twig', [
            'ratings' => $pagination,
        ]);
    }

    #[Route('/comments/validate/{id}', name: 'app_comments_validate', methods: ['POST'])]
    public function validateComment(int $id, EntityManagerInterface $entityManager): Response
    {
        $rating = $entityManager->getRepository(Rating::class)->find($id);
        $rating->setValide(1);
        $entityManager->flush();
        return $this->redirectToRoute('app_comments_moderate');
    }

#[Route('/comments/delete/{id}', name: 'app_comments_delete', methods: ['POST'])]
    public function deleteComment(int $id, EntityManagerInterface $entityManager): Response
    {
        $rating = $entityManager->getRepository(Rating::class)->find($id);
        $entityManager->remove($rating);
        $entityManager->flush();
        return $this->redirectToRoute('app_comments_moderate');
    }
}
