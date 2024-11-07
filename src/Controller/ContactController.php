<?php

namespace App\Controller;

use App\Entity\Contact;
use App\enum\EAction;
use App\enum\EService;
use App\Repository\ContactRepository;
use App\Traits\HistoryTrait;
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

#[Route('/api/contact')]
class ContactController extends AbstractController
{

    use HistoryTrait;

    #[Route(name: 'api_contact_index', methods: ["GET"])]
    #[IsGranted("ROLE_ADMIN", message: "MESSAGE CONTACT")]
    public function getAll(ContactRepository $contactRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $this->addHistory(EService::CONTACT, EAction::READ);

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
        $this->addHistory(EService::CONTACT, EAction::READ);

        $contactJson = $serializer->serialize($contact, 'json', ['groups' => "contact"]);

        return new JsonResponse($contactJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(name: 'api_contact_new', methods: ["POST"])]
    public function create(ValidatorInterface $validator, TagAwareCacheInterface $cache, Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->addHistory(EService::CONTACT, EAction::CREATE);

        $contact = $serializer->deserialize($request->getContent(), Contact::class, 'json');

        $contact->setStatus("on")
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime());

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
        Contact                $contact,
        Request                $request,
        SerializerInterface    $serializer,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface  $urlGenerator
    ): JsonResponse
    {
        $this->addHistory(EService::CONTACT, EAction::UPDATE, $contact);

        $serializer->deserialize(
            $request->getContent(),
            Contact::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $contact]
        );

        $contact->setUpdatedAt(new \DateTime());
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
    public function delete(TagAwareCacheInterface $cache, Contact $contact, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->addHistory(EService::CONTACT, EAction::DELETE, $contact);

        $data = $request->toArray();

        if (isset($data['force']) && $data['force'] === true) {
            $entityManager->remove($contact);

        } else {
            $contact
                ->setStatus("off");
            $entityManager->persist($contact);
        }

        $entityManager->flush();
        $cache->invalidateTags(["contact"]);


        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
