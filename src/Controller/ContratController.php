<?php

namespace App\Controller;

use App\enum\EAction;
use App\enum\EService;
use App\Repository\ContratTypeRepository;
use App\Repository\ClientRepository;
use App\Entity\Contrat;
use App\Repository\ContratRepository;
use App\Traits\HistoryTrait;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Service\DeleteService;

#[Route('/api/contrat')]
class ContratController extends AbstractController
{


    use HistoryTrait;

    private $user;

    public function __construct(Security $security)
    {
        $this->user = $security->getUser();
    }


    #[Route(name: 'api_contrat_index', methods: ["GET"])]
    public function getAll(ContratRepository $contratRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $this->addHistory(EService::CONTRAT, EAction::READ);

        $idCache = "getAllContrats";
        // $contratList = $contratRepository->findAll();
        // $contratJson = $serializer->serialize($contratList, 'json', ['groups' => "contrat"]);

        $contratJson = $cache->get($idCache, function (ItemInterface $item) use ($contratRepository, $serializer) {
            $item->tag("contrat");
            $item->tag("client");
            $item->tag("contrat_type");
            $item->tag("product");
            $contratList = $contratRepository->findAll();
            $contratJson = $serializer->serialize($contratList, 'json', ['groups' => "contrat"]);
            return $contratJson;
        });

        return new JsonResponse($contratJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(path: "/{id}", name: 'api_contrat_show', methods: ["GET"])]
    public function get(Contrat $contrat, SerializerInterface $serializer): JsonResponse
    {

        $this->addHistory(EService::CONTRAT, EAction::READ);

        $contratJson = $serializer->serialize($contrat, 'json', ['groups' => "contrat"]);

        return new JsonResponse($contratJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(name: 'api_contrat_new', methods: ["POST"])]
    public function create(Request $request, clientRepository $clientRepository, ContratTypeRepository $typeRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {

        $this->addHistory(EService::CONTRAT, EAction::CREATE);

        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }


        $data = $request->toArray();
        $contrat = $serializer->deserialize($request->getContent(), Contrat::class, 'json', []);
        $client = $clientRepository->find($data["client"]);
        $type = $typeRepository->find($data["contratType"]);
        $start = new DateTime($data["startAt"]);
        $end = new DateTime($data["endAt"]);
        $done = $data["is_done"];
        $contrat->setClient($client)
            ->setType($type)
            ->setDone($done)
            ->setStartAt($start)
            ->setEndAt($end)
            ->setStatus("on")
            ->setCreatedBy($this->user->getId())
            ->setUpdatedBy($this->user->getId())
        ;
        $entityManager->persist($contrat);
        $entityManager->flush();
        $cache->invalidateTags(["contrat"]);
        $contratJson = $serializer->serialize($contrat, 'json', ['groups' => "contrat"]);
        return new JsonResponse($contratJson, JsonResponse::HTTP_CREATED, [], true);
    }

    #[Route(path: "/{id}", name: 'api_contrat_edit', methods: ["PATCH"])]
    public function update(Contrat $contrat, UrlGeneratorInterface $urlGenerator, Request $request, clientRepository $clientRepository, ContratTypeRepository $typeRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {

        $this->addHistory(EService::CONTRAT, EAction::UPDATE, $contrat);


        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }
         

        $data = $request->toArray();
        if (isset($data['client'])) {
            $client = $clientRepository->find($data["client"]);
        }
        if (isset($data['contratType'])) {
            $type = $typeRepository->find($data["contratType"]);
        }
        if (isset($data['is_done'])) {
            $done = $data["is_done"];
        }
        if (isset($data['startAt'])) {
            $start = new DateTime($data["startAt"]);
        }
        if (isset($data['endAt'])) {
            $end = new DateTime($data["endAt"]);
        }
        $updatedContrat = $serializer->deserialize($request->getContent(), Contrat::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $contrat]);
        $updatedContrat->setClient($client ?? $updatedContrat->getClient())
            ->setType($type ?? $updatedContrat->getType())
            ->setDone($done ?? $updatedContrat->isDone())
            ->setStartAt($start ?? $updatedContrat->getStartAt())
            ->setEndAt($end ?? $updatedContrat->getEndAt())
            ->setStatus("on")
            ->setUpdatedBy($this->user->getId())
        ;
        $entityManager->persist($contrat);
        $entityManager->flush();
        $cache->invalidateTags(["contrat"]);
        $location = $urlGenerator->generate("api_contrat_show", ['id' => $updatedContrat->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, ["Location" => $location]);
    }

    #[Route(path: "/{id}", name: 'api_contrat_delete', methods: ["DELETE"])]
    public function delete(Contrat $contrat, Request $request, DeleteService $deleteService): JsonResponse
    {

        $this->addHistory(EService::CONTRAT, EAction::DELETE, $contrat);

        $data = $request->toArray();
        if (isset($data['force']) && $data['force'] === true) {
            $entityManager->remove($contrat);
        } else {
            $contrat->setStatus("off");
            $entityManager->persist($contrat);

        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);

        }

        $data = $request->toArray();
        return $deleteService->deleteEntity($contrat, $data, 'contrat');
    }
}
