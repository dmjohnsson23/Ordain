<?php
namespace DMJohnson\Ordain\Model;

class TagRepository implements \IteratorAggregate, \Countable, \ArrayAccess{
    /**
     * @param Tag[] $tags
     */
    function __construct(private array $tags){}

    /**
     * Find all tags with the given name, either in the universal cannon or one of the recognized cannon namespaces.
     * 
     * @return static
     */
    public function filter(string $tagName, array $recognizedCannons = [], bool $includeUniversal = true){
        return new static(\array_filter($this->tags, fn($tag)=>
            $tag->name === $tagName && (($includeUniversal && \is_null($tag->cannon)) || \in_array($tag->cannon, $recognizedCannons))
        ));
    }

    /**
     * Sort tags according to a cannon hierarchy. The sorting will be designed such that the first 
     * item in the resulting array should be the "best".
     * 
     * @param string[] ...$cannonHierarchy Arrays of cannon namespaces. Each array is a group of 
     *      equal-rank namespaces. Arrays themselves should be ordered with the highest priorities 
     *      first.
     * @return static The same tags, ordered from most priority to least priority.
     */
    public function sort(array ...$cannonHierarchy){
        $tags = \array_reverse($this->tags);
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
        return new static($tags);
    }

    /**
     * Sort tags according to a cannon hierarchy. The sorting will be designed such that the first 
     * item in the resulting array should be the "best".
     * 
     * This is intended to be used after calling `filter`.
     * 
     * @param string[] ...$cannonHierarchy Arrays of cannon namespaces. Each array is a group of 
     *      equal-rank namespaces. Arrays themselves should be ordered with the highest priorities 
     *      first.
     * @return ?Model\Tag The highest-priority tag from the given list
     */
    public function getTop(array ...$cannonHierarchy){
        if (empty($this->tags)) return null;
        $bestTag = null;
        $bestRank = INF;
        foreach ($this->tags as $tag){
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

    static function merge(TagRepository ...$repos){
        return new TagRepository(\array_merge(...\array_map(fn($repo)=>$repo->tags, $repos)));
    }

    public function getIterator(): \Iterator{
        return new \ArrayIterator($this->tags);
    }

    public function count(): int{
        return \count($this->tags);
    }

    public function offsetExists($offset): bool{
        return isset($this->tags[$offset]);
    }

    public function offsetGet($offset): Tag{
        return $this->tags[$offset];
    }

    public function offsetSet($offset, $value): void{
        throw new \RuntimeException('Cannot modify a tag repository');
    }

    public function offsetUnset($offset): void{
        throw new \RuntimeException('Cannot modify a tag repository');
    }
}