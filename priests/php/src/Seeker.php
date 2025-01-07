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
    public function toTypedef($typedef){
        if ($typedef instanceof Model\Typedef) return $typedef;
        if ($typedef instanceof Model\NamedTypeReference) $typedef = $typedef->type;
        if (\is_string($typedef) && isset($this->model[$typedef])){
            return $this->model[$typedef];
        }
        throw new Exceptions\OrdainException('Typedef not found for '.\var_export($typedef, true));
    }

    /**
     * Find all tags with the given name, either in the universal cannon or one of the recognized cannon namespaces.
     * 
     * @param Model\Tag[] $tags
     * @return Model\Tag[]
     */
    public function filterTags(array $tags, string $tagName, array $recognizedCannons = [], bool $includeUniversal = true){
        return \array_filter($tags, fn($tag)=>
            $tag->name === $tagName && (($includeUniversal && \is_null($tag->cannon)) || \in_array($tag->cannon, $recognizedCannons))
        );
    }

    /**
     * Sort tags according to a cannon hierarchy. The sorting will be designed such that the first 
     * item in the resulting array should be the "best".
     * 
     * @param Model\Tag[] $tags
     * @param string[] ...$cannonHierarchy Arrays of cannon namespaces. Each array is a group of 
     *      equal-rank namespaces. Arrays themselves should be ordered with the highest priorities 
     *      first.
     * @return Model\Tag[] The same tags, ordered from most priority to least priority.
     */
    public function sortTags(array $tags, array ...$cannonHierarchy){
        $tags = \array_reverse($tags);
        \usort($tags, function($tag1, $tag2) use ($cannonHierarchy){
            // -1 : $tag1 < $tag2
            //  0 : $tag1 = $tag2
            // +1 : $tag1 > $tag2
            if ($tag1->cannon === $tag2->cannon){
                return 0;
            }
            foreach ($cannonHierarchy as $rankedGroup){
                $left = \in_array($tag1->cannon, $rankedGroup);
                $right = \in_array($tag2->cannon, $rankedGroup);
                if ($left && $right) return 0;
                if ($left) return -1;
                if ($right) return 1;
            }
            // Code should only reach this point if both cannons are not in the hierarchy
            if (\is_null($tag1->cannon)){
                return 1;
            }
            if (\is_null($tag2->cannon)){
                return -1;
            }
            return 0;
        });
        return $tags;
    }

    /**
     * Sort tags according to a cannon hierarchy. The sorting will be designed such that the first 
     * item in the resulting array should be the "best".
     * 
     * @param Model\Tag[] $tags
     * @param string[] ...$cannonHierarchy Arrays of cannon namespaces. Each array is a group of 
     *      equal-rank namespaces. Arrays themselves should be ordered with the highest priorities 
     *      first.
     * @return Model\Tag The highest-priority tag from the given list
     */
    public function sortTagsGetOne(array $tags, array ...$cannonHierarchy){
        if (empty($tags)) return null;
        $bestTag = null;
        $bestRank = INF;
        foreach ($tags as $tag){
            $rank = INF;
            foreach ($cannonHierarchy as $index=>$rankedGroup){
                if (\in_array($tag->cannon, $rankedGroup)){
                    $rank = $index;
                    break;
                }
            }
            if ($rank <= $bestRank){
                if (isset($bestTag->cannon) && \is_null($tag->cannon)){
                    // Used to sort tags with an unknown cannon before tags with the universal (null) cannon
                    continue;
                }
                $bestRank = $rank;
                $bestTag = $tag;
            }
        }
        return $bestTag;
    }

    public function findAndFilterTags($typedef, string $tagName, array $recognizedCannons = [], bool $includeUniversal = true){
        $typedef = $this->toTypedef($typedef);
        if (\is_null($typedef->tags)) return [];
        return $this->filterTags($typedef->tags, $tagName, $recognizedCannons, $includeUniversal);
    }

    public function findFilterAndSortTags($typedef, string $tagName, array ...$cannonHierarchy){
        $typedef = $this->toTypedef($typedef);
        if (\is_null($typedef->tags)) return [];
        $recognizedCannons = \array_merge(...$cannonHierarchy);
        $tags = $this->filterTags($typedef->tags, $tagName, $recognizedCannons);
        return $this->sortTags($tags, ...$cannonHierarchy);
    }

    public function findFilterAndSortTagsGetOne($typedef, string $tagName, array ...$cannonHierarchy){
        $typedef = $this->toTypedef($typedef);
        if (\is_null($typedef->tags)) return [];
        $recognizedCannons = \array_merge(...$cannonHierarchy);
        $tags = $this->filterTags($typedef->tags, $tagName, $recognizedCannons);
        return $this->sortTagsGetOne($tags, ...$cannonHierarchy);
    }
}