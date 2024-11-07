<?php

namespace App\Traits;

use App\Entity\Action;
use App\Entity\History;
use App\Entity\Service;
use App\enum\EAction;
use App\enum\EService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

trait HistoryTrait
{
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    public function addHistory(EService $service, EAction $action, object $old = null): void
    {
        $serviceRepository = $this->entityManager->getRepository(Service::class);
        $actionRepository = $this->entityManager->getRepository(Action::class);

        $history = new History();
        $actualDate = new \DateTime();
        $serviceToLink = $serviceRepository->findOneBy(['name' => $service->value]);
        $actionToLink = $actionRepository->find($action->value);

        $history
            ->setCreatedAt($actualDate)
            ->setUpdatedAt($actualDate)
            ->setStatus("on")
            ->setOldValue($this->serializer->normalize($old, null))
            ->setService($serviceToLink)
            ->setAction($actionToLink);

        $this->entityManager->persist($history);
        $this->entityManager->flush();
    }
}
