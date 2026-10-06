<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Tests\TestAsset\Database;

/**
 * The Contenir resource tables, in SQLite form, with the columns the
 * entities map.
 */
final class Schema
{
    /**
     * @return list<string>
     */
    public static function create(): array
    {
        return [
            'CREATE TABLE resource_type (
                resource_type_id TEXT PRIMARY KEY, slug TEXT, sequence INTEGER, type TEXT, title TEXT
            )',
            'CREATE TABLE resource (
                resource_id INTEGER PRIMARY KEY AUTOINCREMENT, parent_id INTEGER, resource_type_id TEXT,
                workflow TEXT, sequence INTEGER, slug TEXT, title TEXT, title_short TEXT, subtitle TEXT,
                description TEXT, section TEXT, meta_title TEXT, meta_description TEXT, visible INTEGER,
                created TEXT, updated TEXT, active TEXT
            )',
            'CREATE TABLE resource_collection (
                resource_collection_id INTEGER PRIMARY KEY AUTOINCREMENT, resource_type_id TEXT,
                sequence INTEGER, title TEXT, slug TEXT, description TEXT, layout TEXT, meta_title TEXT,
                meta_description TEXT, active TEXT
            )',
        ];
    }
}
