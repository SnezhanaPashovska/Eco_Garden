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
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
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

    #[Route('/api/users/{id}', name: 'updateUser', methods: ['PUT'])]
    public function updateUser(Request $request, SerializerInterface $serializer, User $currentUser,
        EntityManagerInterface $em, UserPasswordHasherInterface $hasher, TokenStorageInterface $tokenStorage): JsonResponse {

        $token = $tokenStorage->getToken();

        if (!$token) {
            throw new AccessDeniedException('No authentication token found.');
        }

        $authenticatedUser = $token->getUser();

        if (!$authenticatedUser instanceof User) {
            throw new AccessDeniedException('You must be logged in to perform this action.');
        }

        if ($authenticatedUser->getId() !== $currentUser->getId()) {
            return new JsonResponse(['message' => 'You are not allowed to update this user'], JsonResponse::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        // Validate input
        if (isset($data['email'])) {
            $currentUser->setEmail($data['email']);
        }

        if (isset($data['password']) && !empty($data['password'])) {
            $hashedPassword = $hasher->hashPassword($currentUser, $data['password']);
            $currentUser->setPassword($hashedPassword);
        }

        if (isset($data['city'])) {
            $currentUser->setCity($data['city']);
        }

        $em->flush();

        $jsonContent = $serializer->serialize($currentUser, 'json');

        return new JsonResponse($jsonContent, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/users/{id}', name: 'deleteUser', methods: ['DELETE'])]
    public function deleteUser(User $user, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($user);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

}
