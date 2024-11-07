<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class DeleteService
{
    private $entityManager;
    private $cache;
    private $security;
    private $user;
    
    public function __construct(EntityManagerInterface $entityManager, TagAwareCacheInterface $cache, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->cache = $cache;
        $this->security = $security;
        $this->user = $security->getUser();
    }

    public function deleteEntity($entity, array $data, string $cacheTag, string $statusProperty = 'status'): JsonResponse
    {
        if (isset($data['force']) && $data['force'] === true) {
            if (!$this->security->isGranted("ROLE_ADMIN")) {
                return new JsonResponse(["error" => "Erreur d'accès : Vous n'êtes pas autoriser à utiliser la méthode force"], JsonResponse::HTTP_FORBIDDEN);
            }
            $this->entityManager->remove($entity);
        } else {
            // Soft delete
            if (property_exists($entity, $statusProperty)) {
                $entity->{"set" . ucfirst($statusProperty)}("off");
                $entity->setUpdatedBy($this->user->getId());
                $this->entityManager->persist($entity);
            } else {
                return new JsonResponse(["error" => "Property '$statusProperty' not found"], JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        $this->entityManager->flush();
        $this->cache->invalidateTags([$cacheTag]);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
