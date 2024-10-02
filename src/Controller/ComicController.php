<?php

namespace App\Controller;

use App\Model\OrderByDto;
use App\Repository\ComicRepository;
use App\Service\ComicBagiApp;
use App\Service\ComicKingApi;
use App\Util\UrlQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute as HttpKernel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute as Routing;

#[Routing\Route('/comics', name: 'app_comic_')]
class ComicController extends AbstractController
{
    public function __construct(
        private readonly ComicRepository $comicRepository,
        private readonly ComicBagiApp $comicBagiApp,
        private readonly ComicKingApi $comicKingApi
    ) {}

    #[Routing\Route('/', name: 'index')]
    #[HttpKernel\Cache(public: true, maxage: 1800, mustRevalidate: true)]
    public function index(
        Request $request,
        #[HttpKernel\MapQueryParameter(options: ['min_range' => 1])] int $page = 1,
        #[HttpKernel\MapQueryParameter(options: ['min_range' => 1, 'max_range' => 15])] int $limit = 10,
        #[HttpKernel\MapQueryParameter] string $type = ''
    ): Response {
        $queryString = $request->server->get('QUERY_STRING');
        $queries = new UrlQuery($queryString);

        return $this->render('comic/index.html.twig', [
            'rawParameter' => $queryString,
            'paramPage' => $page,
            'paramLimit' => $limit,
            'paramOrderBy' => $queries->all('orderBy'),
            'paramType' => $type,
            'resultCount' => $this->comicRepository->countCustom([])
        ]);
    }

    #[Routing\Route('/{code}', name: 'individual')]
    #[HttpKernel\Cache(public: true, maxage: 3600, mustRevalidate: true)]
    public function individual(
        Request $request,
        string $code,
        #[HttpKernel\MapQueryParameter(options: ['min_range' => 1])] int $chapterPage = 1
    ): Response {
        $queryString = $request->server->get('QUERY_STRING');

        $result = $this->comicRepository->findOneBy(['code' => $code]);
        if (!$result) throw new NotFoundHttpException('Comic not found.');

        return $this->render('comic/individual.html.twig', [
            'rawParameter' => $queryString,
            'paramCode' => $code,
            'paramChapterPage' => $chapterPage,
            'result' => $result,
            'funcComicBagiApp' => $this->comicBagiApp,
            'funcComicKingApi' => $this->comicKingApi
        ]);
    }

    #[HttpKernel\Cache(public: true, maxage: 900, mustRevalidate: true)]
    public function fragmentList(
        int $limit,
        int $page,
        array $orderBy = null,
        string $type = null
    ): Response {
        $result = $this->comicRepository->findByCustom(
            [],
            \array_map([OrderByDto::class, 'parse'], $orderBy),
            $limit,
            $limit * ($page - 1)
        );

        return $this->render('comic/_list.html.twig', [
            'paramLimit' => $limit,
            'paramPage' => $page,
            'paramOrderBy' => $orderBy,
            'paramType' => $type,
            'result' => $result,
            'funcComicBagiApp' => $this->comicBagiApp,
            'funcComicKingApi' => $this->comicKingApi
        ]);
    }

    #[HttpKernel\Cache(public: true, maxage: 900, mustRevalidate: true)]
    public function fragmentWidget(
        int $limit,
        array $orderBy = null
    ): Response {
        $result = $this->comicRepository->findByCustom(
            [],
            \array_map([OrderByDto::class, 'parse'], $orderBy),
            $limit,
            null
        );

        return $this->render('comic/_widget.html.twig', [
            'paramLimit' => $limit,
            'paramOrderBy' => $orderBy,
            'result' => $result,
            'funcComicBagiApp' => $this->comicBagiApp,
            'funcComicKingApi' => $this->comicKingApi
        ]);
    }
}
