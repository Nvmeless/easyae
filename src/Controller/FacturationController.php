<?php

namespace App\Controller;

use App\Entity\Facturation;
use App\Repository\ContratRepository;
use Doctrine\ORM\EntityManagerInterface;
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
    #[Route(name: 'api_facturation_index', methods: ["GET"])]
    #[IsGranted("ROLE_ADMIN", message: "not authorized")]
    public function getAll(FacturationRepository $facturationRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {

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

        if (!$contrat) {
            return new JsonResponse(['message' => 'Contrat non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        $facturation = $facturationRepository->findOneBy(['contrat' => $contrat]);

        if (!$facturation) {
            $facturation = new Facturation();
            $facturation->setContrat($contrat)
                ->setStatus("on")
                ->setName("Facture pour " . $contrat->getName());
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
    public function delete(TagAwareCacheInterface $cache, Facturation $facturation, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $request->toArray();
        if (isset($data['force']) && $data['force'] === true) {
            if (!$this->isGranted("ROLE_ADMIN")) {
                return new JsonResponse(["error" => "Hanhanhaaaaan vous n'avez pas dit le mot magiiiiqueeuuuuuh"], JsonResponse::HTTP_FORBIDDEN);
            }
            $entityManager->remove(object: $facturation);
        } else {
            $facturation->setStatus("off");
            $entityManager->persist(object: $facturation);
        }
        $entityManager->flush();
        $cache->invalidateTags(["facturation"]);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, []);
    }
}
