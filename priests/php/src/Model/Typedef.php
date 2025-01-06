<?php
namespace DMJohnson\Ordain\Model;

class Typedef{
    function __construct(
        public readonly string $name,
        public readonly Type $type,
        public readonly ?string $docs,
        /** @var ?Tag[] $tags*/
        public readonly ?array $tags,
        /** @var ?array<string,Typedef> $struct_fields*/
        public readonly ?array $struct_fields,
    ){}
}