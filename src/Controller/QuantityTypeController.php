<?php

namespace App\Controller;

use App\Entity\QuantityType;
use App\Repository\QuantityTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use App\Entity\Traits\CrudTrait;
use App\Service\DeleteService;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/api/quantity-type')]
class QuantityTypeController extends AbstractController
{
    use CrudTrait;
    private $user;

    public function __construct(Security $security)
    {
        $this->user = $security->getUser();
    }

    #[Route(name: 'api_quantity_type_index', methods: ["GET"])]
    public function getAllQuantityTypes(
        QuantityTypeRepository $quantityTypeRepository,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        return $this->getAll(
            $quantityTypeRepository,
            $serializer,
            $cache,
            'getAllQuantityTypes',
            'quantityType',
            ['quantityType']
        );
    }

    #[Route(path: '/{id}', name: 'api_quantity_type_show', methods: ["GET"])]
    public function getQuantityType(
        QuantityType $quantityType,
        SerializerInterface $serializer
    ): JsonResponse {
        return $this->get($quantityType, $serializer, ['quantityType']);
    }

    #[Route(name: 'api_quantity_type_new', methods: ["POST"])]
    public function createQuantityType(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        return $this->create(
            $request,
            QuantityType::class,
            $serializer,
            $validator,
            $entityManager,
            $cache,
            'quantityType',
            ['quantityType']
        );
    }

    #[Route(path: "/{id}", name: 'api_quantity_type_edit', methods: ["PATCH"])]
    public function updateQuantityType(
        Request $request,
        QuantityType $quantityType,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        TagAwareCacheInterface $cache,
        UrlGeneratorInterface $urlGenerator
    ): JsonResponse {
        return $this->update(
            $request,
            $quantityType,
            $serializer,
            $entityManager,
            $cache,
            $urlGenerator,
            'api_quantity_type_show',
            ['quantityType'],
            'quantityType'
        );
    }

    #[Route(path: "/{id}", name: 'api_quantity_type_delete', methods: ["DELETE"])]
    public function deleteQuantityType(
        QuantityType $quantityType,
        EntityManagerInterface $entityManager,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        return $this->delete(
            $quantityType,
            $entityManager,
            $cache,
            'quantityType'
        );
    public function create(ValidatorInterface $validator, TagAwareCacheInterface $cache, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $quantityType = $serializer->deserialize($request->getContent(), QuantityType::class, 'json', []);
        $quantityType->setStatus("on")
            ->setCreatedBy($this->user->getId())
            ->setUpdatedBy($this->user->getId())
        ;
        $errors = $validator->validate($quantityType);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $entityManager->persist($quantityType);
        $entityManager->flush();
        $cache->invalidateTags(["quantityType"]);
        $accountJson = $serializer->serialize($quantityType, 'json', ['groups' => "quantityType"]);
        return new JsonResponse($accountJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(path: "/{id}", name: 'api_quantity_type_edit', methods: ["PATCH"])]
    public function update(TagAwareCacheInterface $cache, QuantityType $quantityType, UrlGeneratorInterface $urlGenerator, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $updatedQuantityType = $serializer->deserialize($request->getContent(), QuantityType::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $quantityType]);
        $updatedQuantityType->setStatus("on")
            ->setUpdatedBy($this->user->getId())
        ;

        $entityManager->persist($updatedQuantityType);
        $entityManager->flush();
        $cache->invalidateTags(["quantityType"
            //, "product"
        ]);

        $location = $urlGenerator->generate("api_quantity_type_show", ['id' => $updatedQuantityType->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, ["Location" => $location]);
    }

    #[Route(path: "/{id}", name: 'api_quantity_type_delete', methods: ["DELETE"])]
    public function delete(QuantityType $quantityType, Request $request, DeleteService $deleteService): JsonResponse
    {
        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $data = $request->toArray();
        return $deleteService->deleteEntity($quantityType, $data, 'quantityType');
    }
}
