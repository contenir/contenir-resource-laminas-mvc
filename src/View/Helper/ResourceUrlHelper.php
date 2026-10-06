<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\View\Helper;

use Contenir\Db\Model\Exception\ExceptionInterface as DbModelException;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Laminas\Mvc\Url\ResourceUrlGenerator;

/**
 * View helper "resourceUrl": the [url, target] pair for a resource or an
 * editor-entered URL (see ResourceUrlGenerator). Escape both.
 *
 * @api
 */
final readonly class ResourceUrlHelper
{
    public function __construct(
        private ResourceUrlGenerator $urls,
    ) {}

    /**
     * @return array{0: string|null, 1: string|null}
     *
     * @throws DbModelException
     */
    public function __invoke(
        AbstractResourceEntity|int|string|null $resource = null,
        ?string $url = null,
        ?string $target = null,
    ): array {
        return $this->urls->generate($resource, $url, $target)->toArray();
    }
}
