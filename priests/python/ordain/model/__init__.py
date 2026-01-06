from dataclasses import dataclass
from enum import StrEnum
from typing import Optional, Mapping, List, Union, Collection, Sequence

class ScalarType(StrEnum):
    binary = 'binary'
    bool = 'bool'
    date = 'date'
    datetime = 'datetime'
    decimal = 'decimal'
    float = 'float'
    int = 'int'
    string = 'string'
    time = 'time'
    any = 'any'


@dataclass(frozen=True, slots=True)
class NamedTypeReference:
    name_ref: str


@dataclass(frozen=True, slots=True)
class StructType:
    fields: Mapping[str,"Typedef"]


@dataclass(frozen=True, slots=True)
class CollectionType:
    of: "Typedef"


@dataclass(frozen=True, slots=True)
class MappingType:
    keys: "Typedef"
    value: "Typedef"


@dataclass(frozen=True, slots=True)
class EnumType:
    of: ScalarType
    values: list # TODO probably need a new class for this


Type = Union[ScalarType, NamedTypeReference, StructType, CollectionType, MappingType, EnumType]


@dataclass(frozen=True, slots=True)
class Tag:
    cannon: Optional[str]
    name: str
    value: Union[str,int,bool,float]
    # TODO also have "context" (user-controlled tag sorting, e.g. "sql.name@server")

    @classmethod
    def from_key_value(cls, key:str, value):
       split_key = key.rsplit('.', 1)
       if len(split_key) == 2:
          return cls(*split_key, value) # type: ignore
       else:
          return cls(None, key, value)
       

@dataclass(frozen=True, slots=True)
class TagRepository:
    tags: Sequence[Tag]

    def filter(self, tag_name:str, recognized_cannons: Collection[str] = [], include_universal: bool = True)->"TagRepository":
        """
        Find all tags with the given name, either in the universal cannon or one of the recognized cannon namespaces.
        """
        return TagRepository([tag for tag in self.tags
            if tag.name == tag_name and (
               (include_universal and tag.cannon is None) 
               or tag.cannon in recognized_cannons
            )
        ])
    
    def _hierarchy_to_sort(self, cannon_hierarchy: Sequence[Union[str,Collection[str]]]):
        """
        Turn a cannon hierarchy into a sort function, suitable to pass to `sorted` and `max` and similar.
        """
        sort_map = {}
        for index, cannon_list in enumerate(cannon_hierarchy):
            if isinstance(cannon_list, str):
                sort_map[cannon_list] = index
            else:
                for cannon in cannon_list:
                    sort_map[cannon] = index
        def sort_func(tag: Tag):
            try:
                return sort_map[tag.cannon]
            except KeyError:
                # Sort None (universal cannon) after denominational cannons (used if no hierarchy specified)
                if tag.cannon is None:
                    return len(cannon_hierarchy) + 1
                return len(cannon_hierarchy)
        return sort_func

    def sort(self, *cannon_hierarchy: Union[str,Collection[str]]):
        """
        Sort tags according to a cannon hierarchy. The sorting will be designed such that the first 
        item in the resulting array should be the "best".
        
        :param cannon_hierarchy: Arrays of cannon namespaces. Each array is a group of 
            equal-rank namespaces. Arrays themselves should be ordered with the highest priorities 
            first.
        :returns: The same tags, ordered from most priority to least priority.
        """
        if len(self.tags) < 2:
            return TagRepository([*self.tags])
        return TagRepository(list(sorted(
            reversed(self.tags),
            key=self._hierarchy_to_sort(cannon_hierarchy)
        )))

    def get_top(self, *cannon_hierarchy: Union[str,Collection[str]])-> Optional[Tag]:
        """
        Sort tags according to a cannon hierarchy. The sorting will be designed such that the first 
        item in the resulting array should be the "best".
        
        This is intended to be used after calling `filter`.
        
        :param cannon_hierarchy: Arrays of cannon namespaces. Each array is a group of 
            equal-rank namespaces. Arrays themselves should be ordered with the highest priorities 
            first.
        :returns: The highest-priority tag from the given list
        """
        if len(self.tags) < 2:
            return None if len(self.tags) < 1 else self.tags[0]
        return min(
            reversed(self.tags),
            key=self._hierarchy_to_sort(cannon_hierarchy)
        )

    def merge(self, *repos: "TagRepository")->"TagRepository":
        """
        Create a new tag repository with the values of self and all other repositories passed
        """
        new_tags = [*self.tags]
        for repo in repos:
            new_tags.extend(repo.tags)
        return TagRepository(new_tags)

    def __iter__(self):
        return iter(self.tags)

    def __len__(self):
        return len(self.tags)

    def __getitem__(self, key):
        return self.tags[key]


@dataclass(frozen=True, slots=True)
class Typedef:
    name: str
    type: Type
    tags: TagRepository
    docs: Optional[str] = None
    parent: Optional[str] = None

    @property
    def struct_fields(self)->Optional[Mapping[str,"Typedef"]]:
        if isinstance(self.type, StructType):
            return self.type.fields