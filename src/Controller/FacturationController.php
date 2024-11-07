<?php

namespace App\Controller;

use App\Entity\Facturation;
use App\Repository\FacturationRepository;
use App\Repository\ContratRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use App\Service\DeleteService;

#[Route('/api/facturation')]
class FacturationController extends AbstractController
{
    #[Route('/{contratId}', name: 'api_facturation_create_or_show', methods: ["GET", "POST"])]
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
      #[Route(path: '/{id}', name: 'api_facturation_delete', methods: ["DELETE"])]
    public function delete(Facturation $facturation, Request $request, DeleteService $deleteService): JsonResponse
    {
        $data = $request->toArray();
        return $deleteService->deleteEntity($facturation, $data, 'facturation');
    }
}
