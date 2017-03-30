<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Doctrine\Orm\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\FilterEagerLoadingExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Foo;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class FilterEagerLoadingExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testIsNoForceEagerCollectionAttributes()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->willReturn(new ResourceMetadata(DummyCar::class, null, null, null, [
            'get' => [
                'force_eager' => false,
            ],
        ], null));

        $qb = $this->prophesize(QueryBuilder::class);
        $qb->getDQLPart('where')->shouldNotBeCalled();

        $queryNameGenerator = $this->prophesize(QueryNameGeneratorInterface::class);

        $filterEagerLoadingExtension = new FilterEagerLoadingExtension($resourceMetadataFactoryProphecy->reveal(), true);
        $filterEagerLoadingExtension->applyToCollection($qb->reveal(), $queryNameGenerator->reveal(), DummyCar::class, 'get');
    }

    public function testIsNoForceEagerResource()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->willReturn(new ResourceMetadata(DummyCar::class, null, null, null, [
            'get' => [],
        ], ['force_eager' => false]));

        $qb = $this->prophesize(QueryBuilder::class);
        $qb->getDQLPart('where')->shouldNotBeCalled();

        $queryNameGenerator = $this->prophesize(QueryNameGeneratorInterface::class);

        $filterEagerLoadingExtension = new FilterEagerLoadingExtension($resourceMetadataFactoryProphecy->reveal(), true);
        $filterEagerLoadingExtension->applyToCollection($qb->reveal(), $queryNameGenerator->reveal(), DummyCar::class, null);
    }

    public function testIsForceEagerConfig()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->willReturn(new ResourceMetadata(DummyCar::class, null, null, null, [
            'get' => [],
        ]));

        $qb = $this->prophesize(QueryBuilder::class);
        $qb->getDQLPart('where')->shouldNotBeCalled();

        $queryNameGenerator = $this->prophesize(QueryNameGeneratorInterface::class);

        $filterEagerLoadingExtension = new FilterEagerLoadingExtension($resourceMetadataFactoryProphecy->reveal(), false);
        $filterEagerLoadingExtension->applyToCollection($qb->reveal(), $queryNameGenerator->reveal(), DummyCar::class, 'get');
    }

    public function testHasNoWherePart()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->willReturn(new ResourceMetadata(DummyCar::class));

        $qb = $this->prophesize(QueryBuilder::class);
        $qb->getDQLPart('where')->shouldBeCalled()->willReturn(null);

        $queryNameGenerator = $this->prophesize(QueryNameGeneratorInterface::class);

        $filterEagerLoadingExtension = new FilterEagerLoadingExtension($resourceMetadataFactoryProphecy->reveal(), true);
        $filterEagerLoadingExtension->applyToCollection($qb->reveal(), $queryNameGenerator->reveal(), DummyCar::class, 'get');
    }

    public function testHasNoJoinPart()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->willReturn(new ResourceMetadata(DummyCar::class));

        $qb = $this->prophesize(QueryBuilder::class);
        $qb->getDQLPart('where')->shouldBeCalled()->willReturn(new Expr\Andx());
        $qb->getDQLPart('join')->shouldBeCalled()->willReturn(null);

        $queryNameGenerator = $this->prophesize(QueryNameGeneratorInterface::class);

        $filterEagerLoadingExtension = new FilterEagerLoadingExtension($resourceMetadataFactoryProphecy->reveal(), true);
        $filterEagerLoadingExtension->applyToCollection($qb->reveal(), $queryNameGenerator->reveal(), DummyCar::class, 'get');
    }

    public function testApplyCollection()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->willReturn(new ResourceMetadata(DummyCar::class));

        $em = $this->prophesize(EntityManager::class);
        $em->getExpressionBuilder()->shouldBeCalled()->willReturn(new Expr());

        $qb = new QueryBuilder($em->reveal());

        $qb->select('o')
            ->from(DummyCar::class, 'o')
            ->leftJoin('o.colors', 'colors')
            ->where('o.colors = :foo')
            ->setParameter('foo', 1);

        $queryNameGenerator = $this->prophesize(QueryNameGeneratorInterface::class);
        $queryNameGenerator->generateJoinAlias('colors')->shouldBeCalled()->willReturn('colors_2');

        $filterEagerLoadingExtension = new FilterEagerLoadingExtension($resourceMetadataFactoryProphecy->reveal(), true);
        $filterEagerLoadingExtension->applyToCollection($qb, $queryNameGenerator->reveal(), DummyCar::class, 'get');

        $this->assertEquals('SELECT o FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar o LEFT JOIN o.colors colors WHERE o IN(SELECT o_2 FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar o_2 LEFT JOIN o_2.colors colors_2 WHERE o_2.colors = :foo)', $qb->getDQL());
    }

    /**
     * https://github.com/api-platform/core/issues/1021.
     */
    public function testHiddenOrderBy()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->willReturn(new ResourceMetadata(DummyCar::class));

        $em = $this->prophesize(EntityManager::class);
        $em->getExpressionBuilder()->shouldBeCalled()->willReturn(new Expr());

        $qb = new QueryBuilder($em->reveal());

        $qb->select('o', 'CASE WHEN o.dateCreated IS NULL THEN 0 ELSE 1 END AS HIDDEN _o_dateCreated_null_rank')
            ->from(DummyCar::class, 'o')
            ->leftJoin('o.colors', 'colors')
            ->where('o.colors = :foo')
            ->orderBy('_o_dateCreated_null_rank DESC')
            ->setParameter('foo', 1);

        $queryNameGenerator = $this->prophesize(QueryNameGeneratorInterface::class);
        $queryNameGenerator->generateJoinAlias('colors')->shouldBeCalled()->willReturn('colors_2');
        $filterEagerLoadingExtension = new FilterEagerLoadingExtension($resourceMetadataFactoryProphecy->reveal(), true);
        $filterEagerLoadingExtension->applyToCollection($qb, $queryNameGenerator->reveal(), DummyCar::class, 'get');

        $expected = <<<SQL
