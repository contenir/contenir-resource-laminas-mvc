<?php

declare(strict_types=1);

namespace Contenir\Resource\Laminas\Mvc\Exception;

use Contenir\Resource\Core\Exception\ExceptionInterface;
use InvalidArgumentException;

use function get_debug_type;
use function is_string;
use function sprintf;

/**
 * A value given as a resource id is neither an int nor a string of digits.
 *
 * @api
 */
final class InvalidResourceIdException extends InvalidArgumentException implements ExceptionInterface
{
    public static function forValue(mixed $value): self
    {
        return new self(sprintf(
            'A resource id must be an int or a string of digits, got %s',
            is_string($value) ? sprintf('"%s"', $value) : get_debug_type($value),
        ));
    }
}
