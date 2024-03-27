<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\TimeRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Time;
use App\Form\Type\TimeFormType;


class MainController extends AbstractController
{
    #[Route('/')]
    public function index(TimeRepository $timeRepository): Response
    {
        return $this->render('list.html.twig', ['times' => $timeRepository->findAll()]);
    }

    #[Route('/create')]
    public function createTime(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TimeFormType::class, new Time());

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $time = $form->getData();
            $entityManager->persist($time);
            $entityManager->flush();
            $this->addFlash('success', 'Time record was created!');
            return $this->redirectToRoute('app_main_index');
        }

        return $this->render('create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/edit/{id}')]
    public function editTime(Request $request, EntityManagerInterface $entityManager, $id): Response
    {

        $time = $entityManager->getRepository(Time::class)->find($id);

        $form = $this->createForm(TimeFormType::class, $time);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $time      = $form->getData();
            $entityManager->persist($time);
            $entityManager->flush();
            $this->addFlash('success', 'Time record was edited!');
            return $this->redirectToRoute('app_main_index');
        }

        return $this->render('create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/del/{id}')]
    public function deleteTime(EntityManagerInterface $entityManager, $id): Response
    {

        $time = $entityManager->getRepository(Time::class)->find($id);

        $entityManager->remove($time);
        $entityManager->flush();
        $this->addFlash('success', 'Time record was deleted!');

        return $this->redirectToRoute('app_main_index');
    }

    #[Route('/days')]
    public function days(TimeRepository $timeRepository): Response
    {
        $days = $timeRepository->findAll();
        $hours = [];

        foreach($days as $day){
            $dateTitle = $day->getStartTime();

            $timestampStart = strtotime($day->getStartTime()->format("d.m.Y H:i"));
            $timestampEnd = strtotime($day->getEndTime()->format("d.m.Y H:i"));

            $diff_seconds = $timestampEnd - $timestampStart;
            $diff_hours = $diff_seconds / (60 * 60);

            $hours[] = [
                'title' => $dateTitle,
                'count' => $diff_hours,
                'id' => $day->getId(),
            ];
        }

        return $this->render('days.html.twig', ['days' => $hours]);
    }

    #[Route('/month')]
    public function month(TimeRepository $timeRepository): Response
    {
        $days = $timeRepository->findAll();
        $sumHours = [];

        foreach($days as $day){

            $timestampStart = strtotime($day->getStartTime()->format("d.m.Y H:i"));
            $timestampEnd = strtotime($day->getEndTime()->format("d.m.Y H:i"));

            $diff_seconds = $timestampEnd - $timestampStart;
            $diff_hours = $diff_seconds / (60 * 60);

            $monthYear = $day->getStartTime()->format("F Y");

            if(isset($sumHours[$monthYear])){
                $sumHours[$monthYear] += $diff_hours;
            } else {
                $sumHours[$monthYear] = $diff_hours;
            }
        }

        return $this->render('month.html.twig', ['months' => $sumHours]);
    }

    #[Route('/csv')]
    public function csv(TimeRepository $timeRepository): Response
    {
 
        $list = array();

        for($i=0;$i<count($timeRepository->findAll());$i++){

            $start = $timeRepository->findAll()[$i]->getStartTime()->format('Y-m-d H:i');
            $end = $timeRepository->findAll()[$i]->getEndTime()->format('Y-m-d H:i');
            $list[] = array($start, $end);
        }

        $fp = fopen('php://output','w', "w");
     
        foreach ($list as $line)
        {
            fputcsv(
                $fp, // The file pointer
                $line, // The fields
                ',' // The delimiter
            );      
        }
     
        fclose($fp); 

        $fileName = 'data.csv';

        $response = new Response();
        $response->headers->set('Content-type', 'text/csv');
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '";');
        $response->sendHeaders();
        return $response;

    }


}