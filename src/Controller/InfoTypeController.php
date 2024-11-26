<?php

namespace App\Controller;

use App\Entity\InfoType;
use App\enum\EAction;
use App\enum\EService;
use App\Repository\InfoTypeRepository;
use App\Traits\HistoryTrait;
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
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/api/info-type')]
class InfoTypeController extends AbstractController
{

    use HistoryTrait;
    private $user;

    public function __construct(Security $security)
    {
        $this->user = $security->getUser();
    }

    #[Route(name: 'api_InfoType_index', methods: ["GET"])]
    public function getAll(InfoTypeRepository $infoTypeRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $this->addHistory(EService::INFO_TYPE, EAction::READ);

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
        $this->addHistory(EService::INFO_TYPE, EAction::READ);

        $infoTypeJson = $serializer->serialize($infoType, 'json', ['groups' => "infoType"]);


        return new JsonResponse($infoTypeJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(name: 'api_infoType_new', methods: ["POST"])]
    public function create(TagAwareCacheInterface $cache,Request $request, InfoTypeRepository $infoTypeRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->addHistory(EService::INFO_TYPE, EAction::CREATE);
     
        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $infoType = $serializer->deserialize($request->getContent(), InfoType::class, 'json', []);
        $infoType
            ->setCreatedBy($this->user->getId())
            ->setUpdatedBy($this->user->getId())
        ;

        $entityManager->persist($infoType);
        $entityManager->flush();
        $cache->invalidateTags(["infoType"]);
        $infoTypeJson = $serializer->serialize($infoType, 'json', ['groups' => "infoType"]);
        return new JsonResponse($infoTypeJson, JsonResponse::HTTP_CREATED, [], true);
    }

    #[Route(path: "/{id}", name: 'api_infoType_edit', methods: ["PATCH"])]
    public function update(TagAwareCacheInterface $cache,InfoType $infoType, UrlGeneratorInterface $urlGenerator, Request $request, InfoTypeRepository $infoTypeRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->addHistory(EService::INFO_TYPE, EAction::UPDATE, $infoType);

        $data = $request->toArray();
        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $data = $request->toArray();

        $updatedInfoType = $serializer->deserialize($request->getContent(), InfoType::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $infoType]);
        $updatedInfoType
            ->setStatus("on")
            ->setUpdatedBy($this->user->getId())
        ;
        $entityManager->persist($updatedInfoType);
        $entityManager->flush();
        $cache->invalidateTags(["infoType"]);
        $infoTypeJson = $serializer->serialize($updatedInfoType, 'json', ['groups' => "infoType"]);
        $location = $urlGenerator->generate("api_account_show", ['id' => $updatedInfoType->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, ["Location" => $location]);
    }

    #[Route(path: "/{id}", name: 'api_infoType_delete', methods: ["DELETE"])]
    public function delete( TagAwareCacheInterface $cache,InfoType $infoType,UrlGeneratorInterface $urlGenerator, Request $request,SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->addHistory(EService::INFO_TYPE, EAction::DELETE, $infoType);

        $data = $request->toArray();
    
        if (isset($data['force']) && $data['force'] === true) {
            $entityManager->remove($infoType);
            

        } else {
            $infoType
                ->setStatus("off")
            ;

            $entityManager->persist($infoType);
    public function delete(InfoType $infoType, Request $request, DeleteService $deleteService): JsonResponse
    {
        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }
        
        $data = $request->toArray();
        return $deleteService->deleteEntity($infoType, $data, 'infoType');
    }
}
