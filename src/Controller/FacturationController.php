<?php

namespace App\Controller;

use App\Entity\Contrat;
use App\Entity\Facturation;
use App\enum\EAction;
use App\enum\EService;
use App\Repository\ContratRepository;
use App\Repository\FacturationRepository;
use App\Traits\HistoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route('/api/facturation')]
class FacturationController extends AbstractController
{

    use HistoryTrait;

    #[Route(name: 'api_facturation_index', methods: ["GET"])]
    #[IsGranted("ROLE_ADMIN", message: "non")]
    public function getAll(FacturationRepository $facturationRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $this->addHistory(EService::FACTURATION, EAction::READ);

        $idCache = "getAllFacturations";
        $facturationJson = $cache->get($idCache, function (ItemInterface $item) use ($facturationRepository, $serializer) {
            $item->tag("facturation");
            $item->tag("contrat");
            $facturationList = $facturationRepository->findAll();
            $facturationJson = $serializer->serialize($facturationList, 'json', ['groups' => "facturation"]);
            
            return $facturationJson;
        });

        return new JsonResponse($facturationJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(path: '/{id}', name: 'api_facturation_show', methods: ["GET"])]
    public function get(Facturation $facturation, SerializerInterface $serializer): JsonResponse
    {
        $this->addHistory(EService::FACTURATION, EAction::READ);

        $facturationJson = $serializer->serialize($facturation, 'json', ['groups' => "facturation"]);

        return new JsonResponse($facturationJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(name: 'api_facturation_new', methods: ["POST"])]
    public function create(Request $request, ContratRepository $contratRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $this->addHistory(EService::FACTURATION, EAction::CREATE);

        $data = $request->toArray();
        $contrat = $contratRepository->find($data["contrat"]);
        $facturation = $serializer->deserialize($request->getContent(), Facturation::class, 'json', []);
        $facturation->setcontrat($contrat)
            ->setStatus("on")
        ;
        $entityManager->persist($facturation);
        $entityManager->flush();
        $cache->invalidateTags(["facturation"]);
        $contratJson = $serializer->serialize($facturation, 'json', ['groups' => "facturation"]);
        return new JsonResponse($contratJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(path: '/{id}', name: 'api_facturation_edit', methods: ["PATCH"])]
    public function update(TagAwareCacheInterface $cache,Facturation $facturation, Request $request, UrlGeneratorInterface $urlGenerator, ContratRepository $contratRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->addHistory(EService::FACTURATION, EAction::UPDATE, $facturation);

        $data = $request->toArray();
        if (isset($data["contrat"])) {
            $contrat = $contratRepository->find($data["contrat"]);
        }

        $updateFacturation = $serializer->deserialize(data: $request->getContent(), type: Facturation::class, format:"json", context: [AbstractNormalizer::OBJECT_TO_POPULATE => $facturation]);
        $updateFacturation->setcontrat($contrat ?? $updateFacturation->getcontrat())->setStatus("on");

        $entityManager->persist(object: $updateFacturation);
        $entityManager->flush();
        $cache->invalidateTags(["facturation"]);
        $location = $urlGenerator->generate("api_facturation_show", ['id' => $updateFacturation->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $facturationJson = $serializer->serialize(data: $updateFacturation, format: "json", context: ["groups" => "facturation"]);
        return new JsonResponse($facturationJson, JsonResponse::HTTP_NO_CONTENT, ["Location" => $location]);
    }

    #[Route(path: '/{id}', name: 'api_facturation_delete', methods: ["DELETE"])]
    public function delete(TagAwareCacheInterface $cache, Facturation $facturation, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->addHistory(EService::FACTURATION, EAction::DELETE, $facturation);

        $data = $request->toArray();

        if (isset($data['force']) && $data['force'] === true) {
            $entityManager->remove(object: $facturation);
            $entityManager->flush();
        }
        $facturation->setStatus("off");

        $entityManager->persist(object: $facturation);
        $entityManager->flush();
        $cache->invalidateTags(["facturation"]);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, []);
    }
}
