<?php

namespace App\Entity\Traits;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

trait CrudTrait
{
    public function getAll($repository, SerializerInterface $serializer, TagAwareCacheInterface $cache, string $cacheKey, string $cacheTag, array $serializationGroups): JsonResponse
    {
        $jsonData = $cache->get($cacheKey, function ($item) use ($repository, $serializer, $serializationGroups, $cacheTag) {
            $item->tag($cacheTag);
            $items = $repository->findAll();
            return $serializer->serialize($items, 'json', ['groups' => $serializationGroups]);
        });

        return new JsonResponse($jsonData, JsonResponse::HTTP_OK, [], true);
    }

    public function get($entity, SerializerInterface $serializer, array $serializationGroups): JsonResponse
    {
        $jsonData = $serializer->serialize($entity, 'json', ['groups' => $serializationGroups]);
        return new JsonResponse($jsonData, JsonResponse::HTTP_OK, [], true);
    }

    public function create(
        Request $request,
        string $entityClass,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        TagAwareCacheInterface $cache,
        string $cacheTag,
        array $serializationGroups
    ): JsonResponse
    {
        $entity = $serializer->deserialize($request->getContent(), $entityClass, 'json');
        $errors = $validator->validate($entity);

        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $entityManager->persist($entity);
        $entityManager->flush();
        $cache->invalidateTags([$cacheTag]);

        return $this->get($entity, $serializer, $serializationGroups);
    }

    public function update(
        Request $request,
                $entity,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        TagAwareCacheInterface $cache,
        UrlGeneratorInterface $urlGenerator,
        string $routeName,
        array $serializationGroups,
        string $cacheTag
    ): JsonResponse
    {
        $serializer->deserialize($request->getContent(), get_class($entity), 'json', ['object_to_populate' => $entity]);

        $entityManager->flush();
        $cache->invalidateTags([$cacheTag]);

        $location = $urlGenerator->generate($routeName, ['id' => $entity->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, ["Location" => $location]);
    }

    public function delete($entity, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache, string $cacheTag): JsonResponse
    {
        $entityManager->remove($entity);
        $entityManager->flush();
        $cache->invalidateTags([$cacheTag]);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
