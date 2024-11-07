<?php

namespace App\Controller;

use App\Entity\Fonction;
use App\enum\EAction;
use App\enum\EService;
use App\Repository\FonctionRepository;
use App\Traits\HistoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route('/api/fonction')]
class FonctionController extends AbstractController
{

    use HistoryTrait;

    #[Route(name: 'app_fonction', methods: ["GET"])]
    public function getAll(FonctionRepository $fonctionRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $this->addHistory(EService::FONCTION, EAction::READ);

        $idCache = "getAllFonctions";
        $fonctionJson = $cache->get($idCache, function (ItemInterface $item) use ($fonctionRepository, $serializer) {
            $item->tag("fonction");
            $fonctionList = $fonctionRepository->findAll();
            return $serializer->serialize($fonctionList, 'json', ['groups' => "fonction"]);
        });

        return new JsonResponse($fonctionJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(path: '/{id}', name: 'api_fonction_show', methods: ["GET"])]
    public function get(Fonction $fonction, SerializerInterface $serializer): JsonResponse
    {
        $this->addHistory(EService::FONCTION, EAction::READ);

        $fonctionJson = $serializer->serialize($fonction, 'json', ['groups' => "fonction"]);
        return new JsonResponse($fonctionJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(name: 'api_fonction_new', methods: ["POST"])]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $this->addHistory(EService::FONCTION, EAction::CREATE);

        $fonction = $serializer->deserialize($request->getContent(), Fonction::class, 'json', []);
        $fonction->setStatus("on");

        $entityManager->persist($fonction);
        $entityManager->flush();

        $cache->invalidateTags(["fonction"]);

        $fonctionJson = $serializer->serialize($fonction, 'json', ['groups' => "fonction"]);
        return new JsonResponse($fonctionJson, JsonResponse::HTTP_CREATED, [], true);
    }

    #[Route(path: "/{id}", name: 'api_fonction_edit', methods: ["PATCH"])]
    public function update(Fonction $fonction, UrlGeneratorInterface $urlGenerator, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $this->addHistory(EService::FONCTION, EAction::UPDATE, $fonction);

        $updatedFonction = $serializer->deserialize($request->getContent(), Fonction::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $fonction]);
        $updatedFonction->setStatus("on");

        $entityManager->persist($updatedFonction);
        $entityManager->flush();

        $cache->invalidateTags(["fonction"]);

        $location = $urlGenerator->generate("api_fonction_show", ['id' => $updatedFonction->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, ["Location" => $location]);
    }

    #[Route(path: "/{id}", name: 'api_fonction_delete', methods: ["DELETE"])]
    public function delete(Fonction $fonction, Request $request, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $this->addHistory(EService::FONCTION, EAction::DELETE, $fonction);

        $data = $request->toArray();
        if (isset($data['force']) && $data['force'] === true) {
            $entityManager->remove($fonction);
        } else {
            $fonction->setStatus("off");
            $entityManager->persist($fonction);
        }

        $entityManager->flush();

        $cache->invalidateTags(["fonction"]);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
