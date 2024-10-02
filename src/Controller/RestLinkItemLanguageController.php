<?php

namespace App\Controller;

use App\Entity\LinkItemLanguage;
use App\Model\OrderByDto;
use App\Repository\LanguageRepository;
use App\Repository\LinkItemLanguageRepository;
use App\Repository\LinkRepository;
use App\Repository\WebsiteRepository;
use App\Util\UrlQuery;
use App\Util\Href;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute as HttpKernel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Routing\Attribute as Routing;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Routing\Route(
    path: '/api/rest/links/{linkHref}/item-languages',
    name: 'rest_link_item_language_'
)]
class RestLinkItemLanguageController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly LinkItemLanguageRepository $linkItemLanguageRepository,
        private readonly LinkRepository $linkRepository,
        private readonly WebsiteRepository $websiteRepository,
        private readonly LanguageRepository $languageRepository
    ) {}

    #[Routing\Route('', name: 'list', methods: [Request::METHOD_GET])]
    public function list(
        Request $request,
        string $linkHref,
        #[HttpKernel\MapQueryParameter(options: ['min_range' => 1])] int $page = 1,
        #[HttpKernel\MapQueryParameter(options: ['min_range' => 1, 'max_range' => 30])] int $limit = 10,
        #[HttpKernel\MapQueryParameter] string $order = null
    ): Response {
        $pathParams0 = new Href($linkHref);
        $queries = new UrlQuery($request->server->get('QUERY_STRING'));

        $criteria = [];
        $criteria['linkWebsiteHosts'] = [$pathParams0->getHost()];
        $criteria['linkRelativeReferences'] = [$pathParams0->getRelativeReference() ?? ''];
        $orderBy = \array_map([OrderByDto::class, 'parse'], $queries->all('orderBy', 'orderBys'));
        if ($order != null) {
            \array_unshift($orderBy, new OrderByDto('languageLang', $order));
        }
        $offset = $limit * ($page - 1);

        $result = $this->linkItemLanguageRepository->findByCustom($criteria, $orderBy, $limit, $offset);

        $headers = [];
        $headers['X-Total-Count'] = $this->linkItemLanguageRepository->countCustom($criteria);
        $headers['X-Pagination-Limit'] = $limit;

        $response = $this->json($result, Response::HTTP_OK, $headers, ['groups' => ['link']]);

        $response->setEtag(\crc32($response->getContent()));
        foreach ($result as $v) {
            $aLastModified = $response->getLastModified();
            $bLastModified = $v->getUpdatedAt() ?? $v->getCreatedAt();
            if (!$aLastModified || $aLastModified < $bLastModified) {
                $response->setLastModified($bLastModified);
            }
        }
        $response->setPublic();
        if ($response->isNotModified($request)) return $response;

        return $response;
    }

    #[Routing\Route('', name: 'post', methods: [Request::METHOD_POST])]
    public function post(
        Request $request,
        string $linkHref
    ): Response {
        $pathParams0 = new Href($linkHref);
        $parent = $this->linkRepository->findOneBy([
            'website' => $this->websiteRepository->findOneBy(['host' => $pathParams0->getHost()]),
            'relativeReference' => $pathParams0->getRelativeReference() ?? ''
        ]);
        if (!$parent) throw new BadRequestException('Comic does not exists.');
        $result = new LinkItemLanguage();
        switch ($request->headers->get('Content-Type')) {
            case 'application/json':
                $content = \json_decode($request->getContent(), true);
                if (isset($content['languageLang'])) {
                    $r1 = $this->languageRepository->findOneBy(['lang' => $content['languageLang']]);
                    if (!$r1) throw new BadRequestException('Language does not exists.');
                    $result->setLanguage($r1);
                }
                if (isset($content['machineTranslate'])) $result->setMachineTranslate($content['machineTranslate']);
                break;
            default:
                throw new UnsupportedMediaTypeHttpException();
        }
        $result->setLink($parent);
        $resultViolation = $this->validator->validate($result);
        if (\count($resultViolation) > 0) throw new ValidationFailedException($result, $resultViolation);
        $this->entityManager->persist($result);
        $this->entityManager->flush();

        $headers = [];
        $headers['Location'] = $this->generateUrl('rest_link_item_language_get', [
            'linkHref' => \rawurlencode($result->getLinkWebsiteHost() . $result->getLinkRelativeReference()),
            'lang' => $result->getLanguageLang()
        ]);

        return $this->json($result, Response::HTTP_CREATED, $headers, ['groups' => ['link']]);
    }

    #[Routing\Route('/{lang}', name: 'get', methods: [Request::METHOD_GET])]
    public function get(
        Request $request,
        string $linkHref,
        string $lang
    ): Response {
        $pathParams0 = new Href($linkHref);
        $result = $this->linkItemLanguageRepository->findOneBy([
            'link' => $this->linkRepository->findOneBy([
                'website' => $this->websiteRepository->findOneBy(['host' => $pathParams0->getHost()]),
                'relativeReference' => $pathParams0->getRelativeReference() ?? ''
            ]),
            'language' => $this->languageRepository->findOneBy(['lang' => $lang])
        ]);
        if (!$result) throw new NotFoundHttpException('Link Item Language not found.');

        $response = $this->json($result, Response::HTTP_OK, [], ['groups' => ['link']]);

        $response->setEtag(\crc32($response->getContent()));
        $response->setLastModified($result->getUpdatedAt() ?? $result->getCreatedAt());
        $response->setPublic();
        if ($response->isNotModified($request)) return $response;

        return $response;
    }

    #[Routing\Route('/{lang}', name: 'patch', methods: [Request::METHOD_PATCH])]
    public function patch(
        Request $request,
        string $linkHref,
        string $lang
    ): Response {
        $pathParams0 = new Href($linkHref);
        $result = $this->linkItemLanguageRepository->findOneBy([
            'link' => $this->linkRepository->findOneBy([
                'website' => $this->websiteRepository->findOneBy(['host' => $pathParams0->getHost()]),
                'relativeReference' => $pathParams0->getRelativeReference() ?? ''
            ]),
            'language' => $this->languageRepository->findOneBy(['lang' => $lang])
        ]);
        if (!$result) throw new NotFoundHttpException('Link Item Language not found.');
        switch ($request->headers->get('Content-Type')) {
            case 'application/json':
                $content = \json_decode($request->getContent(), true);
                if (isset($content['languageLang'])) {
                    $r1 = $this->languageRepository->findOneBy(['lang' => $content['languageLang']]);
                    if (!$r1) throw new BadRequestException('Language does not exists.');
                    $result->setLanguage($r1);
                }
                if (isset($content['machineTranslate'])) $result->setMachineTranslate($content['machineTranslate']);
                break;
            default:
                throw new UnsupportedMediaTypeHttpException();
        }
        $resultViolation = $this->validator->validate($result);
        if (\count($resultViolation) > 0) throw new ValidationFailedException($result, $resultViolation);
        $this->entityManager->flush();

        $headers = [];
        $headers['Location'] = $this->generateUrl('rest_link_item_language_get', [
            'linkHref' => \rawurlencode($result->getLinkWebsiteHost() . $result->getLinkRelativeReference()),
            'lang' => $result->getLanguageLang()
        ]);

        return $this->json($result, Response::HTTP_OK, $headers, ['groups' => ['link']]);
    }

    #[Routing\Route('/{lang}', name: 'delete', methods: [Request::METHOD_DELETE])]
    public function delete(
        string $linkHref,
        string $lang
    ): Response {
        $pathParams0 = new Href($linkHref);
        $result = $this->linkItemLanguageRepository->findOneBy([
            'link' => $this->linkRepository->findOneBy([
                'website' => $this->websiteRepository->findOneBy(['host' => $pathParams0->getHost()]),
                'relativeReference' => $pathParams0->getRelativeReference() ?? ''
            ]),
            'language' => $this->languageRepository->findOneBy(['lang' => $lang])
        ]);
        if (!$result) throw new NotFoundHttpException('Link Item Language not found.');
        $this->entityManager->remove($result);
        $this->entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
