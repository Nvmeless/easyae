<?php

namespace App\Serializer\Normalizer;

use App\Entity\Client;
use App\Entity\ContactLink;
use ReflectionClass;
use App\Entity\Account;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AutoDiscorveryNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private NormalizerInterface $normalizer,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function normalize($object, ?string $format = null, array $context = []): array
    {
        $data = $this->normalizer->normalize($object, $format, $context);
        $className = (new ReflectionClass($object))->getShortName();
        $className = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
        $data["_links"] = [
            "up" => [
                "path" => $this->urlGenerator->generate("api_" . $className . "_index"),
                "method" => ["GET"]
            ],
            "self" => [
                "path" => $this->urlGenerator->generate("api_" . $className . "_show", ["id" => $data["id"]]),
                "method" => ["GET"]
            ],
            "new" => [
                "path" => $this->urlGenerator->generate("api_" . $className . "_new"),
                "method" => ["POST"]
            ],
            "delete" => [
                "path" => $this->urlGenerator->generate("api_" . $className . "_delete", ["id" => $data["id"]]),
                "method" => ["DELETE"]
            ],
            "edit" => [
                $this->urlGenerator->generate("api_" . $className . "_edit", ["id" => $data["id"]]),
                "method" => ["PUT", "PATCH"]
            ],
        ];
        return $data;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        // TODO: return $data instanceof Object
        return $data instanceof Account || $data instanceof Client || $data instanceof ContactLink;
    }

    public function getSupportedTypes(?string $format): array
    {
        // TODO: return [Object::class => true];
        return [
            Account::class => true,
            Client::class => true,
            ContactLink::class => true
        ];
    }
}
