<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Web;

use App\Application\Public\PublicTributeQueryService;
use App\Application\Public\PublicTributeSerializer;
use App\Domain\Tribute\Exception\TributeException;
use App\Infrastructure\Public\PublicTributeHtmlRenderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('', name: 'web_')]
final class PublicTributePageController extends AbstractController
{
    public function __construct(
        private readonly PublicTributeQueryService $queryService,
        private readonly PublicTributeSerializer $serializer,
        private readonly PublicTributeHtmlRenderer $htmlRenderer,
        private readonly HttpClientInterface $httpClient,
        private readonly string $frontendUrl,
    ) {
    }

    #[Route('/h/{slug}', name: 'public_tribute_page', methods: ['GET'])]
    public function show(string $slug, Request $request): Response
    {
        try {
            $tribute = $this->queryService->getPublishedBySlug($slug);
        } catch (TributeException $exception) {
            if ($exception->getStatusCode() === 404) {
                return new Response('Homenagem não encontrada.', Response::HTTP_NOT_FOUND);
            }

            throw $exception;
        }

        $canonicalUrl = rtrim($request->getSchemeAndHttpHost(), '/').'/h/'.$tribute->getSlug();
        $serialized = $this->serializer->serialize($tribute);
        $ogImageUrl = isset($serialized['og_image_url']) ? (string) $serialized['og_image_url'] : null;

        $frontendHtml = null;
        try {
            $frontendHtml = $this->httpClient->request('GET', rtrim($this->frontendUrl, '/').'/', [
                'timeout' => 3,
                'headers' => ['Accept' => 'text/html'],
            ])->getContent();
        } catch (\Throwable) {
            // Em desenvolvimento, o fallback continua apontando para o entrypoint do Vite.
        }

        $html = $this->htmlRenderer->render($tribute, $canonicalUrl, $this->frontendUrl, $ogImageUrl, $frontendHtml);

        return new Response($html, Response::HTTP_OK, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' https: data:; connect-src 'self'; frame-ancestors 'none';",
        ]);
    }
}
