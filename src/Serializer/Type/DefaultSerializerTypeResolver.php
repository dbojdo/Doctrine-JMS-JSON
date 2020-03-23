<?php
/**
 * File DefaultSerializerTypeResolver.php
 * Created at: 2016-08-25 23-01
 *
 * @author Daniel Bojdo <daniel.bojdo@web-it.eu>
 */

namespace Webit\DoctrineJmsJson\Serializer\Type;

use Webit\DoctrineJmsJson\Serializer\Type\Exception\TypeNotResolvedException;

class DefaultSerializerTypeResolver implements SerializerTypeResolver
{
    /**
     * @param mixed $value
     * @return string
     */
    public function resolveType($value): string
    {

        if (is_scalar($value)) {
            return gettype($value);
        }

        if (is_array($value)) {
            return $this->resolveCollectionType(
                $value,
                function (array $arValue) {
                    return 'array';
                },
                function (array $arValue) {
                    if (count($arValue) > 0) {
                        return array_shift($arValue);
                    }

                    return null;
                },
                function (array $arValue) {
                    $keys = array_keys($arValue);

                    if ($keys == array()) {
                        return null;
                    }

                    return $keys !== range(0, count($keys) - 1) ? 'string' : 'integer';
                }
            );
        }

        if ($value instanceof \Doctrine\Common\Collections\Collection) {
            return $this->resolveCollectionType(
                $value,
                function (\Doctrine\Common\Collections\Collection $collection) {
                    return get_class($collection);
                },
                function (\Doctrine\Common\Collections\Collection $collection) {
                    if ($collection->count() > 0) {
                        return $collection->first();
                    }

                    return null;
                },
                function (\Doctrine\Common\Collections\Collection $collection) {
                    $keys = $collection->getKeys();

                    if ($keys === array()) {
                        return null;
                    }

                    return $keys !== range(0, count($keys) - 1) ? 'string' : 'integer';
                }
            );
        }

        if ($value instanceof \DateTime) {
            return sprintf("DateTime");
        }

        if (is_object($value)) {
            return get_class($value);
        }

        throw new TypeNotResolvedException('Could not resolved the Serialized type for given value.');
    }

    /**
     * @param Collection $collection
     * @param \callable $collectionType
     * @param \callable $firstItem
     * @param \callable $keyType
     * @return string
     */
    private function resolveCollectionType($collection, $collectionType, $firstItem, $keyType)
    {
        $type = call_user_func($collectionType, $collection);
        $item = call_user_func($firstItem, $collection);
        $keyType = call_user_func($keyType, $collection);

        if ($item === null) {
            return $type;
        }

        if ($keyType) {
            return sprintf('%s<%s,%s>', $type, $keyType, $this->resolveType($item));
        }

        return sprintf('%s<%s>', $type, $this->resolveType($item));
    }
}
