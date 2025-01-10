<?php
namespace DMJohnson\Ordain\Model;

class Typedef{
    function __construct(
        public readonly string $name,
        public readonly Type $type,
        public readonly ?string $docs,
        public readonly TagRepository $tags,
        /** @var ?array<string,Typedef> $struct_fields*/
        public readonly ?array $struct_fields,
    ){}

    /**
     * Merge two typedefs
     */
    static function overlay(Typedef $parent, Typedef $child){
        return new Typedef(
            $child->name,
            $parent->type,
            $child->docs ?? $parent->docs,
            TagRepository::merge($parent->tags, $child->tags),
            array_merge($child->struct_fields ?? [], $parent->struct_fields ?? []) ?: null,
        );
    }
}