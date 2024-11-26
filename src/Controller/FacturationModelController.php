<?php

namespace App\Controller;
use App\Entity\Account;
use App\enum\EAction;
use App\enum\EService;
use App\Repository\ClientRepository;
use App\Repository\FacturationModelRepository;
use App\Traits\HistoryTrait;
use App\Entity\FacturationModel;
use App\Repository\FacturationModelRepository;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\FacturationModel;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use App\Service\DeleteService;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/api/facturation-model')]
#[IsGranted("ROLE_ADMIN", message: "No access!")]
class FacturationModelController extends AbstractController
{
    use HistoryTrait;
    private $user;

    public function __construct(Security $security)
    {
        $this->user = $security->getUser();
    }

    #[Route(name: 'api_facturation_model_index', methods: ["GET"])]
    public function getAll(FacturationModelRepository $facturationModelRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $this->addHistory(EService::FACTURATION_MODEL, EAction::READ);

        $idCache = "getAllFacturationsModels";

        $facturationModelJson = $cache->get($idCache, function (ItemInterface $item) use ($facturationModelRepository, $serializer) {
            $item->tag("facturationModel");
            $item->tag("client");
            $facturationModelList = $facturationModelRepository->findAll();
            $facturationModelJson = $serializer->serialize($facturationModelList, 'json', ['groups' => "facturationModel"]);

            return $facturationModelJson;

        });


        return new JsonResponse($facturationModelJson, JsonResponse::HTTP_OK, [], true);
    }
    #[Route(path: '/{id}', name: 'api_facturation_model_show', methods: ["GET"])]
    public function get(FacturationModel $facturationModel, SerializerInterface $serializer): JsonResponse
    {
        $this->addHistory(EService::FACTURATION_MODEL, EAction::READ);

        $facturationModelJson = $serializer->serialize($facturationModel, 'json', ['groups' => "facturationModel"]);

        return new JsonResponse($facturationModelJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(name: 'api_facturation_model_new', methods: ["POST"])]
    public function create(tagAwareCacheInterface $cache, Request $request, ClientRepository $clientRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->addHistory(EService::FACTURATION_MODEL, EAction::CREATE);
        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $data = $request->toArray();
        $client = $clientRepository->find($data["client"]);
        $facturationModel = $serializer->deserialize($request->getContent(), FacturationModel::class, 'json', []);
        $facturationModel->setClient($client)
            ->setStatus("on")
            ->setCreatedBy($this->user->getId())
            ->setUpdatedBy($this->user->getId())
        ;
        $entityManager->persist($facturationModel);
        $entityManager->flush();
        $cache->invalidateTags(["facturationModel"]);
        $facturationModelJson = $serializer->serialize($facturationModel, 'json', ['groups' => "facturationModel"]);
        return new JsonResponse($facturationModelJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(path: "/{id}", name: 'api_facturation_model_edit', methods: ["PATCH"])]
    public function update(tagAwareCacheInterface $cache ,FacturationModel $facturationModel, UrlGeneratorInterface $urlGenerator, Request $request, ClientRepository $clientRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->addHistory(EService::FACTURATION_MODEL, EAction::UPDATE, $facturationModel);
        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $data = $request->toArray();
        if (isset($data['client'])) {
            $client = $clientRepository->find($data["client"]);
        }


        $updatedFacturationModel = $serializer->deserialize($request->getContent(), FacturationModel::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $facturationModel]);
        $updatedFacturationModel
            ->setClient($client ?? $updatedFacturationModel->getClient())
            ->setStatus("on")
            ->setUpdatedBy($this->user->getId())
        ;

        $entityManager->persist($updatedFacturationModel);
        $entityManager->flush();
        $cache->invalidateTags(["facturationModel","client"]);
        $facturationModelJson = $serializer->serialize($updatedFacturationModel, 'json', ['groups' => "facturationModel"]);
        $location = $urlGenerator->generate("api_facturation_model_show", ['id' => $updatedFacturationModel->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, ["Location" => $location]);
    }

    #[Route(path: "/{id}", name: 'api_facturation_model_delete', methods: ["DELETE"])]
    public function delete(FacturationModel $facturationModel, Request $request, DeleteService $deleteService): JsonResponse
    {
        $this->addHistory(EService::FACTURATION_MODEL, EAction::DELETE, $facturationModel);

        $data = $request->toArray();
        if (isset($data['force']) && $data['force'] === true) {
            $entityManager->remove($facturationModel);


        } else {
            $facturationModel
                ->setStatus("off")
            ;

            $entityManager->persist($facturationModel);
        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $data = $request->toArray();
        return $deleteService->deleteEntity($facturationModel, $data, 'facturationModel');
    }
}