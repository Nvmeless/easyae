<?php

namespace App\Controller;

use App\Entity\Facturation;
use App\enum\EAction;
use App\enum\EService;
use App\Repository\ContratRepository;

use App\Repository\FacturationRepository;
use App\Traits\HistoryTrait;

use Doctrine\ORM\EntityManagerInterface;
use App\Service\DeleteService;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\FacturationRepository;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\FacturationModelRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/facturation')]
class FacturationController extends AbstractController
{

    use HistoryTrait;

    private $user;

    public function __construct(Security $security)
    {
        $this->user = $security->getUser();
    }


    #[Route(name: 'api_facturation_index', methods: ["GET"])]
    #[IsGranted("ROLE_ADMIN", message: "not authorized")]
    public function getAll(FacturationRepository $facturationRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $this->addHistory(EService::FACTURATION, EAction::READ);

        $idCache = "getAllFacturations";
        $facturationJson = $cache->get($idCache, function (ItemInterface $item) use ($facturationRepository, $serializer) {
            $item->tag("facturation");
            $item->tag("contrat");
            $item->tag("model");
            $facturationList = $facturationRepository->findAll();
            $facturationJson = $serializer->serialize($facturationList, 'json', ['groups' => "facturation"]);

            return $facturationJson;
        });

        return new JsonResponse($facturationJson, JsonResponse::HTTP_OK, [], true);
    }
    #[Route('/{contratId}', name: 'api_facturation_create_or_show', methods: ["GET", "POST"])]
    #[IsGranted("ROLE_ADMIN", message: "not authorized")]
    public function createOrShow(
        $contratId,
        ContratRepository $contratRepository,
        FacturationRepository $facturationRepository,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ): JsonResponse {
        $contrat = $contratRepository->find($contratId);


    #[Route(path: '/{id}', name: 'api_facturation_show', methods: ["GET"])]
    public function get(Facturation $facturation, SerializerInterface $serializer): JsonResponse
    {
        $this->addHistory(EService::FACTURATION, EAction::READ);

        $facturationJson = $serializer->serialize($facturation, 'json', ['groups' => "facturation"]);

        if (!$contrat) {
            return new JsonResponse(['message' => 'Contrat non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }


        $facturation = $facturationRepository->findOneBy(['contrat' => $contrat]);


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

        if (!$facturation) {
            $facturation = new Facturation();
            $facturation->setContrat($contrat)
                ->setStatus("on")
                ->setName("Facture pour " . $contrat->getName())
                ->setCreatedBy($this->user->getId())
                ->setUpdatedBy($this->user->getId());
            $entityManager->persist($facturation);
            $entityManager->flush();
        }

        $products = $contrat->getProducts();
        $totalHT = 0;
        $totalTTC = 0;
        $productDetails = [];

        foreach ($products as $product) {
            $totalPrice = $product->getPriceUnit() * $product->getQuantity();
            $totalHT += $totalPrice;

            $fee = $product->getFees();

            $totalTTC += $totalPrice * (1 + $fee / 100);

            $productDetails[] = [
                'product' => $product->getType()->getName(),
                'quantity' => $product->getQuantity(),
                'price_unit' => $product->getPriceUnit(),
                'total_price' => $totalPrice,
                'fee' => $fee,
                'total_with_fee' => $totalPrice * (1 + $fee / 100),
            ];
        }

        $factureData = [
            'facturation_id' => $facturation->getId(),
            'contrat_name' => $contrat->getName(),
            'products' => $productDetails,
            'total_HT' => $totalHT,
            'total_TTC' => $totalTTC,
        ];

        $factureJson = $serializer->serialize($factureData, 'json', ['groups' => 'facturation']);

        return new JsonResponse($factureJson, JsonResponse::HTTP_OK, [], true);

    }
    
  

    #[Route(path: '/{id}', name: 'api_facturation_edit', methods: ["PATCH"])]
    #[IsGranted("ROLE_ADMIN", message: "not authorized")]
    public function update(TagAwareCacheInterface $cache, Facturation $facturation, Request $request, UrlGeneratorInterface $urlGenerator, ContratRepository $contratRepository, FacturationModelRepository $modelRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->addHistory(EService::FACTURATION, EAction::UPDATE, $facturation);

        $data = $request->toArray();
        if (isset($data["contrat"])) {
            $contrat = $contratRepository->find($data["contrat"]);
        }
        if (isset($data["model"])) {
            $model = $modelRepository->find($data["model"]);
        }

        $updateFacturation = $serializer->deserialize(data: $request->getContent(), type: Facturation::class, format: "json", context: [AbstractNormalizer::OBJECT_TO_POPULATE => $facturation]);
        $updateFacturation->setcontrat($contrat ?? $updateFacturation->getcontrat())->setModel($model ?? $updateFacturation->getModel())->setStatus("on");

        $entityManager->persist(object: $updateFacturation);
        $entityManager->flush();
        $cache->invalidateTags(["facturation"]);
        $location = $urlGenerator->generate("api_facturation_show", ['id' => $updateFacturation->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $facturationJson = $serializer->serialize(data: $updateFacturation, format: "json", context: ["groups" => "facturation"]);
        return new JsonResponse($facturationJson, JsonResponse::HTTP_NO_CONTENT, ["Location" => $location]);
    }

    #[Route(path: '/{id}', name: 'api_facturation_delete', methods: ["DELETE"])]
    #[IsGranted("ROLE_ADMIN", message: "not authorized")]
    public function delete(Facturation $facturation, Request $request, DeleteService $deleteService): JsonResponse
    {

        $this->addHistory(EService::FACTURATION, EAction::DELETE, $facturation);

        $data = $request->toArray();
       return $deleteService->deleteEntity($action, $data, 'facturation');
    }
}