from .model import *
from .denominations import Denomination

class DenominationalTypedefView:
    """
    This ia a wrapper around a typedef adjusted for a specific cannon hierarchy.
    """
    def __init__(self, name: str, typedef: Typedef, model: Mapping[str,Typedef], denomination: Denomination):
        self._name = name
        self._typedef = typedef
        self._model = model
        self._denomination = denomination
        self._tag_heritage = None
    
    @classmethod
    def from_model(cls, name: str, model: Mapping[str,Typedef], denomination: Denomination):
        return cls(name, model[name], model, denomination)
    
    @classmethod
    def for_typedef(cls, typedef: Typedef, model: Mapping[str,Typedef], denomination: Denomination):
        return cls(typedef.name, typedef, model, denomination)

    @property
    def is_ignored(self):
        """
        Indicates if this typedef should be ignored for the current denominational view
        """
        # If any ignore tags are present in the filtered tag view, ignore
        tag = self.tag_search_top('ignore')
        if tag is not None and tag.value:
            return True
        # If there is a universal not-if tag that list one of our recognized cannons, ignore
        # (If there are multiple not-if tags, only the last one is used)
        tag = self.tag_search_top_universal('not-if')
        if tag is not None:
            if not set(str(tag.value).split()).union(self._denomination.recognized_cannons):
                return True
        # If there is an only-if tag, and it does not list any cannons we recognize, ignore
        # (If there are multiple only-if tags, only the last one is used)
        tag = self.tag_search_top_universal('only-if')
        if tag is not None:
            if set(str(tag.value).split()).union(self._denomination.recognized_cannons):
                return True
        # If no reason was found to ignore, then don't
        return False
    
    @property
    def name(self)->str:
        """
        The type name (adjusted for the current cannon)
        """
        name_tag = self.tag_search_top('name', inheritable=False)
        return str(name_tag.value) if name_tag else self._name
    
    @property
    def typedef(self)->Typedef:
        """
        The underlying typedef being represented
        """
        return self._typedef
    
    @property
    def denomination(self):
        """
        The hierarchical list of cannons, for resolving conflicts
        """
        return self._denomination
    
    @property
    def impl(self)->Optional[Tag]:
        """
        Specifies an underlying implementation type; or None to use the default
        """
        return self.tag_search_top('impl')
    
    @property
    def struct_field_views(self)->Optional[Mapping[str,"DenominationalTypedefView"]]:
        """
        Get the denominational views for all the struct fields, if applicable. 

        Only includes fields which should not be ignored.
        """
        fields = self._typedef.struct_fields
        if fields is None:
            return None
        views = {}
        for key, typedef in fields.items():
            view = DenominationalTypedefView(key, typedef, self._model, self._denomination)
            if not view.is_ignored:
                views[key] = view
        return views
    
    @property
    def tag_heritage(self)->TagRepository:
        if self._tag_heritage is None:
            tags = self._typedef.tags
            typedef = self._typedef
            while typedef.parent is not None:
                typedef = self._model[typedef.parent]
                tags = typedef.tags.merge(tags)
            self._tag_heritage = tags
        return self._tag_heritage
    
    def tag_search_top(self, tag_name:str, filter_cannons: Optional[Collection[str]] = None, include_universal: bool = True, inheritable: bool = True)->Optional[Tag]:
        """
        Search for a denominational tag, returning only the best match.

        This is used for tags which overwrite one another rather than stacking.

        :param tag_name: The plain tag name (without cannon or context) to search for.
        :param filter_cannons: An optional list of cannons to filter by. If None, the view's list of cannons will be used.
        :param include_universal: Universal tags are also included by default, but can be ignored if desired.
        :param inheritable: Tags are considered inheritable by default, but inherited tags can be ignored if desired.
        """
        if filter_cannons is None:
            filter_cannons = self._denomination.recognized_cannons
        tags = self.tag_heritage if inheritable else self._typedef.tags
        return tags.filter(tag_name, filter_cannons, include_universal).get_top(*self._denomination.cannon_hierarchy)
    
    def tag_search_all(self, tag_name:str, filter_cannons: Optional[Collection[str]] = None, include_universal: bool = True, inheritable: bool = True)->TagRepository:
        """
        Search for a denominational tag, returning all matches.

        This is used for tags which stack rather than overwriting one another. Tags will be 
        returned in the order they appear in the source.

        :param tag_name: The plain tag name (without cannon or context) to search for.
        :param filter_cannons: An optional list of cannons to filter by. If None, the view's list of cannons will be used.
        :param include_universal: Universal tags are also included by default, but can be ignored if desired.
        :param inheritable: Tags are considered inheritable by default, but inherited tags can be ignored if desired.
        """
        if filter_cannons is None:
            filter_cannons = self._denomination.recognized_cannons
        tags = self.tag_heritage if inheritable else self._typedef.tags
        return tags.filter(tag_name, filter_cannons, include_universal)
    
    def tag_search_all_ordered(self, tag_name:str, filter_cannons: Optional[Collection[str]] = None, include_universal: bool = True, inheritable: bool = True)->TagRepository:
        """
        Search for a denominational tag, returning all matches.

        This is used for tags which stack rather than overwriting one another. Tags will be sorted 
        with better or more specific matches first, followed by less specific matches.

        :param tag_name: The plain tag name (without cannon or context) to search for.
        :param filter_cannons: An optional list of cannons to filter by. If None, the view's list of cannons will be used.
        :param include_universal: Universal tags are also included by default, but can be ignored if desired.
        :param inheritable: Tags are considered inheritable by default, but inherited tags can be ignored if desired.
        """
        if filter_cannons is None:
            filter_cannons = self._denomination.recognized_cannons
        tags = self.tag_heritage if inheritable else self._typedef.tags
        return tags.filter(tag_name, filter_cannons, include_universal).sort(*self._denomination.cannon_hierarchy)
    
    def tag_search_top_universal(self, tag_name:str, inheritable: bool = True)->Optional[Tag]:
        """
        Search for a universal tag, returning only the best match. All denominational tags are ignored.

        This is used for tags which overwrite one another rather than stacking.

        :param tag_name: The plain tag name (without context) to search for.
        :param inheritable: Tags are considered inheritable by default, but inherited tags can be ignored if desired.
        """
        tags = self.tag_heritage if inheritable else self._typedef.tags
        return tags.filter(tag_name).get_top()
    
    def tag_search_all_universal(self, tag_name:str, inheritable: bool = True)->TagRepository:
        """
        Search for a universal tag, returning all matches. All denominational tags are ignored.

        This is used for tags which stack rather than overwriting one another. Tags will be 
        returned in the order they appear in the source.

        :param tag_name: The plain tag name (without context) to search for.
        :param inheritable: Tags are considered inheritable by default, but inherited tags can be ignored if desired.
        """
        tags = self.tag_heritage if inheritable else self._typedef.tags
        return tags.filter(tag_name)
    
    def tag_search_all_universal_sorted(self, tag_name:str, inheritable: bool = True)->TagRepository:
        """
        Search for a universal tag, returning all matches. All denominational tags are ignored.

        This is used for tags which stack rather than overwriting one another. Tags will be sorted 
        with better or more specific matches first, followed by less specific matches.

        :param tag_name: The plain tag name (without context) to search for.
        :param inheritable: Tags are considered inheritable by default, but inherited tags can be ignored if desired.
        """
        tags = self.tag_heritage if inheritable else self._typedef.tags
        return tags.filter(tag_name).sort()