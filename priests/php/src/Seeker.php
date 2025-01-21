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

    /**
     * Get a list of all named typedefs (e.g. those which classes should be generated for)
     * 
     * @return Model\Typedef[]
     */
    public function getAllNamedTypedefs(){
        // TODO as the structure gets more expressive, we will likely outgrow this implementation pretty soon.
        return \array_values($this->model);
    }
    
    /**
     * @return class-string
     */
    public function phpNameFor(Model\Typedef $typedef){
        // TODO we need a way to specify a default namespace so users don't need to do so with a `name` tag
        $tag = $typedef->tags->filter('name', ['php'])->getTop();
        if (is_null($tag)) return $typedef->name;
        else return $tag->value;
    }

    /**
     * @return string
     */
    public function sqlNameFor(Model\Typedef $typedef){
        // TODO incorporate SQL dialect
        $tag = $typedef->tags->filter('name', ['sql'])->getTop();
        if (is_null($tag)) return $typedef->name;
        else return $tag->value;
    }
}