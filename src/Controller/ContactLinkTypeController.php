<?php

namespace App\Controller;

use App\Entity\ContactLinkType;
use App\enum\EAction;
use App\enum\EService;
use App\Repository\ContactLinkTypeRepository;
use App\Traits\HistoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Service\DeleteService;

#[Route('/api/contact-link-type')]

class ContactLinkTypeController extends AbstractController
{


    use HistoryTrait;

    private $user;


    public function __construct(
        private readonly TagAwareCacheInterface $cache,
        Security $security
    ) {
        $this->user = $security->getUser();
    }

    #[Route(name: 'api_contact_link_type_index', methods: ["GET"])]
    public function getAll(ContactLinkTypeRepository $contactLinkTypeRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $this->addHistory(EService::CONTACT_LINK_TYPE, EAction::READ);

        $idCache = "getAllContactLinkType";
        $contactLinkTypeJson = $cache->get($idCache, function (ItemInterface $item) use ($contactLinkTypeRepository, $serializer) {
            $item->tag("contactLinkType");
            $item->tag("client");
            $contactLinkTypeList = $contactLinkTypeRepository->findAll();
            $contactLinkTypeJson = $serializer->serialize($contactLinkTypeList, 'json', ['groups' => "contactLinkType"]);
            return $contactLinkTypeJson;
        });


        return new JsonResponse($contactLinkTypeJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(path: '/{id}', name: 'api_contact_link_type_show', methods: ["GET"])]
    public function get(ContactLinkType $contactLinkType, SerializerInterface $serializer): JsonResponse
    {
        $this->addHistory(EService::CONTACT_LINK_TYPE, EAction::READ);

        $contactLinkTypeJson = $serializer->serialize($contactLinkType, 'json', ['groups' => "contactLinkType"]);

        return new JsonResponse($contactLinkTypeJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(path: "/{id}", name: 'api_contact_link_type_edit', methods: ["PATCH"])]
    public function update(ContactLinkType $contactLinkType, UrlGeneratorInterface $urlGenerator, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {

        $this->addHistory(EService::CONTACT_LINK_TYPE, EAction::UPDATE, $contactLinkType);

        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }


        $updatedContactLinkType = $serializer->deserialize($request->getContent(), $contactLinkType::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $contactLinkType]);
        $updatedContactLinkType->setUpdatedBy($this->user->getId());

        $entityManager->persist($updatedContactLinkType);
        $entityManager->flush();

        $cache->invalidateTags(["contactLinkType", "client"]);

        $location = $urlGenerator->generate("api_contact_link_type_show", ['id' => $updatedContactLinkType->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, ["Location" => $location]);
    }

    #[Route(path: "/{id}", name: 'api_contact_link_type_delete', methods: ["DELETE"])]
    public function delete(ContactLinkType $contactLinkType, Request $request, DeleteService $deleteService): JsonResponse
    {

        $this->addHistory(EService::CONTACT_LINK_TYPE, EAction::DELETE, $contactLinkType);

        $data = $request->toArray();
        if (isset($data['force']) && $data['force'] === true) {
            $entityManager->remove($contactLinkType);
        } else {
            $contactLinkType->setStatus("off");
            $entityManager->persist($contactLinkType);

        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);

        }

        $data = $request->toArray();
        return $deleteService->deleteEntity($contactLinkType, $data, 'contactLinkType');
    }
}
