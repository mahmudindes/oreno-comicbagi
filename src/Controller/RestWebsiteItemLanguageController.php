<?php

namespace App\Controller;

use App\Entity\WebsiteItemLanguage;
use App\Model\OrderByDto;
use App\Repository\LanguageRepository;
use App\Repository\WebsiteItemLanguageRepository;
use App\Repository\WebsiteRepository;
use App\Util\UrlQuery;
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
    path: '/api/rest/websites/{websiteHost}/item-languages',
    name: 'rest_website_item_language_'
)]
class RestWebsiteItemLanguageController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly WebsiteItemLanguageRepository $websiteItemLanguageRepository,
        private readonly WebsiteRepository $websiteRepository,
        private readonly LanguageRepository $languageRepository
    ) {}

    #[Routing\Route('', name: 'list', methods: [Request::METHOD_GET])]
    public function list(
        Request $request,
        string $websiteHost,
        #[HttpKernel\MapQueryParameter(options: ['min_range' => 1])] int $page = 1,
        #[HttpKernel\MapQueryParameter(options: ['min_range' => 1, 'max_range' => 30])] int $limit = 10,
        #[HttpKernel\MapQueryParameter] string $order = null
    ): Response {
        $queries = new UrlQuery($request->server->get('QUERY_STRING'));

        $criteria = [];
        $criteria['websiteHosts'] = [$websiteHost];
        $orderBy = \array_map([OrderByDto::class, 'parse'], $queries->all('orderBy', 'orderBys'));
        if ($order != null) {
            \array_unshift($orderBy, new OrderByDto('languageLang', $order));
        }
        $offset = $limit * ($page - 1);

        $result = $this->websiteItemLanguageRepository->findByCustom($criteria, $orderBy, $limit, $offset);

        $headers = [];
        $headers['X-Total-Count'] = $this->websiteItemLanguageRepository->countCustom($criteria);
        $headers['X-Pagination-Limit'] = $limit;

        $response = $this->json($result, Response::HTTP_OK, $headers, ['groups' => ['website']]);

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
        string $websiteHost
    ): Response {
        $parent = $this->websiteRepository->findOneBy(['host' => $websiteHost]);
        if (!$parent) throw new BadRequestException('Website does not exists.');
        $result = new WebsiteItemLanguage();
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
        $result->setWebsite($parent);
        $resultViolation = $this->validator->validate($result);
        if (\count($resultViolation) > 0) throw new ValidationFailedException($result, $resultViolation);
        $this->entityManager->persist($result);
        $this->entityManager->flush();

        $headers = [];
        $headers['Location'] = $this->generateUrl('rest_website_item_language_get', [
            'websiteHost' => $result->getWebsiteHost(),
            'lang' => $result->getLanguageLang()
        ]);

        return $this->json($result, Response::HTTP_CREATED, $headers, ['groups' => ['website']]);
    }

    #[Routing\Route('/{lang}', name: 'get', methods: [Request::METHOD_GET])]
    public function get(
        Request $request,
        string $websiteHost,
        string $lang
    ): Response {
        $result = $this->websiteItemLanguageRepository->findOneBy([
            'website' => $this->websiteRepository->findOneBy(['host' => $websiteHost]),
            'language' => $this->languageRepository->findOneBy(['lang' => $lang])
        ]);
        if (!$result) throw new NotFoundHttpException('Website Item Language not found');

        $response = $this->json($result, Response::HTTP_OK, [], ['groups' => ['website']]);

        $response->setEtag(\crc32($response->getContent()));
        $response->setLastModified($result->getUpdatedAt() ?? $result->getCreatedAt());
        $response->setPublic();
        if ($response->isNotModified($request)) return $response;

        return $response;
    }

    #[Routing\Route('/{lang}', name: 'patch', methods: [Request::METHOD_PATCH])]
    public function patch(
        Request $request,
        string $websiteHost,
        string $lang
    ): Response {
        $result = $this->websiteItemLanguageRepository->findOneBy([
            'website' => $this->websiteRepository->findOneBy(['host' => $websiteHost]),
            'language' => $this->languageRepository->findOneBy(['lang' => $lang])
        ]);
        if (!$result) throw new NotFoundHttpException('Website Item Language not found.');
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
        $headers['Location'] = $this->generateUrl('rest_website_item_language_get', [
            'websiteHost' => $result->getWebsiteHost(),
            'lang' => $result->getLanguageLang()
        ]);

        return $this->json($result, Response::HTTP_OK, $headers, ['groups' => ['website']]);
    }

    #[Routing\Route('/{lang}', name: 'delete', methods: [Request::METHOD_DELETE])]
    public function delete(
        string $websiteHost,
        string $lang
    ): Response {
        $result = $this->websiteItemLanguageRepository->findOneBy([
            'website' => $this->websiteRepository->findOneBy(['host' => $websiteHost]),
            'language' => $this->languageRepository->findOneBy(['lang' => $lang])
        ]);
        if (!$result) throw new NotFoundHttpException('Website Item Language not found.');
        $this->entityManager->remove($result);
        $this->entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
