<?php

namespace App\Controller;

use App\Entity\ProductType;
use App\Repository\ProductTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\DeleteService;

#[Route('/api/productType')]

class ProductTypeController extends AbstractController
{
    #[Route(name: 'app_product_type_index', methods: ["GET"])]
    #[IsGranted("ROLE_USER", message: "Hanhanhaaaaan vous n'avez pas dit le mot magiiiiqueeuuuuuh")]
    public function getAll(ProductTypeRepository $productTypeRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $idCache = "getAllProductType";
        $productTypeJson = $cache->get($idCache, function (ItemInterface $item) use ($productTypeRepository, $serializer) {
            $item->tag("productType");
            $productTypeList = $productTypeRepository->findAll();
            $productTypeJson = $serializer->serialize($productTypeList, 'json', ['groups' => "productType"]);

            return $productTypeJson;
        });

        return new JsonResponse($productTypeJson, Response::HTTP_OK, [], true);
    }

    #[Route(path: '/{id}', name: 'api_product_type', methods: ["GET"] )]
    public function get(ProductType $productType, SerializerInterface $serializer):JsonResponse
    {
        $productTypeJson = $serializer->serialize($productType, 'json', ['groups' => "productType"]);

        return new JsonResponse($productTypeJson, Response::HTTP_OK, [], true);
    }

    #[Route(name: 'api_product_type_new', methods: ["POST"])]
    public function create(ValidatorInterface $validator, TagAwareCacheInterface $cache, Request $request, ProductTypeRepository $productTypeRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $request->toArray();
        $productType = $serializer->deserialize($request->getContent(), ProductType::class, 'json', []);
        $productType->setClient($productType)
            ->setStatus("on")
        ;

        $errors = $validator->validate($productType);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);

        }
        $entityManager->persist($productType);
        $entityManager->flush();
        $cache->invalidateTags(["productType"]);
        $productTypeJson = $serializer->serialize($productType, 'json', ['groups' => "productType"]);
        return new JsonResponse($productTypeJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(path: "/{id}", name: 'api_product_type_edit', methods: ["PATCH"])]
    public function update(ProductType $productType, UrlGeneratorInterface $urlGenerator, Request $request, ProductTypeRepository $productTypeRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $request->toArray();
        if (isset($data['productType'])) {

            $productType = $productTypeRepository->find($data["productType"]);
        }


        $updatedProductType = $serializer->deserialize($request->getContent(), ProductType::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $productType]);
        $updatedProductType
            ->setProductType($client ?? $updatedProductType->getProductType())
            ->setStatus("on")
        ;

        $entityManager->persist($updatedProductType);
        $entityManager->flush();

        $location = $urlGenerator->generate("api_product_type", ['id' => $updatedProductType->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, ["Location" => $location]);
    }

    #[Route(path: "/{id}", name: 'api_product_type_delete', methods: ["DELETE"])]
    public function delete(ProductType $productType, Request $request, DeleteService $deleteService): JsonResponse
    {
        $data = $request->toArray();
        return $deleteService->deleteEntity($productType, $data, 'productType');
    }
}
