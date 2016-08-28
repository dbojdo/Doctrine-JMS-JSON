<?php
/**
 * File SerializerTypeResolver.php
 * Created at: 2016-08-25 23-04
 *
 * @author Daniel Bojdo <daniel.bojdo@web-it.eu>
 */

namespace Webit\DoctrineJmsJson\Serializer\Type;

interface SerializerTypeResolver
{
    /**
     * @param mixed $value
     * @return string
     */
    public function resolveType($value);
}
