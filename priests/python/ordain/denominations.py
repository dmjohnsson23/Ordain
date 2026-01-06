from typing import Sequence, Union, Collection, Optional
from dataclasses import dataclass

@dataclass
class Denomination:
    cannon_hierarchy: Sequence[Union[str,Collection[str]]]
    _recognized_cannons: Optional[Collection[str]] = None

    @property
    def recognized_cannons(self)->Collection[str]:
        """
        A flat list of all cannons recognized by this denominations
        """
        if self._recognized_cannons is None:
            cannons = []
            for cannon_list in self.cannon_hierarchy:
                if isinstance(cannon_list, str):
                    cannons.append(cannon_list)
                else:
                    for cannon in cannon_list:
                        cannons.append(cannon)
            self._recognized_cannons = cannons
        return self._recognized_cannons


class KnownCannon:
    # Programming Languages
    Php = ['php']
    Python = ['python', 'py']

    # Serialization
    Json = ['json']

    # Database Engines
    Sql = ['sql']
    MySql = ['mysql']
    Postgres = ['postgres', 'pg']


class KnownDenomination:
    # Programming Languages
    @staticmethod
    def PhpBase()->Denomination:
        return Denomination([KnownCannon.Php])
    @staticmethod
    def PythonBase()->Denomination:
        return Denomination([KnownCannon.Python])
    
    # Serialization
    @staticmethod
    def JsonBase()->Denomination:
        return Denomination([KnownCannon.Json])
    
    # Database Engines
    @staticmethod
    def SqlBase()->Denomination:
        return Denomination([KnownCannon.Sql])
    @staticmethod
    def MySql()->Denomination:
        return Denomination([KnownCannon.MySql, KnownCannon.Sql])
    @staticmethod
    def Postgres()->Denomination:
        return Denomination([KnownCannon.Postgres, KnownCannon.Sql])