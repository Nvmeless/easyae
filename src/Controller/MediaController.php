<?php

namespace App\Controller;

use App\Entity\Media;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MediaController extends AbstractController
{
    #[Route('/', name: 'app_media')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/MediaController.php',
        ]);
    }
    #[Route('/api/media/{media}', name: 'app_media_show')]
    public function show(Media $media, UrlGeneratorInterface $urlGenerator, SerializerInterface $serializer): JsonResponse
    {
        // $location = $media->getPublicPath() . "/" . $media->getRealPath();
        $location = $urlGenerator->generate("app_media", [], UrlGeneratorInterface::ABSOLUTE_URL);
        $location = $location . str_replace("/public/", "", $media->getPublicPath() . "/" . $media->getRealPath());
        return $media ?
            new JsonResponse($serializer->serialize($media, 'json', []), Response::HTTP_OK, ["Location" => $location], true) : new JsonResponse(null, Response::HTTP_NOT_FOUND);

    }
    #[Route('/api/media', name: 'app_media_new', methods: ["POST"])]
    public function new(Request $request, SerializerInterface $serializer, UrlGeneratorInterface $urlGenerator, EntityManagerInterface $entityManager): JsonResponse
    {
        $media = new Media();
        $file = $request->files->get('file');
        $media->setFile($file);
        $media->setName($file->getClientOriginalName());
        $media->setRealName($file->getClientOriginalName());
        $media->setPublicPath('media');


        $entityManager->persist($media);
        $entityManager->flush();

        $jsonFile = $serializer->serialize($media, "json");
        $location = $urlGenerator->generate('app_media', ["media" => $media->getId(), UrlGeneratorInterface::ABSOLUTE_URL]);
        return new JsonResponse($jsonFile, Response::HTTP_CREATED, ["Location" => $location], true);
    }
}
