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

#[Route('/api/quantity-type')]
class QuantityTypeController extends AbstractController
{
    use CrudTrait;

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
    }
}
