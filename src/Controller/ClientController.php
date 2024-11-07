<?php

namespace App\Controller;

use App\Entity\Client;
use App\Repository\ClientRepository;
use App\Repository\ContactRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route('/api/client')]
class ClientController extends AbstractController
{
    #[Route(name: 'api_client_index', methods: ["GET"])]
    public function getAll(ClientRepository $clientRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $idCache = "getAllClients";

        $clientJson = $cache->get($idCache, function (ItemInterface $item) use ($clientRepository, $serializer) {
            $item->tag("client");

            $clientList = $clientRepository->findAll();
            return $serializer->serialize($clientList, 'json', ['groups' => ["client", "clientType"]]);
        });

        return new JsonResponse($clientJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(path: '/{id}', name: 'api_client_show', methods: ["GET"])]
    public function get(Client $client = null, SerializerInterface $serializer): JsonResponse
    {
        if (!$client) {
            return new JsonResponse(['error' => 'Client not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $clientJson = $serializer->serialize($client, 'json', ['groups' => ["client"]]);
        return new JsonResponse($clientJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(path: '/{id}/carnet-contact', name: 'api_client_carnet_contact', methods: ["GET"])]
    public function getCarnetcontact(Client $client = null, SerializerInterface $serializer, ContactRepository $contactRepository) {
        $contactsList = $contactRepository->findBy(['client' => $client->getId()]);
        $contactsJson = $serializer->serialize($contactsList, 'json', ['groups' => ["contact"]]);

        return new JsonResponse($contactsJson, JsonResponse::HTTP_OK, [], true);
    }


    #[Route(name: 'api_client_new', methods: ["POST"])]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $client = $serializer->deserialize($request->getContent(), Client::class, 'json');
        if (!$client) {
            return new JsonResponse(['error' => 'Invalid data'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (is_null($client->getStatus())) {
            $client->setStatus("on");
        }

        $contact = $client->getContact();
        if ($contact) {
            $entityManager->persist($contact);
        }

        $entityManager->persist($client);
        $entityManager->flush();

        $cache->invalidateTags(["client"]);

        $clientJson = $serializer->serialize($client, 'json', ['groups' => "client"]);
        return new JsonResponse($clientJson, JsonResponse::HTTP_CREATED, [], true);
    }

    #[Route(path: "/{id}", name: 'api_client_edit', methods: ["PATCH"])]
    public function update(Client $client, UrlGeneratorInterface $urlGenerator, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $updatedClient = $serializer->deserialize($request->getContent(), Client::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $client]);
        $updatedClient->setStatus("on");

        $entityManager->persist($updatedClient);
        $entityManager->flush();

        $cache->invalidateTags(["client"]);

        $location = $urlGenerator->generate("api_client_show", ['id' => $updatedClient->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, ["Location" => $location]);
    }

    #[Route(path: "/{id}", name: 'api_client_delete', methods: ["DELETE"])]
    public function delete(Client $client, Request $request, EntityManagerInterface $entityManager, TagAwareCacheInterface $cache): JsonResponse
    {
        $data = $request->toArray();
        if (isset($data['force']) && $data['force'] === true) {
            if (!$this->isGranted("ROLE_ADMIN")) {
                return new JsonResponse(["error" => "Hanhanhaaaaan vous n'avez pas dit le mot magiiiiqueeuuuuuh"], JsonResponse::HTTP_FORBIDDEN);
            }
            $entityManager->remove($client);
        } else {
            $client->setStatus("off");
            $entityManager->persist($client);
        }
        $entityManager->flush();
        $cache->invalidateTags(["client"]);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }    
}
