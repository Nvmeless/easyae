<?php

namespace App\Controller;

use App\Entity\InfoType;
use App\Repository\InfoTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use App\Service\DeleteService;

#[Route('/api/info-type')]
class InfoTypeController extends AbstractController
{
    #[Route(name: 'api_InfoType_index', methods: ["GET"])]
    public function getAll(InfoTypeRepository $infoTypeRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $idCache = "getAllInfoType";
        $infoTypeJson = $cache->get($idCache, function (ItemInterface $item) use ($infoTypeRepository, $serializer) {
            $item->tag("infoType");
            $infoTypeList = $infoTypeRepository->findAll();
            $infoTypeJson = $serializer->serialize($infoTypeList, 'json', ['groups' => "infoType"]);
            return $infoTypeJson;
        });


        return new JsonResponse($infoTypeJson, JsonResponse::HTTP_OK, [], true);
    }
    #[Route(path: '/{id}', name: 'api_infoType_show', methods: ["GET"])]
    public function get(InfoType $infoType, SerializerInterface $serializer): JsonResponse
    {
     

        $infoTypeJson = $serializer->serialize($infoType, 'json', ['groups' => "infoType"]);


        return new JsonResponse($infoTypeJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(name: 'api_infoType_new', methods: ["POST"])]
    public function create(TagAwareCacheInterface $cache,Request $request, InfoTypeRepository $infoTypeRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
     
        $infoType = $serializer->deserialize($request->getContent(), InfoType::class, 'json', []);
        $entityManager->persist($infoType);
        $entityManager->flush();
        $cache->invalidateTags(["infoType"]);
        $infoTypeJson = $serializer->serialize($infoType, 'json', ['groups' => "infoType"]);
        return new JsonResponse($infoTypeJson, JsonResponse::HTTP_CREATED, [], true);
    }

    #[Route(path: "/{id}", name: 'api_infoType_edit', methods: ["PATCH"])]
    public function update(TagAwareCacheInterface $cache,InfoType $infoType, UrlGeneratorInterface $urlGenerator, Request $request, InfoTypeRepository $infoTypeRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $request->toArray();


        $updatedInfoType = $serializer->deserialize($request->getContent(), InfoType::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $infoType]);
        $updatedInfoType
            ->setStatus("on")
        ;
        $entityManager->persist($updatedInfoType);
        $entityManager->flush();
        $cache->invalidateTags(["infoType"]);
        $infoTypeJson = $serializer->serialize($updatedInfoType, 'json', ['groups' => "infoType"]);
        $location = $urlGenerator->generate("api_account_show", ['id' => $updatedInfoType->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, ["Location" => $location]);
    }

    #[Route(path: "/{id}", name: 'api_infoType_delete', methods: ["DELETE"])]
    public function delete(InfoType $infoType, Request $request, DeleteService $deleteService): JsonResponse
    {
        $data = $request->toArray();
        return $deleteService->deleteEntity($infoType, $data, 'infoType');
    }
}
