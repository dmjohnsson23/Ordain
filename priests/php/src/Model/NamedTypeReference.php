<?php
namespace DMJohnson\Ordain\Model;

class NamedTypeReference extends Type{
    public function __construct(public readonly string $type){}
}