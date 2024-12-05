<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    #[Route('/api/users', name: 'users', methods: [Request::METHOD_GET])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour voir la liste des utilisateurs')]
    public function getUserList(UserRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {
        $userList = $userRepository->findAll();
        $jsonUserList = $serializer->serialize($userList, 'json');
        return new JsonResponse($jsonUserList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/users/{id}', name: 'getUserById', methods: [Request::METHOD_GET])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour mettre à jour cet utilisateur')]
    public function getUserById(int $id, UserRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {
        $user = $userRepository->find($id);

        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $userJson = $serializer->serialize($user, 'json', ['groups' => 'user']);

        return new JsonResponse($userJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/users', name: 'createUser', methods: [Request::METHOD_POST])]
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

    #[Route('/api/users/{id}', name: 'updateUser', methods: [Request::METHOD_PUT])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour mettre à jour cet utilisateur')]
    public function updateUser(Request $request, SerializerInterface $serializer, User $user,
        EntityManagerInterface $em, UserPasswordHasherInterface $hasher, TokenStorageInterface $tokenStorage): JsonResponse {

        $token = $tokenStorage->getToken();
        if (!$token || !$token->getUser() instanceof User) {
            throw new AccessDeniedException('You must be authenticated as an admin to perform this action.');
        }

        $authenticatedUser = $token->getUser();
        if (!$authenticatedUser->getRoles('ROLE_ADMIN')) {
            throw new AccessDeniedException('Only administrators can update users.');
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }

        if (isset($data['password']) && !empty($data['password'])) {
            $hashedPassword = $hasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }

        if (isset($data['city'])) {
            $user->setCity($data['city']);
        }

        $em->flush();

        $jsonContent = $serializer->serialize($user, 'json');

        return new JsonResponse($jsonContent, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/users/{id}', name: 'deleteUser', methods: [Request::METHOD_DELETE])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour mettre à jour cet utilisateur')]
    public function deleteUser(User $user, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($user);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

}
