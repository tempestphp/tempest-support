<?php

declare(strict_types=1);

namespace Tempest\Support\Json;

use JsonException;

use function json_decode;
use function json_encode;

use const JSON_BIGINT_AS_STRING;
use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * Decodes a json encoded string into a dynamic variable.
 *
 * @throws Exception\JsonCouldNotBeDecoded If an error occurred.
 */
function decode(string $json, bool $associative = true, bool $base64 = false): mixed
{
    if ($base64) {
        $json = base64_decode($json, strict: true);

        if ($json === false) {
            throw new Exception\JsonCouldNotBeDecoded('The provided base64 string is not valid.');
        }
    }

    try {
        /** @var mixed $value */
        $value = json_decode($json, $associative, 512, JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR);
    } catch (JsonException $jsonException) {
        throw new Exception\JsonCouldNotBeDecoded(sprintf('%s.', $jsonException->getMessage()), $jsonException->getCode(), $jsonException);
    }

    return $value;
}

/**
 * Returns a string containing the JSON representation of the supplied value.
 *
 * @throws Exception\JsonCouldNotBeEncoded If an error occurred.
 *
 * @return non-empty-string
 */
function encode(mixed $value, bool $pretty = false, int $flags = 0, bool $base64 = false): string
{
    $flags |= JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR;

    if ($pretty) {
        $flags |= JSON_PRETTY_PRINT;
    }

    try {
        /** @var non-empty-string $json */
        $json = json_encode($value, $flags);
    } catch (JsonException $jsonException) {
        throw new Exception\JsonCouldNotBeEncoded(sprintf('%s.', $jsonException->getMessage()), $jsonException->getCode(), $jsonException);
    }

    if ($base64) {
        return base64_encode($json);
    }

    return $json;
}

/**
 * Determines whether the given value is a valid JSON string.
 */
function is_valid(mixed $value): bool
{
    if (! is_string($value)) {
        return false;
    }

    return json_validate($value);
}
