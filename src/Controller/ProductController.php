<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Repository\ProductTypeRepository;
use App\Repository\QuantityTypeRepository;
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
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use App\Service\DeleteService;

#[Route('/api/product')]

class ProductController extends AbstractController
{
    #[Route(name: 'api_product_index', methods: ['GET'])]
    #[IsGranted("ROLE_USER", message: "Hanhanhaaaaan vous n'avez pas dit le mot magiiiiqueeuuuuuh")]
    public function getAll(ProductRepository $productRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $idCache = "getAllProduct";
        $productJson = $cache->get($idCache, function (ItemInterface $item) use ($productRepository, $serializer) {
            $item->tag("product");
            $item->tag("productType");
            $item->tag("quantityType");

            $productList = $productRepository->findAll();
            return $serializer->serialize($productList, 'json', ['groups' => "product"]);
        });

        return new JsonResponse($productJson, Response::HTTP_OK, [], true);
    }

    #[Route(path: '/{id}', name: 'api_product_show', methods: ["GET"])]
    public function get(Product $product, SerializerInterface $serializer): JsonResponse
    {

        $productJson = $serializer->serialize($product, 'json', ['groups' => "product"]);


        return new JsonResponse($productJson, Response::HTTP_OK, [], true);
    }

    #[Route(name: 'api_product_new', methods: ['POST'])]
    public function create(ValidatorInterface $validator, TagAwareCacheInterface $cache, Request $request, ProductTypeRepository $productTypeRepository, QuantityTypeRepository $quantityTypeRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $request->toArray();
        $type = $productTypeRepository->find($data['type']);
        $quantityType = $quantityTypeRepository->find($data['quantityType']);

        $product = $serializer->deserialize($request->getContent(), Product::class, 'json', []);
        $product
            ->setType($type)->setStatus("on")
            ->setQuantityType($quantityType)->setStatus("on");

        $errors = $validator->validate($product);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $entityManager->persist($product);
        $entityManager->flush();
        $cache->invalidateTags(['product']);

        $productJson = $serializer->serialize($product, 'json', ['groups' => "product"]);
        return new JsonResponse($productJson, Response::HTTP_CREATED, [], true);
    }
    
    #[Route(path: '/{id}', name: 'api_product_edit', methods: ['PATCH'])]
    public function update(TagAwareCacheInterface $cache, Product $product, UrlGeneratorInterface $urlGenerator, Request $request, ProductTypeRepository $productTypeRepository, QuantityTypeRepository $quantityTypeRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $request->toArray();
        
        if (isset($data['type'])) {
            
            $type = $productTypeRepository->find($data['type']);
        }
        if (isset($data['quantityType'])) {
            $quantityType = $quantityTypeRepository->find($data['quantityType']);
        }


        $updatedProduct = $serializer->deserialize($request->getContent(), Product::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $product]);
        $updatedProduct
            ->setType($type ?? $updatedProduct->getType())
            ->setQuantityType($quantityType ?? $updatedProduct->getQuantityType())
            ->setStatus("on")
        ;

        $entityManager->persist($updatedProduct);
        $entityManager->flush();
        $cache->invalidateTags(['product', 'productType', 'quantityType']);

        $location = $urlGenerator->generate("api_product_show", ['id' => $updatedProduct->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT, ["Location" => $location]);
    }

    #[Route(path: "/{id}", name: 'api_product_delete', methods: ["DELETE"])]
    public function delete(Product $product, Request $request, DeleteService $deleteService): JsonResponse
    {
        $data = $request->toArray();
        return $deleteService->deleteEntity($product, $data, 'product');
    }
}
