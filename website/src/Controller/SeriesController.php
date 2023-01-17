<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Genre;
use App\Entity\Series;
use App\Entity\Rating;
use App\Entity\Episode;
use App\Form\SeriesType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SeriesController extends AbstractController
{
    #[Route('/series', name: 'app_series_index', methods: ['GET'])]
    public function index(
        Request $request,
        PaginatorInterface $paginator,
        EntityManagerInterface $entityManager
    ): Response {

        $stringWhere = self::stringWhere($entityManager, $request, $paginator);

        $appointmentsRepository = $entityManager->getRepository(Series::class)->createQueryBuilder('s')
        ->setFirstResult(0 + 25 * ($request->query->getInt('page', 1) - 1))
        ->setMaxResults(25)
        ->join('s.genre', 'g')
        ->join('s.rate', 'r')
        ->where('' . $stringWhere)
        ->orderBy('s.id', 'ASC')
        ->getQuery();

        $res = self::requeteFiltred($appointmentsRepository, $entityManager, $request, $paginator);

        return $this->render('series/index.html.twig', [
            'series' => $res['series'],
            'genres' => $res['genres'],
            'years' => $res['years'],
            'rates' => $res['rates'],
        ]);
    }

    public static function stringWhere(
        EntityManagerInterface $entityManager,
        Request $request,
        PaginatorInterface $paginator
    ): string {
        $appointmentsRepository = $entityManager->getRepository(Series::class);

        $title = "'%" . $request->query->get('title') . "%'";

        $genre = $request->query->get('genre');
        $debut = $request->query->get('debut');
        $fin = $request->query->get('fin');
        $rateMin = $request->query->get('rateMin');
        $rateMax = $request->query->get('rateMax');

        $stringWhere = '1 = 1';

        if ($title != "%''%") {
            $stringWhere .= ' AND s.title LIKE ' . $title;
        }
        if ($genre != '') {
            $stringWhere .= ' AND g.id = ' . $genre;
        }
        if ($debut != '') {
            $stringWhere .= ' AND s.yearStart >= ' . $debut;
        }
        if ($fin != '') {
            $stringWhere .= ' AND s.yearEnd <= ' . $fin;
        }
        if ($rateMin != '') {
            $stringWhere .= ' AND r.value >= ' . $rateMin;
        }
        if ($rateMax != '') {
            $stringWhere .= ' AND r.value <= ' . $rateMax;
        }

        return $stringWhere;
    }

    public static function requeteFiltred(
        $appointmentsRepository,
        EntityManagerInterface $entityManager,
        Request $request,
        PaginatorInterface $paginator
    ): array {
        $years = array();

        for ($i = 1900; $i < 2022; $i++) {
            $years[] = $i;
        }

        $rates = array();

        for ($i = 0; $i <= 5; $i = $i + 0.5) {
            $rates[] = $i;
        }

        $genres = $entityManager->getRepository(Genre::class)->findAll();

        // Paginate the results of the query
        $appointments = $paginator->paginate(
            // Doctrine Query, not results
            $appointmentsRepository,
            // Define the page parameter
            $request->query->getInt('page', 1),
            // Items per page
            25
        );
        $res = array();
        $res['series'] = $appointments;
        $res['genres'] = $genres;
        $res['years'] = $years;
        $res['rates'] = $rates;

        return $res;
    }

    #[Route('/{id}', name: 'app_series_show', methods: ['GET'])]
    public function show(Series $series, EntityManagerInterface $entityManager, Request $request, PaginatorInterface $paginator): Response
    {
        $serie = $entityManager->getRepository(Series::class)->find($request->get('id'));

        $seasons = $serie->getSeasons();

        $cpt = 0;
        foreach ($seasons as $s) {
            $cpt = $cpt + sizeof($s->getEpisodes());
        }

        /** @var App\Entity\User */
        $user = $this->getUser();

        $stringWhere = AdminController::donneStringWhere($entityManager, $request, "r.series = :series AND ");
        
        $rates = $entityManager->getRepository(Rating::class)->createQueryBuilder('r')
            ->join('r.series', 's')
            ->where('' . $stringWhere)
            ->setParameter('user', $user->getId())
            ->setParameter('series', $series->getId())
            ->getQuery();

        $res = AdminController::donneVariables($rates, $paginator, $request);

        if ($this->getUser() != null) {
            $epi = $entityManager->getRepository(Episode::class)->createQueryBuilder('e')
            ->leftJoin('e.user', 'us')
            ->leftJoin('e.season', 'seas')
            ->leftJoin('seas.series', 'ser')
            ->where('ser.id = :series')
            ->andWhere('us.id = :user')
            ->setParameter('series', $series->getId())
            ->setParameter('user', $user->getId())
            ->getQuery()
            ->getResult();
        } else {
            $epi = $entityManager->getRepository(Episode::class)->createQueryBuilder('e')
            ->leftJoin('e.user', 'us')
            ->leftJoin('e.season', 'seas')
            ->leftJoin('seas.series', 'ser')
            ->where('ser.id = :series')
            ->setParameter('series', $series->getId())
            ->getQuery()
            ->getResult();
        }

        $genre = $series->getGenre();

        return $this->render('series/show.html.twig', [
            'series' => $series,
            'genres' => $genre,
            'totalEpisode' => $cpt,
            'episodesVues' => $epi,
            'ratesFiltre' => $res['ratesFiltre'],
            'years' => $res['years'],
            'rates' => $res['rates'],
        ]);
    }

    #[Route('/series/{id}', name: 'app_series_poster', methods: ['GET'])]
    public function poster(Series $series): Response
    {
        return new Response(stream_get_contents($series->getPoster()), 200, array('Content=>Type' => 'image.jpeg'));
    }

    #[Route('/{id}/edit', name: 'app_series_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Series $series, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SeriesType::class, $series);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_series_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('series/edit.html.twig', [
            'series' => $series,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_series_delete', methods: ['POST'])]
    public function delete(Request $request, Series $series, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $series->getId(), $request->request->get('_token'))) {
            $entityManager->remove($series);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_series_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/follow', name: 'app_series_like', methods: ['GET'])]
    public function like(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User */
        $user = $this->getUser();

        $series = $entityManager->getRepository(Series::class)->find($request->get('id'));

        $user->addSeries($series);

        $entityManager->flush();

        return $this->redirectToRoute('app_series_index', [], Response::HTTP_SEE_OTHER);

    }

    #[Route('/series/{id}/dislike', name: 'app_series_dislike', methods: ['GET'])]
    public function dislike(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User */
        $user = $this->getUser();

        $series = $entityManager->getRepository(Series::class)->find($request->get('id'));

        $user->removeSeries($series);

        $entityManager->flush();

        return $this->redirectToRoute('app_series_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/series/like/list', name: 'app_liked_series', methods: ['GET'])]
    public function listLikedSeries(
        Request $request,
        PaginatorInterface $paginator,
        EntityManagerInterface $entityManager
    ): Response {

        $stringWhere = self::stringWhere($entityManager, $request, $paginator);

        $user = $this->getUser();

        $requete = $this->requeteSQL($entityManager, $user, $stringWhere);

        $res = self::requeteFiltred($requete, $entityManager, $request, $paginator);

        return $this->render('liked/like.html.twig', [
            'series' => $res['series'],
            'genres' => $res['genres'],
            'years' => $res['years'],
            'rates' => $res['rates'],
        ]);
    }

    #[Route('/series/episode/list/{id}', name: 'app_view_episodes', methods: ['GET'])]
    public function viewed(
        EntityManagerInterface $entityManager,
        Request $request,
        PaginatorInterface $paginator
    ): Response {

        $stringWhere = self::stringWhere($entityManager, $request, $paginator);
        
        $result = self::getEpisodeVu($entityManager, $stringWhere, $this->getUser());

        $res = self::requeteFiltred($result['seriesView'], $entityManager, $request, $paginator);

        return $this->render('liked/view.html.twig', [
            'series' => $res['series'],
            'genres' => $res['genres'],
            'years' => $res['years'],
            'rates' => $res['rates'],
            'episodes' => $result['episodes'],
            'seriesView' => $result['seriesView'],
            'seasons' => $result['seasons'],
            ]);
    }

    public static function getEpisodeVu(
        EntityManagerInterface $entityManager,
        string $stringWhere,
        User $user
    ) {
        $viewedEpisode = $user->getEpisode();
        $seasons = array();
        for ($i = 0; $i < sizeof($viewedEpisode); $i++) {
            if (!in_array($viewedEpisode[$i]->getSeason(), $seasons)) {
                $seasons[] = $viewedEpisode[$i]->getSeason();
            }
        }
        $series = array();
        for ($i = 0; $i < sizeof($seasons); $i++) {
            if (!in_array($seasons[$i]->getSeries(), $series)) {
                $series[] = $seasons[$i]->getSeries();
            }
        }

        $series = $entityManager->getRepository(Series::class)->createQueryBuilder('s')
            ->join('s.user', 'u')
            ->join('s.genre', 'g')
            ->join('s.rate', 'r')
            ->where('u.id = :user AND s.id IN (:series) AND ' . $stringWhere)
            ->setParameter('user', $user->getId())
            ->setParameter('series', $series)
            ->getQuery();

        $res['seriesView'] = $series;
        $res['episodes'] = $viewedEpisode;
        $res['seasons'] = $seasons;
        return $res;
    }

    #[Route('/{id}/addRating', name: 'app_series_rate', methods: ['GET', 'POST'])]
    public function addRating(Request $request, EntityManagerInterface $entityManager): Response
    {
        $rating = new Rating();

        $series = $entityManager->getRepository(Series::class)->find($request->get('id'));

        $rating->setSeries($series);
        $rating->setUser($this->getUser());
        $rating->setValue($request->get('rate') * 2);
        $rating->setComment($request->get('comment'));

        $series->addRating($rating);
        $entityManager->persist($rating);

        $entityManager->flush();
        return $this->redirectToRoute('app_series_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/vu', name: 'app_series_vu', methods: ['GET'])]
    public function vu(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine): Response
    {
        /** @var \App\Entity\User */
        $user = $this->getUser();

        $epi = $entityManager->getRepository(Episode::class)->find($request->get('id'));

        $episode = $entityManager->getRepository(Episode::class)->findBy(
            ['season' => $epi->getSeason(),
                'number' => range(1, $request->get('epi_number'))]
        );

        foreach ($episode as $e) {
            $user->addEpisode($e);
        }

        $serie = $epi->getSeason()->getSeries();

        if ($request->get('season_number') != 1) {
            foreach ($serie->getSeasons() as $seas) {
                if ($seas->getNumber() < $request->get('season_number')) {
                    $episode = $entityManager->getRepository(Episode::class)->findBy(['season' => $seas]);
                    foreach ($episode as $e) {
                        $user->addEpisode($e);
                    }
                }
            }
        }

        $entityManager->flush();

        return $this->redirectToRoute(
            'app_series_show',
            array('id' => $epi->getSeason()->getSeries()->getId()),
            Response::HTTP_SEE_OTHER
        );
    }

    private function requeteSQL(EntityManagerInterface $entityManager, User $user, string $stringWhere)
    {
        $series = $entityManager->getRepository(Series::class)->createQueryBuilder('s')
            ->join('s.user', 'u')
            ->join('s.genre', 'g')
            ->join('s.rate', 'r')
            ->where('u.id = :user AND ' . $stringWhere)
            ->setParameter('user', $user->getId())
            ->getQuery();

        return $series;
    }
}
