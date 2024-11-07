<?php

namespace App\Controller;

use App\Entity\Action;
use App\Repository\ActionRepository;
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

#[Route('/api/action')]

class ActionController extends AbstractController
{
    #[Route(name: 'api_action_index', methods: ["GET"])]
    public function getAll(ActionRepository $actionRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $idCache = "getAllAction";
        $actionJson = $cache->get($idCache, function (ItemInterface $item) use ($actionRepository, $serializer) {
            $item->tag("action");
            $actionList = $actionRepository->findAll();
            $actionJson = $serializer->serialize($actionList, 'json', ['groups' => "action"]);
            return $actionJson;
        });
        return new JsonResponse($actionJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(path: '/{id}', name: 'api_action_show', methods: ["GET"])]
    public function get(Action $action = null, SerializerInterface $serializer): JsonResponse
    {
        if (!$action) {
            return new JsonResponse(['error' => 'action not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $actionJson = $serializer->serialize($action, 'json', ['groups' => ["action"]]);
        return new JsonResponse($actionJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(name: 'api_action_new', methods: ["POST"])]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $action = $serializer->deserialize($request->getContent(), Action::class, 'json');
        if (!$action) {
            return new JsonResponse(['error' => 'Invalid data'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (is_null($action->getStatus())) {
            $action->setStatus("on");
        }

        $contact = $action->getContact();
        if ($contact) {
            $entityManager->persist($contact);
        }

        $entityManager->persist($action);
        $entityManager->flush();

        $cache->invalidateTags(["action"]);

        $actionJson = $serializer->serialize($action, 'json', ['groups' => "action"]);
        return new JsonResponse($actionJson, JsonResponse::HTTP_CREATED, [], true);
    }

    #[Route(path: "/{id}", name: 'api_action_edit', methods: ["PATCH"])]
    public function update(Action $action, UrlGeneratorInterface $urlGenerator, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $updatedAction = $serializer->deserialize($request->getContent(), Action::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $action]);
        $updatedAction->setStatus("on");

        $entityManager->persist($updatedAction);
        $entityManager->flush();

        $cache->invalidateTags(["action"]);

        $location = $urlGenerator->generate("api_action_show", ['id' => $updatedAction->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, ["Location" => $location]);
    }

    #[Route(path: "/{id}", name: 'api_action_delete', methods: ["DELETE"])]
    public function delete(Action $action, Request $request, DeleteService $deleteService): JsonResponse
    {
        $data = $request->toArray();
        return $deleteService->deleteEntity($action, $data, 'action');
    }
}
