<?php

use DMJohnson\Ordain\Model\Tag;
use \DMJohnson\Ordain\Seeker;
use PHPUnit\Framework\TestCase;

class SeekerTest extends TestCase{
    function testSortTagsLastShallBeFirst(){
        $tags = [
            new Tag(null, 'tag', 'first'),
            new Tag(null, 'tag', 'middle'),
            new Tag(null, 'tag', 'last'),
        ];
        $seeker = new Seeker([]);
        $sorted = $seeker->sortTags($tags);
        $this->assertSameSize($tags, $sorted);
        $this->assertSame($tags[2], $sorted[0], "Last tag has the highest priority");
        $this->assertSame($tags[1], $sorted[1], "Middle tag has middle priority");
        $this->assertSame($tags[0], $sorted[2], "First tags has the lowest priority");
    }
    function testSortTagsDenominationalTagsFirst(){
        $tags = [
            new Tag(null, 'tag', 'first'),
            new Tag('mine', 'tag', 'middle'),
            new Tag(null, 'tag', 'last'),
        ];
        $seeker = new Seeker([]);
        $sorted = $seeker->sortTags($tags);
        $this->assertSameSize($tags, $sorted);
        $this->assertSame($tags[1], $sorted[0], "Denominational tags has highest priority");
        $this->assertSame($tags[2], $sorted[1], "Last tag has the highest priority after denominational tag");
        $this->assertSame($tags[0], $sorted[2], "First tags has the lowest priority");
    }
    function testSortTagsWithHierarchy(){
        $tags = [
            new Tag(null, 'tag', 'first universal'),
            new Tag('mine', 'tag', 'first mine'),
            new Tag(null, 'tag', 'second universal'),
            new Tag('ours', 'tag', 'first ours'),
            new Tag('mine', 'tag', 'second mine'),
            new Tag('yours', 'tag', 'first yours'),
            new Tag('yours', 'tag', 'second yours'),
            new Tag('ours', 'tag', 'second ours'),
            new Tag('theirs', 'tag', 'unknown'),
        ];
        $seeker = new Seeker([]);
        $sorted = $seeker->sortTags($tags, ['mine', 'yours'], ['ours']);
        $this->assertSameSize($tags, $sorted);
        $this->assertSame($tags[6], $sorted[0], "Last tag from highest rank has the highest priority");
        $this->assertSame($tags[5], $sorted[1], "Second-to-last tag from highest rank has the second-highest priority");
        $this->assertSame($tags[4], $sorted[2], "Third-to-last tag from highest rank has the third-highest priority");
        $this->assertSame($tags[1], $sorted[3], "Fourth-to-last tag from highest rank has the fourth-highest priority");
        $this->assertSame($tags[7], $sorted[4], "Last tag from lowest rank has the fifth-highest priority");
        $this->assertSame($tags[3], $sorted[5], "First tag from lowest rank has the sixth-highest priority");
        $this->assertSame($tags[8], $sorted[6], "Unknown denominational tag has the seventh-highest priority");
        $this->assertSame($tags[2], $sorted[7], "Last universal tag has the eighth-highest priority");
        $this->assertSame($tags[0], $sorted[8], "First universal tag has the lowest priority");
    }

    function testSortTagsGetOneLastShallBeFirst(){
        $tags = [
            new Tag(null, 'tag', 'first'),
            new Tag(null, 'tag', 'middle'),
            new Tag(null, 'tag', 'last'),
        ];
        $seeker = new Seeker([]);
        $accepted = $seeker->sortTagsGetOne($tags);
        $this->assertSame($tags[2], $accepted, "Last tag has the highest priority");
    }
    function testSortTagsGetOneDenominationalTagsFirst(){
        $tags = [
            new Tag(null, 'tag', 'first'),
            new Tag('mine', 'tag', 'middle'),
            new Tag(null, 'tag', 'last'),
        ];
        $seeker = new Seeker([]);
        $accepted = $seeker->sortTagsGetOne($tags);
        $this->assertSame($tags[1], $accepted, "Denominational tags has highest priority");
    }
    function testSortTagsGetOneWithHierarchy(){
        $tags = [
            new Tag(null, 'tag', 'first universal'),
            new Tag('mine', 'tag', 'first mine'),
            new Tag(null, 'tag', 'second universal'),
            new Tag('ours', 'tag', 'first ours'),
            new Tag('mine', 'tag', 'second mine'),
            new Tag('yours', 'tag', 'first yours'),
            new Tag('yours', 'tag', 'second yours'),
            new Tag('ours', 'tag', 'second ours'),
            new Tag('theirs', 'tag', 'unknown'),
        ];
        $seeker = new Seeker([]);
        $accepted = $seeker->sortTagsGetOne($tags, ['mine', 'yours'], ['ours']);
        $this->assertSame($tags[6], $accepted, "Last tag from highest rank has the highest priority");
    }
}