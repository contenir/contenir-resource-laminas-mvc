<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Preview;

use Contenir\Db\Model\EntityManager;
use Contenir\Db\Model\RowValues;
use Contenir\Preview\PreviewContext;
use Contenir\Preview\PreviewRecord;
use Contenir\Preview\PreviewRequest;
use DateTimeImmutable;
use PhpDb\Adapter\AdapterInterface;

/**
 * Previews of resources as the CMS sends them, and the context a site holds one in.
 */
final class PreviewRecords
{
    public static function context(?PreviewRecord $record = null): PreviewContext
    {
        $context = new PreviewContext();
        if (null !== $record) {
            $context->begin(new PreviewRequest(
                $record,
                '12',
                '0123456789abcdef0123456789abcdef',
                new DateTimeImmutable('@1792236000'),
                new DateTimeImmutable('@1792236300'),
            ));
        }

        return $context;
    }

    /**
     * Row conversion over an entity manager that never touches its database.
     */
    public static function rows(AdapterInterface $adapter): RowValues
    {
        return new RowValues(new EntityManager($adapter));
    }

    /**
     * A preview of a saved resource, keyed as the CMS keys the resource table.
     *
     * @param array<string, string|list<string>|null> $values
     */
    public static function saved(int $resourceId, array $values, string $section = 'site.page.home'): PreviewRecord
    {
        return new PreviewRecord($section, ['resource_id' => (string) $resourceId], $values);
    }

    /**
     * A preview of a resource not saved yet.
     *
     * @param array<string, string|list<string>|null> $values
     */
    public static function unsaved(array $values, string $section = 'library.articles.article'): PreviewRecord
    {
        return new PreviewRecord($section, [], $values);
    }
}
