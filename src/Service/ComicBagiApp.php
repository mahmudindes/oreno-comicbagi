<?php

namespace App\Service;

use App\Model\OrderByDto;
use App\Repository\ComicChapterDestinationLinkRepository;
use App\Repository\ComicChapterRepository;
use App\Repository\ComicDestinationLinkRepository;
use App\Repository\LanguageRepository;
use App\Repository\LinkItemLanguageRepository;
use App\Repository\LinkRepository;
use App\Repository\WebsiteItemLanguageRepository;

class ComicBagiApp
{
    public function __construct(
        private readonly LanguageRepository $languageRepository,
        private readonly WebsiteItemLanguageRepository $websiteItemLanguageRepository,
        private readonly LinkRepository $linkRepository,
        private readonly LinkItemLanguageRepository $linkItemLanguageRepository,
        private readonly ComicDestinationLinkRepository $comicDestinationLinkRepository,
        private readonly ComicChapterRepository $comicChapterRepository,
        private readonly ComicChapterDestinationLinkRepository $comicChapterDestinationLinkRepository
    ) {}

    public function getLanguages(
        ?int $limit = null
    ): array {
        return $this->languageRepository->findByCustom(
            [],
            [],
            $limit
        );
    }

    public function getWebsiteItemLanguages(
        string $host,
        ?int $limit = null
    ): array {
        return $this->websiteItemLanguageRepository->findByCustom(
            ['websiteHosts' => [$host]],
            [],
            $limit
        );
    }

    public function getLinkItemLanguages(
        string $websiteHost,
        ?string $relativeReference,
        ?int $limit = null
    ): array {
        return $this->linkItemLanguageRepository->findByCustom(
            [
                'linkWebsiteHosts' => [$websiteHost],
                'linkRelativeReferences' => [$relativeReference ?? '']
            ],
            [],
            $limit
        );
    }

    public function getLinksByComic(
        string $comicCode,
        ?array $langs = [],
        ?int $limit = null
    ): array {
        if (!$langs) {
            $langs = ['en'];
        }

        $result0 = $this->comicDestinationLinkRepository->findByCustom(
            ['comicCodes' => [$comicCode]],
            [
                new OrderByDto('linkItemLanguageLang', custom: [
                    'prefer' => \implode('+', $langs)
                ]),
                new OrderByDto('linkWebsiteItemLanguageLang', custom: [
                    'prefer' => \implode('+', $langs)
                ]),
                new OrderByDto('linkWebsiteName'),
                new OrderByDto('linkWebsiteHost'),
                new OrderByDto('linkRelativeReference')
            ],
            $limit
        );

        $result1 = $this->linkRepository->findByCustom(
            ['hrefs' => \array_map(function ($val) {
                return $val->getLinkWebsiteHost() . $val->getLinkRelativeReference();
            }, $result0)],
            [],
            null
        );

        $result = [];
        foreach ($result0 as $val0) {
            foreach ($result1 as $val1) {
                if ($val1->getWebsiteHost() != $val0->getLinkWebsiteHost()) {
                    continue;
                }

                if ($val1->getRelativeReference() != $val0->getLinkRelativeReference()) {
                    continue;
                }

                \array_push($result, $val1);
                break;
            }
        }
        return $result;
    }

    public function getLinksByComicChapter(
        string $comicCode,
        string $chapterNumber,
        ?string $chapterVersion,
        ?array $langs = [],
        ?int $limit = null
    ): array {
        if (!$langs) {
            $langs = ['en'];
        }

        $result0 = $this->comicChapterDestinationLinkRepository->findByCustom(
            [
                'chapterComicCodes' => [$comicCode],
                'chapterNumbers' => [$chapterNumber],
                'chapterVersions' => [$chapterVersion ?? '']
            ],
            [
                new OrderByDto('linkItemLanguageLang', custom: [
                    'prefer' => \implode('+', $langs)
                ]),
                new OrderByDto('linkWebsiteItemLanguageLang', custom: [
                    'prefer' => \implode('+', $langs)
                ]),
                new OrderByDto('linkWebsiteName'),
                new OrderByDto('linkWebsiteHost'),
                new OrderByDto('linkRelativeReference')
            ],
            $limit
        );

        $result1 = $this->linkRepository->findByCustom(
            ['hrefs' => \array_map(function ($val) {
                return $val->getLinkWebsiteHost() . $val->getLinkRelativeReference();
            }, $result0)],
            [],
            null
        );

        $result = [];
        foreach ($result0 as $val0) {
            foreach ($result1 as $val1) {
                if ($val1->getWebsiteHost() != $val0->getLinkWebsiteHost()) {
                    continue;
                }

                if ($val1->getRelativeReference() != $val0->getLinkRelativeReference()) {
                    continue;
                }

                \array_push($result, $val1);
                break;
            }
        }
        return $result;
    }

    public function getComicChapters(
        string $comicCode,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        return $this->comicChapterRepository->findByCustom(
            ['comicCodes' => [$comicCode]],
            [
                new OrderByDto('number', 'desc'),
                new OrderByDto('version')
            ],
            $limit,
            $offset
        );
    }

    public function getRecommendedLangs(
        array $curLangs,
        array $priLangs
    ): array {
        $recLangs = [];

        foreach ($priLangs as $lang) {
            if (!\in_array($lang, $curLangs)) {
                continue;
            }

            \array_push($recLangs, $lang);
        }

        foreach ($priLangs as $lang) {
            if (!\str_contains($lang, '-')) {
                continue;
            }

            $langs = [];

            $langc = '';
            foreach (\explode('-', $lang) as $langPart) {
                if ($langc) {
                    $langc .= '-';
                }

                $langc .= $langPart;

                if (!\in_array($langc, $recLangs) && \in_array($langc, $curLangs)) {
                    \array_push($langs, $langc);
                }
            }

            \array_push($recLangs, ...\array_reverse($langs));
        }

        return $recLangs;
    }

    public function getHREF(
        string $websiteHost,
        ?string $relativeReference = null
    ): string {
        $href = '';

        if ($websiteHost) {
            $href .= '//' . $websiteHost;
        }

        return $href . $relativeReference;
    }
}
