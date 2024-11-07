<?php

namespace App\Controller;

use App\Entity\History;
use App\Repository\HistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use App\Service\DeleteService;

#[Route('/api/history')]

class HistoryController extends AbstractController
{
    #[Route(name: 'api_history_index', methods: ["GET"])]
    public function getAll(HistoryRepository $historyRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $idCache = "getAllHistory";
        $historyJson = $cache->get($idCache, function (ItemInterface $item) use ($historyRepository, $serializer) {
            $item->tag("history");
            $historyList = $historyRepository->findAll();
            $historyJson = $serializer->serialize($historyList, 'json', ['groups' => "history"]);
            return $historyJson;
        });
        return new JsonResponse($historyJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(path: '/{id}', name: 'api_history_show', methods: ["GET"])]
    public function get(History $history = null, SerializerInterface $serializer): JsonResponse
    {
        if (!$history) {
            return new JsonResponse(['error' => 'History not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $historyJson = $serializer->serialize($history, 'json', ['groups' => ["history"]]);
        return new JsonResponse($historyJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(name: 'api_history_new', methods: ["POST"])]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $history = $serializer->deserialize($request->getContent(), History::class, 'json');
        if (!$history) {
            return new JsonResponse(['error' => 'Invalid data'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (is_null($history->getStatus())) {
            $history->setStatus("on");
        }

        $contact = $history->getContact();
        if ($contact) {
            $entityManager->persist($contact);
        }

        $entityManager->persist($history);
        $entityManager->flush();

        $cache->invalidateTags(["history"]);

        $historyJson = $serializer->serialize($history, 'json', ['groups' => "history"]);
        return new JsonResponse($historyJson, JsonResponse::HTTP_CREATED, [], true);
    }

    #[Route(path: "/{id}", name: 'api_history_edit', methods: ["PATCH"])]
    public function update(History $history, UrlGeneratorInterface $urlGenerator, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $updatedHistory = $serializer->deserialize($request->getContent(), History::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $history]);
        $updatedHistory->setStatus("on");

        $entityManager->persist($updatedHistory);
        $entityManager->flush();

        $cache->invalidateTags(["history"]);

        $location = $urlGenerator->generate("api_history_show", ['id' => $updatedHistory->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, ["Location" => $location]);
    }

    #[Route(path: "/{id}", name: 'api_history_delete', methods: ["DELETE"])]
    public function delete(History $history, Request $request, DeleteService $deleteService): JsonResponse
    {
        $data = $request->toArray();
        return $deleteService->deleteEntity($history, $data, 'history');
    }
}
