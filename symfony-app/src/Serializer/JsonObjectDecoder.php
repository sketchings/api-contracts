<?php

namespace App\Serializer;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;

/**
 * Decodes JSON request bodies, which must be JSON objects in this API.
 *
 * Unlike the built-in decoder it keeps nested `{}` as an object instead of
 * turning it into `[]`, so `"dates": {}` fails type checks rather than being
 * accepted as an empty list. A body of `null`, `[]`, a number or a string is
 * rejected instead of being mapped to an empty object, and so is any `null`
 * value: no property in the API is nullable, and an explicit `null` would
 * otherwise be treated as if the property were absent.
 */
#[AutoconfigureTag('serializer.encoder', ['priority' => 10])]
class JsonObjectDecoder implements DecoderInterface
{
    public function decode(string $data, string $format, array $context = []): mixed
    {
        try {
            $decoded = json_decode($data, false, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new NotEncodableValueException($e->getMessage(), 0, $e);
        }

        if (!$decoded instanceof \stdClass) {
            throw new NotEncodableValueException('The request body must be a JSON object.');
        }

        return self::toArray($decoded, '');
    }

    public function supportsDecoding(string $format): bool
    {
        return 'json' === $format;
    }

    /**
     * Converts objects to arrays, except nested empty objects, which stay objects.
     * Throws on null values; $path names the value in the error message.
     *
     * @return array<mixed>
     */
    private static function toArray(\stdClass|array $value, string $path): array
    {
        $result = [];
        foreach ((array) $value as $key => $item) {
            $itemPath = '' === $path ? (string) $key : \sprintf('%s[%s]', $path, $key);
            $result[$key] = match (true) {
                null === $item => throw new NotEncodableValueException(\sprintf('"%s" must not be null.', $itemPath)),
                $item instanceof \stdClass && [] === (array) $item => $item,
                $item instanceof \stdClass, \is_array($item) => self::toArray($item, $itemPath),
                default => $item,
            };
        }

        return $result;
    }
}
