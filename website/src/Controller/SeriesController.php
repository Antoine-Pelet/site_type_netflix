<?php

namespace App\Controller;

use App\Entity\Series;
use App\Form\SeriesType;
use App\Entity\Episode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Rating;
use Doctrine\Persistence\ManagerRegistry;
use App\Controller\ArrayObject;



use App\Entity\Appointments;
use App\Entity\Genre;
// include de la pagination
use Knp\Component\Pager\PaginatorInterface;


class SeriesController extends AbstractController
{
    #[Route('/series', name: 'app_series_index', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator, EntityManagerInterface $entityManager): Response
    {
        $appointmentsRepository = $entityManager->getRepository(Series::class);

        $appointmentsRepository = $appointmentsRepository->createQueryBuilder('s')
        //->join('s.rating', 'r')
        //->groupby('s.id')
        //->orderBy('s.title', 'ASC')
        ->setFirstResult(0+25*($request->query->getInt('page', 1)-1))
        ->setMaxResults(25)
        ->where('s.title LIKE :search')
        ->setParameter('search', '%' . $request->query->get('title') . '%')
        ->orderBy('s.id', 'ASC')
        ->getQuery();

        // Paginate the results of the query
        $appointments = $paginator->paginate(
            // Doctrine Query, not results
            $appointmentsRepository,
            // Define the page parameter
            $request->query->getInt('page', 1),
            // Items per page
            25
        );

        return $this->render('series/index.html.twig', [
            'series' => $appointments,
        ]);
    }

    #[Route('/{id}', name: 'app_series_show', methods: ['GET'])]
    public function show(Series $series, EntityManagerInterface $entityManager): Response
    {

        $rates = $entityManager->getRepository(Rating::class)->createQueryBuilder('r')
        ->where('r.series = :series')
        ->setParameter('series', $series->getId())
        ->getQuery()
        ->getResult();

        $genre = $series->getGenre();

        return $this->render('series/show.html.twig', [
            'series' => $series,
            'rates' => $rates,
            'genres' => $genre,
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

    #[Route('/{id}', name: 'app_series_delete', methods: ['POST'])]
    public function delete(Request $request, Series $series, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$series->getId(), $request->request->get('_token'))) {
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
    public function listLikedSeries(Request $request, PaginatorInterface $paginator, EntityManagerInterface $entityManager): Response
    {

        /** @var \App\Entity\User */
        $user = $this->getUser();

        $likedSeries = $user->getSeries();

         // Paginate the results of the query
         $appointments = $paginator->paginate(
            // Doctrine Query, not results
            $likedSeries,
            // Define the page parameter
            $request->query->getInt('page', 1),
            // Items per page
            25
        );

        return $this->render('liked/like.html.twig', [
            'series' => $appointments,
        ]);
    }

    #[Route('/series/episode/list', name: 'app_view_episodes', methods: ['GET'])]
    public function viewed(): Response
    {

        /** @var \App\Entity\User */
        $user = $this->getUser();

        $viewedEpisode = $user->getEpisode();

        $seasons = array();
        for($i=0;$i<sizeof($viewedEpisode);$i++){
            if (!in_array($viewedEpisode[$i]->getSeason(), $seasons)) {
                $seasons[] = $viewedEpisode[$i]->getSeason();
            } 
        }
        $series = array();
        for($i=0;$i<sizeof($seasons);$i++){
            if(!in_array($seasons[$i]->getSeries(), $series)) {
                $series[] = $seasons[$i]->getSeries();  
            }
        }

        return $this->render('liked/view.html.twig', [
            'episodes' => $viewedEpisode,
            'seriesView' => $series,
            'seasons' => $seasons
        ]);
    }

    #[Route('/{id}/addRating', name: 'app_series_rate', methods: ['GET', 'POST'])]
    public function addRating(Request $request, EntityManagerInterface $entityManager): Response
    {
        $rating = new Rating();

        $series = $entityManager->getRepository(Series::class)->find($request->get('id'));

        $rating->setSeries($series);
        $rating->setUser($this->getUser());
        $rating->setValue($request->get('rate'));
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

        $episode = $entityManager->getRepository(Episode::class)->findBy(['season' => $epi->getSeason(), 'number' => range(1, $request->get('epi_number'))]);

        foreach($episode as $e) {
            $user->addEpisode($e);
        }

        $serie = $epi->getSeason()->getSeries();

        if($request->get('season_number') != 1) {
            foreach($serie->getSeasons() as $seas) {
                if($seas->getNumber() < $request->get('season_number')) {
                    $episode = $entityManager->getRepository(Episode::class)->findBy(['season' => $seas]);
                    foreach($episode as $e) {
                        $user->addEpisode($e);
                    }
                }
            }
        }


        $entityManager->flush();

        return $this->redirectToRoute('app_series_index', [], Response::HTTP_SEE_OTHER);
    }
}
