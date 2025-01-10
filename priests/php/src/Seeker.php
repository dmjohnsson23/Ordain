<?php
namespace DMJohnson\Ordain;

/**
 * Utility class to search a model and answer questions about its structure.
 */
class Seeker{
    public function __construct(
        /** @var array<string,Model\Typedef> $model */
        public readonly array $model,
    ){}

    /**
     * @return Model\Typedef
     */
    public function find($typedef){
        if ($typedef instanceof Model\Typedef) return $typedef;
        if ($typedef instanceof Model\NamedTypeReference) $typedef = $typedef->type;
        if (\is_string($typedef) && isset($this->model[$typedef])){
            return $this->model[$typedef];
        }
        throw new Exceptions\OrdainException('Typedef not found for '.\var_export($typedef, true));
    }

    /**
     * Fully resolve a typedef into a complete definition.
     * 
     * The returned typedef will not have any named type references in the `type` field, but 
     * instead have a fully realized Ordain-native type. Tags, documentation, and fields will
     * also be inherited from parents and merged into this typedef.
     * 
     * However, this does not recursively resolve the types of struct fields.
     * 
     * @return Model\Typedef
     */
    public function resolveTypedef($typedef){
        $typedef = $this->find($typedef);
        while ($typedef->type instanceof Model\NamedTypeReference){
            $typedef = Model\Typedef::overlay($this->find($typedef->type), $typedef);
        }
        return $typedef;
    }

    public function findAndFilterTags($typedef, string $tagName, array $recognizedCannons = [], bool $includeUniversal = true){
        $typedef = $this->resolveTypedef($typedef);
        return $typedef->tags->filter($tagName, $recognizedCannons, $includeUniversal);
    }

    public function findFilterAndSortTags($typedef, string $tagName, array ...$cannonHierarchy){
        $typedef = $this->resolveTypedef($typedef);
        $recognizedCannons = \array_merge(...$cannonHierarchy);
        return $typedef->tags->filter($tagName, $recognizedCannons)->sort();
    }

    public function findFilterAndSortTagsGetOne($typedef, string $tagName, array ...$cannonHierarchy){
        $typedef = $this->resolveTypedef($typedef);
        $recognizedCannons = \array_merge(...$cannonHierarchy);
        return $typedef->tags->filter($tagName, $recognizedCannons)->getTop(...$cannonHierarchy);
    }
}