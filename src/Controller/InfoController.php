<?php

namespace App\Controller;

use App\Entity\Info;
use App\enum\EAction;
use App\enum\EService;
use App\Repository\InfoRepository;
use App\Repository\InfoTypeRepository;
use App\Traits\HistoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;


#[Route('/api/info')]

class InfoController extends AbstractController
{

    use HistoryTrait;

    #[Route(name: 'api_info_index', methods: ["GET"])]
    #[IsGranted("ROLE_USER", message: "Hanhanhaaaaan vous n'avez pas dit le mot magiiiiqueeuuuuuh")]
    public function getAll(InfoRepository $infoRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $this->addHistory(EService::INFO, EAction::READ);

        $idCache = "getAllAccounts";
        $infoJson = $cache->get($idCache, function (ItemInterface $item) use ($infoRepository, $serializer) {
            $item->tag("info");
            $item->tag("type");
            $infoList = $infoRepository->findAll();
            $infoJson = $serializer->serialize($infoList, 'json', ['groups' => "info"]);

            return $infoJson;

        });


        return new JsonResponse($infoJson, JsonResponse::HTTP_OK, [], true);
    }
    #[Route(path: '/{id}', name: 'api_info_show', methods: ["GET"])]
    public function get(Info $info, SerializerInterface $serializer): JsonResponse
    {
        $this->addHistory(EService::INFO, EAction::READ);

        // $infoList = $infoRepository->find($id);

        $infoJson = $serializer->serialize($info, 'json', ['groups' => "info"]);


        return new JsonResponse($infoJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(name: 'api_info_new', methods: ["POST"])]

    public function create(ValidatorInterface $validator, TagAwareCacheInterface $cache, Request $request, SerializerInterface $serializer, InfoTypeRepository $infoTypeRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->addHistory(EService::INFO, EAction::CREATE);

        $data = $request->toArray();
        $infoType = $infoTypeRepository->find($data["type"]);
        $info = $serializer->deserialize($request->getContent(), Info::class, 'json', []);
        $info->setType($infoType)
            ->setStatus("on")
        ;

        $errors = $validator->validate($info);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $entityManager->persist($info);
        $entityManager->flush();
        $cache->invalidateTags(["info"]);
        $infoJson = $serializer->serialize($info, 'json', ['groups' => "info"]);
        return new JsonResponse($infoJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(path: "/{id}", name: 'api_info_edit', methods: ["PATCH"])]
    public function update(TagAwareCacheInterface $cache, Info $info, UrlGeneratorInterface $urlGenerator, Request $request, InfoTypeRepository $infoTypeRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->addHistory(EService::INFO, EAction::UPDATE, $info);

        $data = $request->toArray();
        if (isset($data['type'])) {

            $type = $infoTypeRepository->find($data["type"]);
        }

        $updatedInfo = $serializer->deserialize($request->getContent(), Info::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $info]);
        $updatedInfo
            ->setType($type ?? $updatedInfo->getType())
            ->setStatus("on")
        ;

        $entityManager->persist($updatedInfo);
        $entityManager->flush();
        $cache->invalidateTags(["info", "type"]);
        $location = $urlGenerator->generate("api_info_show", ['id' => $updatedInfo->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, ["Location" => $location]);
    }

    #[Route(path: "/{id}", name: 'api_info_delete', methods: ["DELETE"])]
    public function delete(TagAwareCacheInterface $cache, Info $info, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->addHistory(EService::INFO, EAction::DELETE, $info);

        $data = $request->toArray();
        if (isset($data['force']) && $data['force'] === true) {
            $entityManager->remove($info);
        } else {
            $info
                ->setStatus("off")
            ;
            $entityManager->persist($info);
        }
        $entityManager->flush();
        $cache->invalidateTags(["info"]);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
