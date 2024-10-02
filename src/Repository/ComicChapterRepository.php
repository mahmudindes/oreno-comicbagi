<?php

namespace App\Repository;

use App\Entity\ComicChapter;
use App\Model\OrderByDto;
use App\Util\Href;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ComicChapter>
 */
class ComicChapterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComicChapter::class);
    }

    public function findByCustom(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $query = $this->createQueryBuilder('c')
            ->leftJoin('c.comic', 'cc')->addSelect('cc');

        $q1 = false;
        $q1Func = function (bool &$c, QueryBuilder &$q): void {
            if ($c) return;
            $q->leftJoin('c.destinationLinks', 'cd2');
            $c = true;
        };
        $q11 = false;
        $q11Func = function (bool &$c, QueryBuilder &$q): void {
            if ($c) return;
            $q->leftJoin('cd2.link', 'cd2l');
            $c = true;
        };
        $q111 = false;
        $q111Func = function (bool &$c, QueryBuilder &$q): void {
            if ($c) return;
            $q->leftJoin('cd2l.website', 'cd2lw');
            $c = true;
        };

        $qZ = false;
        $qZFunc = function (bool &$c, QueryBuilder &$q): void {
            if ($c) return;
            $q->addGroupBy('c.comic', 'c.number', 'c.version');
            $c = true;
        };

        foreach ($criteria as $key => $val) {
            $val = \array_unique($val);

            switch ($key) {
                case 'comicCodes':
                    $c = \count($val);
                    if ($c < 1) break;

                    if ($c == 1) {
                        $query->andWhere('cc.code = :comicCode');
                        $query->setParameter('comicCode', $val[0]);
                        break;
                    }
                    $query->andWhere('cc.code IN (:comicCodes)');
                    $query->setParameter('comicCodes', $val);
                    break;
                case 'destinationLinks':
                    $c = \count($val);
                    if ($c < 1) break;

                    $q1Func($q1, $query);
                    $qZFunc($qZ, $query);

                    $w = [];
                    foreach ($val as $v) {
                        foreach ($v as $key => $val) {
                            if (!isset($w[$key])) {
                                $w[$key] = [];
                            }
                            \array_push($w[$key], ...$val);
                        }
                    }
                    if (\count($w) < 1) break;

                    foreach ($w as $key => $val) {
                        switch ($key) {
                            case 'linkWebsiteHosts':
                                $c = \count($val);
                                if ($c < 1) break;

                                $q11Func($q11, $query);
                                $q111Func($q111, $query);

                                if ($c == 1) {
                                    $query->andWhere('cd2lw.host = :linkWebsiteHost');
                                    $query->setParameter('linkWebsiteHost', $val[0]);
                                    break;
                                }
                                $query->andWhere('cd2lw.host IN (:linkWebsiteHosts)');
                                $query->setParameter('linkWebsiteHosts', $val);
                                break;
                            case 'linkRelativeReferences':
                                $c = \count($val);
                                if ($c < 1) break;

                                $q11Func($q11, $query);

                                foreach ($val as $k => $v) {
                                    switch ($v) {
                                        case null:
                                            $val[$k] = '';
                                            break;
                                        case '':
                                            $val[$k] = null;
                                            break;
                                    }
                                }

                                if ($c == 1) {
                                    $query->andWhere('cd2l.relativeReference = :linkRelativeReference');
                                    $query->setParameter('linkRelativeReference', $val[0]);
                                    break;
                                }
                                $query->andWhere('cd2l.relativeReference IN (:linkRelativeReferences)');
                                $query->setParameter('linkRelativeReferences', $val);
                                break;
                            case 'linkHREFs':
                                $c = \count($val);
                                if ($c < 1) break;

                                $q11Func($q11, $query);
                                $q111Func($q111, $query);

                                $qExOr = $query->expr()->orX();
                                foreach ($val as $k => $v) {
                                    $href = new Href($v);

                                    $qExOr->add('cd2lw.host = :linkHREFA' . $k . ' AND ' . 'cd2l.relativeReference = :linkHREFB' . $k);
                                    $query->setParameter('linkHREFA' . $k, $href->getHost());
                                    $query->setParameter('linkHREFB' . $k, $href->getRelativeReference() ?? '');
                                }
                                $query->andWhere($qExOr);
                                break;
                        }
                    }
                    break;
            }
        }

        if ($orderBy) {
            foreach ($orderBy as $key => $val) {
                if (!($val instanceof OrderByDto)) continue;

                if ($key > 5) break;

                switch ($val->name) {
                    case 'comicCode':
                        $val->name = 'cc.code';
                        break;
                    case 'createdAt':
                    case 'updatedAt':
                    case 'number':
                    case 'version':
                        $val->name = 'c.' . $val->name;
                        break;
                    default:
                        continue 2;
                }

                switch (\strtolower($val->order ?? '')) {
                    case 'a':
                    case 'asc':
                    case 'ascending':
                        $val->order = 'ASC';
                        break;
                    case 'd':
                    case 'desc':
                    case 'descending':
                        $val->order = 'DESC';
                        break;
                    default:
                        $val->order = null;
                }

                switch (\strtolower($val->nulls ?? '')) {
                    case 'f':
                    case 'first':
                        $val->nulls = 'DESC';
                        break;
                    case 'l':
                    case 'last':
                        $val->nulls = 'ASC';
                        break;
                    default:
                        $val->nulls = null;
                }

                if ($val->nulls) {
                    $vname = \str_replace('.', '', $val->name . $key);
                    $vselc = '(CASE WHEN ' . $val->name . ' IS NULL THEN 1 ELSE 0 END) AS HIDDEN ' . $vname;

                    $query->addSelect($vselc);
                    $query->addOrderBy($vname, $val->nulls);
                }

                $query->addOrderBy($val->name, $val->order);
            }
        } else {
            $query->orderBy('c.number');
            $query->orderBy('c.version');
        }

        $query->setMaxResults($limit);
        $query->setFirstResult($offset);

        return $query->setCacheable(true)->getQuery()->getResult();
    }

    public function countCustom(array $criteria = []): int
    {
        $query = $this->createQueryBuilder('c')
            ->select('count(c.id)');

        $q01 = false;
        $q01Func = function (bool &$c, QueryBuilder &$q): void {
            if ($c) return;
            $q->leftJoin('c.comic', 'cc');
            $c = true;
        };
        $q1 = false;
        $q1Func = function (bool &$c, QueryBuilder &$q): void {
            if ($c) return;
            $q->leftJoin('c.destinationLinks', 'cd2');
            $c = true;
        };
        $q11 = false;
        $q11Func = function (bool &$c, QueryBuilder &$q): void {
            if ($c) return;
            $q->leftJoin('cd2.link', 'cd2l');
            $c = true;
        };
        $q111 = false;
        $q111Func = function (bool &$c, QueryBuilder &$q): void {
            if ($c) return;
            $q->leftJoin('cd2l.website', 'cd2lw');
            $c = true;
        };

        foreach ($criteria as $key => $val) {
            $val = \array_unique($val);

            switch ($key) {
                case 'comicCodes':
                    $c = \count($val);
                    if ($c < 1) break;

                    $q01Func($q01, $query);

                    if ($c == 1) {
                        $query->andWhere('cc.code = :comicCode');
                        $query->setParameter('comicCode', $val[0]);
                        break;
                    }
                    $query->andWhere('cc.code IN (:comicCodes)');
                    $query->setParameter('comicCodes', $val);
                    break;
                case 'destinationLinks':
                    $c = \count($val);
                    if ($c < 1) break;

                    $q1Func($q1, $query);

                    $w = [];
                    foreach ($val as $v) {
                        foreach ($v as $key => $val) {
                            if (!isset($w[$key])) {
                                $w[$key] = [];
                            }
                            \array_push($w[$key], ...$val);
                        }
                    }
                    if (\count($w) < 1) break;

                    foreach ($w as $key => $val) {
                        switch ($key) {
                            case 'linkWebsiteHosts':
                                $c = \count($val);
                                if ($c < 1) break;

                                $q11Func($q11, $query);
                                $q111Func($q111, $query);

                                if ($c == 1) {
                                    $query->andWhere('cd2lw.host = :linkWebsiteHost');
                                    $query->setParameter('linkWebsiteHost', $val[0]);
                                    break;
                                }
                                $query->andWhere('cd2lw.host IN (:linkWebsiteHosts)');
                                $query->setParameter('linkWebsiteHosts', $val);
                                break;
                            case 'linkRelativeReferences':
                                $c = \count($val);
                                if ($c < 1) break;

                                $q11Func($q11, $query);

                                foreach ($val as $k => $v) {
                                    switch ($v) {
                                        case null:
                                            $val[$k] = '';
                                            break;
                                        case '':
                                            $val[$k] = null;
                                            break;
                                    }
                                }

                                if ($c == 1) {
                                    $query->andWhere('cd2l.relativeReference = :linkRelativeReference');
                                    $query->setParameter('linkRelativeReference', $val[0]);
                                    break;
                                }
                                $query->andWhere('cd2l.relativeReference IN (:linkRelativeReferences)');
                                $query->setParameter('linkRelativeReferences', $val);
                                break;
                            case 'linkHREFs':
                                $c = \count($val);
                                if ($c < 1) break;

                                $q11Func($q11, $query);
                                $q111Func($q111, $query);

                                $qExOr = $query->expr()->orX();
                                foreach ($val as $k => $v) {
                                    $href = new Href($v);

                                    $qExOr->add('cd2lw.host = :linkHREFA' . $k . ' AND ' . 'cd2l.relativeReference = :linkHREFB' . $k);
                                    $query->setParameter('linkHREFA' . $k, $href->getHost());
                                    $query->setParameter('linkHREFB' . $k, $href->getRelativeReference() ?? '');
                                }
                                $query->andWhere($qExOr);
                                break;
                        }
                    }
                    break;
            }
        }

        return $query->getQuery()->getSingleScalarResult();
    }
}
