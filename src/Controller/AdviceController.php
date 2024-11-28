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
    #[Route('/api/advice/', name: 'currentMonthAdvice', methods: ['GET'])]
    public function getAdviceForCurrentMonth(AdviceRepository $adviceRepository, SerializerInterface $serializer): JsonResponse
    {
        $currentMonthNumber = (int) date('n'); // Get the current month number (1-12)

        $advices = $adviceRepository->findAll();

        $filteredAdvices = array_map(function ($advice) use ($currentMonthNumber) {
            $filteredMonths = $advice->getMonths()->filter(
                fn($month) => $month->getMonthNumber() === $currentMonthNumber
            );

            if ($filteredMonths->isEmpty()) {
                return null;
            }

            $adviceArray = [
                'id' => $advice->getId(),
                'content' => $advice->getContent(),
                'months' => array_values(array_map(function ($month) {
                    return [
                        'id' => $month->getId(),
                        'name' => $month->getName(),
                        'month_number' => $month->getMonthNumber(),
                    ];
                }, $filteredMonths->toArray())),
            ];

            return $adviceArray;
        }, $advices);

        $filteredAdvices = array_filter($filteredAdvices);

        $jsonContent = $serializer->serialize(array_values($filteredAdvices), 'json');

        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
    }

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

        $filteredAdvices = array_map(function ($advice) use ($month) {
            return [
                'id' => $advice->getId(),
                'content' => $advice->getContent(),
                'month_number' => $month,
            ];
        }, $advices);

        $jsonAdvices = $serializer->serialize($filteredAdvices, 'json');

        return new JsonResponse($jsonAdvices, Response::HTTP_OK, [], true);
    }

}
