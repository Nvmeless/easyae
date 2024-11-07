<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route('/api/user')]
class UserController extends AbstractController
{
    #[Route(name: 'api_user_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez pas les droits pour accéder à cette ressource")]
    public function getAll(UserRepository $userRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $idCache = 'getAllUsers';
        $userJson = $cache->get($idCache, function (ItemInterface $item) use ($userRepository, $serializer) {
            $item->tag('user');
            $userList = $userRepository->findAll();
            return $serializer->serialize($userList, 'json', ['groups' => 'user']);
        });

        return new JsonResponse($userJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(path: '/{id}', name: 'api_user_show', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez pas les droits pour accéder à cette ressource")]
    public function get(User $user, SerializerInterface $serializer): JsonResponse
    {
        $userJson = $serializer->serialize($user, 'json', ['groups' => 'user']);
        return new JsonResponse($userJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(name: 'api_user_new', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez pas les droits pour accéder à cette ressource")]
    public function create(ValidatorInterface $validator, TagAwareCacheInterface $cache, Request $request, ClientRepository $clientRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $request->toArray();
        $client = $clientRepository->find($data['client']);
        $user = $serializer->deserialize($request->getContent(), User::class, 'json', []);
        $user->setClient($client)
            ->setStatus('on');

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $entityManager->persist($user);
        $entityManager->flush();
        $cache->invalidateTags(['user']);
        $userJson = $serializer->serialize($user, 'json', ['groups' => 'user']);
        return new JsonResponse($userJson, JsonResponse::HTTP_CREATED, [], true);
    }

    #[Route(path: '/{id}', name: 'api_user_edit', methods: ['PATCH'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez pas les droits pour accéder à cette ressource")]
    public function update(TagAwareCacheInterface $cache, User $user, UrlGeneratorInterface $urlGenerator, Request $request, ClientRepository $clientRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $request->toArray();
        if (isset($data['client'])) {
            $client = $clientRepository->find($data['client']);
        }

        $updatedUser = $serializer->deserialize($request->getContent(), User::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $user]);
        $updatedUser
            ->setClient($client ?? $updatedUser->getClient())
            ->setStatus('on');

        $entityManager->persist($updatedUser);
        $entityManager->flush();
        $cache->invalidateTags(['user']);

        $location = $urlGenerator->generate('api_user_show', ['id' => $updatedUser->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, ['Location' => $location]);
    }

    #[Route(path: '/{id}', name: 'api_user_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez pas les droits pour accéder à cette ressource")]
    public function delete(TagAwareCacheInterface $cache, User $user, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $request->toArray();
        if (isset($data['force']) && $data['force'] === true) {
            $entityManager->remove($user);
        } else {
            $entityManager->persist($user);
        }

        $entityManager->flush();
        $cache->invalidateTags(['user']);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route(path: '/register',name: 'api_user_register', methods: ['POST'])]
    public function register(ValidatorInterface $validator, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): JsonResponse
    {
        $data = $request->toArray();
        $user = $serializer->deserialize($request->getContent(), User::class, 'json', ['user']);
        $user->setPassword($userPasswordHasher->hashPassword($user, $data['password']));

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $entityManager->persist($user);
        $entityManager->flush();
        $userJson = $serializer->serialize($user, 'json', ['groups' => 'user']);
        return new JsonResponse($userJson, JsonResponse::HTTP_CREATED, [], true);
    }
}