SELECT o, CASE WHEN o.dateCreated IS NULL THEN 0 ELSE 1 END AS HIDDEN _o_dateCreated_null_rank 
FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar o 
LEFT JOIN o.colors colors 
WHERE o IN(
  SELECT o_2 FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar o_2 
  LEFT JOIN o_2.colors colors_2 
  WHERE o_2.colors = :foo
) ORDER BY _o_dateCreated_null_rank DESC ASC
SQL;

        $this->assertEquals($this->toDQLString($expected), $qb->getDQL());
    }

    public function testGroupBy()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->willReturn(new ResourceMetadata(DummyCar::class));

        $em = $this->prophesize(EntityManager::class);
        $em->getExpressionBuilder()->shouldBeCalled()->willReturn(new Expr());

        $qb = new QueryBuilder($em->reveal());

        $qb->select('o', 'count(o.id) as counter')
            ->from(DummyCar::class, 'o')
            ->leftJoin('o.colors', 'colors')
            ->where('o.colors = :foo')
            ->orderBy('o.colors')
            ->groupBy('o.colors')
            ->having('counter > 3')
            ->setParameter('foo', 1);

        $queryNameGenerator = $this->prophesize(QueryNameGeneratorInterface::class);
        $queryNameGenerator->generateJoinAlias('colors')->shouldBeCalled()->willReturn('colors_2');
        $filterEagerLoadingExtension = new FilterEagerLoadingExtension($resourceMetadataFactoryProphecy->reveal(), true);
        $filterEagerLoadingExtension->applyToCollection($qb, $queryNameGenerator->reveal(), DummyCar::class, 'get');

        $expected = <<<SQL
SELECT o, count(o.id) as counter 
FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar o 
LEFT JOIN o.colors colors WHERE o 
IN(
  SELECT o_2 FROM ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar o_2 
  LEFT JOIN o_2.colors colors_2 
  WHERE o_2.colors = :foo
) 
GROUP BY o.colors HAVING counter > 3 
ORDER BY o.colors ASC
SQL;

        $this->assertEquals($this->toDQLString($expected), $qb->getDQL());
    }

    private function toDQLString(string $dql): string
    {
        return str_replace(PHP_EOL, '', str_replace('  ', '', $dql));
    }
}
