<?php

namespace DMJohnson\Ordain\Conversion;

use DMJohnson\Ordain\Model\Type;

interface TypeConverter{
    function toOrdained(mixed $value, Type $ordainedType):TypedValue;
    function fromOrdained(TypedValue $value, string $targetType):mixed;
}