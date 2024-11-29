<?php

namespace App\Controller;

use App\Repository\AdviceRepository;
use App\Repository\MonthRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class AdviceController extends AbstractController
{
    //Get advice for the current month by dynamically filtering the months collection before serialization.
    #[Route('/api/advice', name: 'currentMonthAdvice', methods: ['GET'])]
    public function getAdviceForCurrentMonth(AdviceRepository $adviceRepository, SerializerInterface $serializer): JsonResponse
    {
        $currentMonthNumber = (int) date('n'); // Get the current month number (1-12)

        $advices = $adviceRepository->findAll();

        $filteredAdvices = array_map(function ($advice) use ($currentMonthNumber) {
            $filteredMonths = $advice->getMonths()->filter(fn($month) => $month->getMonthNumber() === $currentMonthNumber
            );

            if ($filteredMonths->isEmpty()) {
                return null;
            }

            $clonedAdvice = clone $advice;
            $clonedAdvice->setMonths($filteredMonths);

            return $clonedAdvice;
        },
            $advices);

        $filteredAdvices = array_filter($filteredAdvices);

        $jsonContent = $serializer->serialize(array_values($filteredAdvices), 'json', ['groups' => 'advice']);

        $data = json_decode($jsonContent, true);

        foreach ($data as &$advice) {
            $advice['months'] = array_values($advice['months']);
        }

        $jsonContent = json_encode($data);

        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
    }
    //Get advice for a selected month 
    #[Route('/api/advice/{month}', name: 'selectedMonthAdvice', methods: ['GET'])]
    public function getAdviceByMonth(int $month, AdviceRepository $adviceRepository, MonthRepository $monthRepository, SerializerInterface $serializer): JsonResponse
    {
        $monthEntity = $monthRepository->findOneBy(['month_number' => $month]);

        if (!$monthEntity) {
            return new JsonResponse(['message' => 'Month not found'], Response::HTTP_NOT_FOUND);
        }

        $advices = $adviceRepository->findAdvicesForMonth($monthEntity);

        if (empty($advices)) {
            return new JsonResponse(['message' => 'No advice found for this month'], Response::HTTP_NOT_FOUND);
        }

        foreach ($advices as $advice) {
            $advice->setMonths($advice->getMonths()->filter(function ($monthEntity) use ($month) {
                return $monthEntity->getMonthNumber() === $month;
            })
            );
        }

        $jsonAdvices = $serializer->serialize($advices, 'json', ['groups' => ['advice', 'months']]);
        $data = json_decode($jsonAdvices, true);

        foreach ($data as &$advice) {
            $advice['months'] = array_values($advice['months']);
        }

        $jsonAdvices = json_encode($data);

        return new JsonResponse($jsonAdvices, Response::HTTP_OK, [], true);
    }

    
}
