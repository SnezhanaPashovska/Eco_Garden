<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    #[Route('/api/users', name: 'users', methods: ['GET'])]
    public function getUserList(UserRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {
        $userList = $userRepository->findAll();
        $jsonUserList = $serializer->serialize($userList, 'json');
        return new JsonResponse($jsonUserList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/users/{id}', name: 'getUserById', methods: ['GET'])]
    public function getUserById(int $id, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($id);

        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse($user, JsonResponse::HTTP_OK);
    }

    #[Route('/api/users', name: 'createUser', methods: ['POST'])]
    public function createUser(Request $request, SerializerInterface $serializer, EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator, UserPasswordHasherInterface $hasher, ValidatorInterface $validator): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password']) || !isset($data['city'])) {
            return new JsonResponse(['message' => 'Email, password and city are required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setCity($data['city']);
        $user->setRoles(['ROLE_USER']);

        $hashPassword = $hasher->hashPassword($user, $data['password']);
        $user->setPassword($hashPassword);

        // Validate the user
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorsString = (string) $errors;
            return new JsonResponse(['message' => $errorsString], JsonResponse::HTTP_BAD_REQUEST);
        }

        $em->persist($user);
        $em->flush();

        $location = $urlGenerator->generate('getUserById', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $jsonContent = $serializer->serialize($user, 'json');

        $responseArray = json_decode($jsonContent, true);

        return new JsonResponse($responseArray, Response::HTTP_CREATED, ['Location' => $location]);
    }
}
