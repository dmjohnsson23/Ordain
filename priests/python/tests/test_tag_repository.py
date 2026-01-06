from ordain.model import *


def test_sort_tags_last_shall_be_first():
    tags = TagRepository([
        Tag(None, 'tag', 'first'),
        Tag(None, 'tag', 'middle'),
        Tag(None, 'tag', 'last'),
    ])
    
    sorted = tags.sort()
    assert len(tags) == len(sorted)
    assert tags[2] is sorted[0], "Last tag has the highest priority"
    assert tags[1] is sorted[1], "Middle tag has middle priority"
    assert tags[0] is sorted[2], "First tags has the lowest priority"


def test_sort_tags_denominational_tags_first():
    tags = TagRepository([
        Tag(None, 'tag', 'first'),
        Tag('mine', 'tag', 'middle'),
        Tag(None, 'tag', 'last'),
    ])
    
    sorted = tags.sort()
    assert len(tags) == len(sorted)
    assert tags[1] is sorted[0], "Denominational tags has highest priority"
    assert tags[2] is sorted[1], "Last tag has the highest priority after denominational tag"
    assert tags[0] is sorted[2], "First tags has the lowest priority"



def test_sort_tags_with_hierarchy():
    tags = TagRepository([
        Tag(None, 'tag', 'first universal'),
        Tag('mine', 'tag', 'first mine'),
        Tag(None, 'tag', 'second universal'),
        Tag('ours', 'tag', 'first ours'),
        Tag('mine', 'tag', 'second mine'),
        Tag('yours', 'tag', 'first yours'),
        Tag('yours', 'tag', 'second yours'),
        Tag('ours', 'tag', 'second ours'),
        Tag('theirs', 'tag', 'unknown'),
    ])
    
    sorted = tags.sort(['mine', 'yours'], ['ours'])
    assert len(tags) == len(sorted)
    assert tags[6] is sorted[0], "Last tag from highest rank has the highest priority"
    assert tags[5] is sorted[1], "Second-to-last tag from highest rank has the second-highest priority"
    assert tags[4] is sorted[2], "Third-to-last tag from highest rank has the third-highest priority"
    assert tags[1] is sorted[3], "Fourth-to-last tag from highest rank has the fourth-highest priority"
    assert tags[7] is sorted[4], "Last tag from lowest rank has the fifth-highest priority"
    assert tags[3] is sorted[5], "First tag from lowest rank has the sixth-highest priority"
    assert tags[8] is sorted[6], "Unknown denominational tag has the seventh-highest priority"
    assert tags[2] is sorted[7], "Last universal tag has the eighth-highest priority"
    assert tags[0] is sorted[8], "First universal tag has the lowest priority"


def test_sort_tags_get_one_last_shall_be_first():
    tags = TagRepository([
        Tag(None, 'tag', 'first'),
        Tag(None, 'tag', 'middle'),
        Tag(None, 'tag', 'last'),
    ])
    
    accepted = tags.get_top()
    assert tags[2] is accepted, "Last tag has the highest priority"


def test_sort_tags_get_one_denominational_tags_first():
    tags = TagRepository([
        Tag(None, 'tag', 'first'),
        Tag('mine', 'tag', 'middle'),
        Tag(None, 'tag', 'last'),
    ])
    
    accepted = tags.get_top()
    assert tags[1] is accepted, "Denominational tags has highest priority"


def test_sort_tags_get_one_with_hierarchy():
    tags = TagRepository([
        Tag(None, 'tag', 'first universal'),
        Tag('mine', 'tag', 'first mine'),
        Tag(None, 'tag', 'second universal'),
        Tag('ours', 'tag', 'first ours'),
        Tag('mine', 'tag', 'second mine'),
        Tag('yours', 'tag', 'first yours'),
        Tag('yours', 'tag', 'second yours'),
        Tag('ours', 'tag', 'second ours'),
        Tag('theirs', 'tag', 'unknown'),
    ])
    
    accepted = tags.get_top(['mine', 'yours'], ['ours'])
    assert tags[6] is accepted, "Last tag from highest rank has the highest priority"