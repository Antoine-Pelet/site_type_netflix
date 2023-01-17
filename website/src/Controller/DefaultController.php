<?php

namespace App\Controller;

use App\Entity\Series;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_default', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $appointmentsRepository = $entityManager->getRepository(Series::class);

        $youtubeTrailer = $appointmentsRepository->createQueryBuilder('ytb')
        ->select('ytb.title, ytb.youtubeTrailer, ytb.id')
        ->getQuery()
        ->getResult();

        shuffle($youtubeTrailer);

        return $this->render('default/index.html.twig', [
            'controller_name' => 'DefaultController',
            'series' => $youtubeTrailer
        ]);
    }
}
