<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Service\DeleteService;

#[Route('/api/contact')]

class ContactController extends AbstractController
{
    private $user;

    public function __construct(Security $security)
    {
        $this->user = $security->getUser();
    }

    #[Route(name: 'api_contact_index', methods: ["GET"])]
    #[IsGranted("ROLE_ADMIN", message: "MESSAGE CONTACT")]

    public function getAll(ContactRepository $contactRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {

        $idCache = "getAllContacts";
        $contactJson = $cache->get($idCache, function (ItemInterface $item) use ($contactRepository, $serializer) {
            $item->tag("contact");
            $contactList = $contactRepository->findAll();
    
            $contactJson = $serializer->serialize($contactList, 'json', ['groups' => "contact"]);
            
            return $contactJson;

        });

        return new JsonResponse($contactJson, JsonResponse::HTTP_OK, [], true);
    }
    
    
    #[Route(path: '/{id}', name: 'api_contact_show', methods: ["GET"])]
    public function get(Contact $contact, SerializerInterface $serializer): JsonResponse
    {
        $contactJson = $serializer->serialize($contact, 'json', ['groups' => "contact"]);
        
        return new JsonResponse($contactJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(name: 'api_contact_new', methods: ["POST"])]
    public function create(ValidatorInterface $validator, TagAwareCacheInterface $cache, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $contact = $serializer->deserialize($request->getContent(), Contact::class, 'json');
        
        $contact->setStatus("on")
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime())
            ->setCreatedBy($this->user->getId())
            ->setUpdatedBy($this->user->getId())
        ;

        $errors = $validator->validate($contact);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $entityManager->persist($contact);
        $entityManager->flush();

        $cache->invalidateTags(["contact"]);

        $contactJson = $serializer->serialize($contact, 'json', ['groups' => "contact"]);
        return new JsonResponse($contactJson, JsonResponse::HTTP_CREATED, [], true);
    }

    #[Route(path: "/{id}", name: 'api_contact_edit', methods: ["PATCH"])]
    public function update(
        TagAwareCacheInterface $cache,
        Contact $contact,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator
    ): JsonResponse {
        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $serializer->deserialize(
            $request->getContent(),
            Contact::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $contact]
        );

        $contact->setUpdatedAt(new \DateTime())
            ->setUpdatedBy($this->user->getId())
        ;

        $entityManager->flush();
        $cache->invalidateTags(["contact"]);


        $location = $urlGenerator->generate(
            "api_contact_show",
            ['id' => $contact->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, ["Location" => $location]);
    }

    #[Route(path: "/{id}", name: 'api_contact_delete', methods: ["DELETE"])]
    public function delete(Contact $contact, Request $request, DeleteService $deleteService): JsonResponse
    {
        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $data = $request->toArray();
        return $deleteService->deleteEntity($contact, $data, 'contact');
    }
}
