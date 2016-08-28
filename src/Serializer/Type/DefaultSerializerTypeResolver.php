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
    public function resolveType($value)
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
                    return current($arValue);
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
                    return $collection->first();
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
     * @return string
     */
    private function resolveCollectionType($collection, $collectionType, $firstItem)
    {
        $type = call_user_func($collectionType, $collection);
        $item = call_user_func($firstItem, $collection);

        if (! is_object($item)) {
            return $type;
        }

        return sprintf('%s<%s>', $type, $this->resolveType($item));
    }
}
