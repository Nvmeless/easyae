<?php

namespace App\Controller;

use App\Entity\Fonction;
use App\Repository\FonctionRepository;
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
use App\Service\DeleteService;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/api/fonction')]
class FonctionController extends AbstractController
{
    private $user;

    public function __construct(Security $security)
    {
        $this->user = $security->getUser();
    }

    #[Route(name: 'app_fonction', methods: ["GET"])]
    public function getAll(FonctionRepository $fonctionRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
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
        $fonctionJson = $serializer->serialize($fonction, 'json', ['groups' => "fonction"]);
        return new JsonResponse($fonctionJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(name: 'api_fonction_new', methods: ["POST"])]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $fonction = $serializer->deserialize($request->getContent(), Fonction::class, 'json', []);
        $fonction->setStatus("on")
            ->setCreatedBy($this->user->getId())
            ->setUpdatedBy($this->user->getId())
        ;

        $entityManager->persist($fonction);
        $entityManager->flush();

        $cache->invalidateTags(["fonction"]);

        $fonctionJson = $serializer->serialize($fonction, 'json', ['groups' => "fonction"]);
        return new JsonResponse($fonctionJson, JsonResponse::HTTP_CREATED, [], true);
    }

    #[Route(path: "/{id}", name: 'api_fonction_edit', methods: ["PATCH"])]
    public function update(Fonction $fonction, UrlGeneratorInterface $urlGenerator, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $updatedFonction = $serializer->deserialize($request->getContent(), Fonction::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $fonction]);
        $updatedFonction->setStatus("on")->setUpdatedBy($this->user->getId());

        $entityManager->persist($updatedFonction);
        $entityManager->flush();

        $cache->invalidateTags(["fonction"]);

        $location = $urlGenerator->generate("api_fonction_show", ['id' => $updatedFonction->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, ["Location" => $location]);
    }

    #[Route(path: "/{id}", name: 'api_fonction_delete', methods: ["DELETE"])]
     public function delete(Fonction $fonction, Request $request, DeleteService $deleteService): JsonResponse
    {
        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $data = $request->toArray();
        return $deleteService->deleteEntity($fonction, $data, 'fonction');
    }
}
