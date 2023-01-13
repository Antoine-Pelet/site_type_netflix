<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Series;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_default', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $appointmentsRepository = $entityManager->getRepository(Series::class);

        $youtubeTrailer = $appointmentsRepository->createQueryBuilder('s')
        ->select('s.title, s.youtubeTrailer, s.id')
        ->getQuery()
        ->getResult();

        shuffle($youtubeTrailer);

        return $this->render('default/index.html.twig', [
            'controller_name' => 'DefaultController',
            'series' => $youtubeTrailer
        ]);
    }
}
