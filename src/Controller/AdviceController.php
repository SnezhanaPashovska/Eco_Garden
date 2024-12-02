<?php

namespace App\Controller;

use App\Entity\Advice;
use App\Repository\AdviceRepository;
use App\Repository\MonthRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
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

    #[Route('/api/advice/{id}', name: "getAdvice", methods: ['GET'])]
    public function getAdvice(Advice $advice, SerializerInterface $serializer): JsonResponse
    {
        $jsonContent = $serializer->serialize($advice, 'json', ['groups' => ['advice', 'advice_detail']]);
        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
    }

    // Create an advice
    #[Route('/api/advice', name: "createAdvice", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour ajouter un conseil')]
    public function createAdvice(Request $request, SerializerInterface $serializer, MonthRepository $monthRepository, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['content']) || !isset($data['months'])) {
            return new JsonResponse(['message' => 'Content and months are required'], Response::HTTP_BAD_REQUEST);
        }

        $advice = new Advice();
        $advice->setContent($data['content']);

        $months = [];
        foreach ($data['months'] as $monthNumber) {
            $monthEntity = $monthRepository->findOneBy(['month_number' => $monthNumber]);

            if ($monthEntity) {
                $months[] = $monthEntity;
            } else {
                return new JsonResponse(['message' => 'Invalid month number'], Response::HTTP_BAD_REQUEST);
            }
        }

        foreach ($months as $monthEntity) {
            $advice->addMonth($monthEntity);
        }

        $em->persist($advice);
        $em->flush();

        $location = $urlGenerator->generate('getAdvice', ['id' => $advice->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $jsonContent = $serializer->serialize($advice, 'json', ['groups' => ['advice', 'advice_detail']]);

        $responseArray = json_decode($jsonContent, true);

        return new JsonResponse($responseArray, Response::HTTP_CREATED, ['Location' => $location]);
    }

    //Update the advice

    #[Route('/api/advice/{id}', name: 'updateAdvice', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour mettre à jour ce conseil')]
    public function updateAdvice(Request $request, SerializerInterface $serializer, Advice $currentAdvice, EntityManagerInterface $em, MonthRepository $monthRepository): JsonResponse
    {
        $updatedAdvice = $serializer->deserialize($request->getContent(),
            Advice::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentAdvice]);
        $content = $request->toArray();

        if (isset($content['content'])) {
            $currentAdvice->setContent($content['content']);
        }

        if (isset($content['monthId'])) {
            $month = $monthRepository->find($content['monthId']);
            if (!$month) {
                return new JsonResponse(['message' => 'Invalid month ID'], JsonResponse::HTTP_BAD_REQUEST);
            }

            $currentAdvice->setMonths(new ArrayCollection([$month]));
        }

        $em->flush();

        $jsonContent = $serializer->serialize($updatedAdvice, 'json', ['groups' => ['advice', 'advice_detail']]);

        return new JsonResponse($jsonContent, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/advice/{id}', name: 'deleteAdvice', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour mettre à jour ce conseil')]
    public function deleteAdvice(Advice $advice, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($advice);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
