<?php
namespace DMJohnson\Ordain\Model;

class ScalarType extends Type{
    const TYPES = [
        'binary',
        'bool',
        'date',
        'datetime',
        'decimal',
        'float',
        'int',
        'string',
        'time',
        // TODO these aren't actually scalars and will eventually need a separate class
        'struct',
        'list',
        'array',
        'mapping',
        'enum',
        'any',
    ];
    public function __construct(public readonly string $type){}
}