<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route('/api/user')]
class UserController extends AbstractController
{
    #[Route(name: 'api_user_index', methods: ["GET"])]
    #[IsGranted("ROLE_ADMIN", message: "Hanhanhaaaaan vous n'avez pas dit le mot magiiiiqueeuuuuuh")]
    public function getAll(UserRepository $userRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $idCache = "getAllUsers";
        $quantityTypeJson = $cache->get($idCache, function (ItemInterface $item) use ($userRepository, $serializer) {
            $item->tag("User");
            $quantityTypeList = $userRepository->findAll();
            $quantityTypeJson = $serializer->serialize($quantityTypeList, 'json', ['groups' => "quantityType"]);
            return $quantityTypeJson;
        });
        return new JsonResponse($quantityTypeJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(path: '/{id}', name: 'api_user_show', methods: ["GET"])]
    public function get(User $user, SerializerInterface $serializer): JsonResponse
    {
        $quantityTypeJson = $serializer->serialize($user, 'json', ['groups' => "quantityType"]);

        return new JsonResponse($quantityTypeJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(name: 'api_quantity_type_new', methods: ["POST"])]
    public function create(ValidatorInterface $validator, TagAwareCacheInterface $cache, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $serializer->deserialize($request->getContent(), User::class, 'json', []);
        $user->setStatus("on");
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $entityManager->persist($user);
        $entityManager->flush();
        $cache->invalidateTags(["User"]);
        $accountJson = $serializer->serialize($user, 'json', ['groups' => "quantityType"]);
        return new JsonResponse($accountJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(path: "/{id}", name: 'api_quantity_type_edit', methods: ["PATCH"])]
    public function update(TagAwareCacheInterface $cache, User $user, UrlGeneratorInterface $urlGenerator, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $updatedUser = $serializer->deserialize($request->getContent(), User::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $user]);
        $updatedUser->setStatus("on");

        $entityManager->persist($updatedUser);
        $entityManager->flush();
        $cache->invalidateTags([
            "User"
        ]);

        $location = $urlGenerator->generate("api_quantity_type_show", ['id' => $updatedUser->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, ["Location" => $location]);
    }

    #[Route(path: "/{id}", name: 'api_quantity_type_delete', methods: ["DELETE"])]
    #[IsGranted("ROLE_ADMIN", message: "Hanhanhaaaaan vous n'avez pas dit le mot magiiiiqueeuuuuuh")]
    public function delete(TagAwareCacheInterface $cache, User $user, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $request->toArray();
        if (isset($data['force']) && $data['force'] === true) {
            $entityManager->remove($user);
        } else {
            $user->setStatus("off");
            $entityManager->persist($user);
        }
        $cache->invalidateTags(["User"]);
        $entityManager->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
