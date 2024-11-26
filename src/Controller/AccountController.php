<?php

namespace App\Controller;

use App\Entity\Account;
use App\enum\EAction;
use App\enum\EService;
use App\Repository\AccountRepository;
use App\Repository\ClientRepository;

use App\Traits\HistoryTrait;

use App\Repository\InfoRepository;

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
use App\Service\DeleteService;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/api/account')]

class AccountController extends AbstractController
{


    use HistoryTrait;

    private $user;

    public function __construct(Security $security)
    {
        $this->user = $security->getUser();
    }


    #[Route(name: 'api_account_index', methods: ["GET"])]
    #[IsGranted("ROLE_ADMIN", message: "Hanhanhaaaaan vous n'avez pas dit le mot magiiiiqueeuuuuuh")]
    public function getAll(AccountRepository $accountRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $this->addHistory(EService::ACCOUNT, EAction::READ);

        $idCache = "getAllAccounts";
        $accountJson = $cache->get($idCache, function (ItemInterface $item) use ($accountRepository, $serializer) {
            $item->tag("account");
            $item->tag("client");
            $item->tag("info");
            $accountList = $accountRepository->findAll();
            $accountJson = $serializer->serialize($accountList, 'json', ['groups' => "account"]);

            return $accountJson;
        });

        return new JsonResponse($accountJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(path: '/{id}', name: 'api_account_show', methods: ["GET"])]
    public function get(Account $account, SerializerInterface $serializer): JsonResponse
    {
        $this->addHistory(EService::ACCOUNT, EAction::READ);

        $accountJson = $serializer->serialize($account, 'json', ['groups' => "account"]);
        return new JsonResponse($accountJson, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(name: 'api_account_new', methods: ["POST"])]
    public function create(ValidatorInterface $validator, TagAwareCacheInterface $cache, Request $request, ClientRepository $clientRepository, InfoRepository $infoRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {

        $this->addHistory(EService::ACCOUNT, EAction::CREATE);


        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }
        

        $data = $request->toArray();
        $client = $clientRepository->find($data["client"]);
        $info = $infoRepository->find($data["info"]);
        $account = $serializer->deserialize($request->getContent(), Account::class, 'json', []);
        $account->setClient($client)
            ->addInfo($info)
            ->setStatus("on")
            ->setCreatedBy($this->user->getId())
            ->setUpdatedBy($this->user->getId())
        ;

        $errors = $validator->validate($account);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $entityManager->persist($account);
        $entityManager->flush();
        $cache->invalidateTags(["account"]);
        $accountJson = $serializer->serialize($account, 'json', ['groups' => "account"]);
        return new JsonResponse($accountJson, JsonResponse::HTTP_CREATED, [], true);
    }

    #[Route(path: "/{id}", name: 'api_account_edit', methods: ["PATCH"])]
    public function update(TagAwareCacheInterface $cache, Account $account, UrlGeneratorInterface $urlGenerator, Request $request, ClientRepository $clientRepository, InfoRepository $infoRepository, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {

        $this->addHistory(EService::ACCOUNT, EAction::UPDATE, $account);

        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }


        $data = $request->toArray();
        if (isset($data['client'])) {
            $client = $clientRepository->find($data["client"]);
        }
        if (isset($data["info"])) {
            $info = $infoRepository->find($data["info"]);
        }


        $updatedAccount = $serializer->deserialize($request->getContent(), Account::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $account]);
        $updatedAccount
            ->setClient($client ?? $updatedAccount->getClient())
            ->addInfo($info ?? $updatedAccount->getInfo())
            ->setStatus("on")
            ->setUpdatedBy($this->user->getId())
        ;

        $entityManager->persist($updatedAccount);
        $entityManager->flush();
        $cache->invalidateTags(["account", "client", "info"]);

        $location = $urlGenerator->generate("api_account_show", ['id' => $updatedAccount->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, ["Location" => $location]);
    }

    #[Route(path: "/{id}", name: 'api_account_delete', methods: ["DELETE"])]
    public function delete(Account $account, Request $request, DeleteService $deleteService): JsonResponse
    {

        $this->addHistory(EService::ACCOUNT, EAction::DELETE);

        $data = $request->toArray();
        if (isset($data['force']) && $data['force'] === true) {
            $entityManager->remove($account);


        } else {
            $account
                ->setStatus("off")
            ;

            $entityManager->persist($account);

        if (!$this->user) {
            return new JsonResponse(['message' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);

        }

        $data = $request->toArray();

        return $deleteService->deleteEntity($account, $data, 'account');
    }
}
