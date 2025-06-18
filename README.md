# common-php

- Generic Stream class
- Generic StringHelper class

# Documentation de la Classe Stream

La classe `Stream` est une classe utilitaire qui permet de manipuler des flux de données sous forme de tableaux. Elle offre plusieurs méthodes pour effectuer diverses opérations sur les données contenues dans le flux. Cette classe peut être utilisée pour simplifier le traitement et la manipulation de collections de données.

## Méthodes statiques

### `empty() : Stream`
Crée et retourne un nouveau flux vide.

### `fill(int $start_index, int $count, $value) : Stream`
Crée et retourne un nouveau flux contenant un nombre spécifié de valeurs identiques, à partir d'un index de départ.

### `diff($a, $b, bool $unidirectional = false, bool $insensitive = false) : Stream`
Compare les éléments de deux flux ou tableaux et retourne les différences. L'option `$unidirectional` permet de déterminer si la comparaison doit être unidirectionnelle. L'option `$insensitive` permet d'effectuer une comparaison insensible à la casse.

### `explode($delimiters, $value) : Stream`
Divise une chaîne en un flux de sous-chaînes en utilisant les délimiteurs spécifiés. Les éléments vides résultant de la division sont filtrés.

### `keys($array) : Stream`
Retourne un flux contenant les clés d'un tableau associatif.

### `from($array, ...$others) : Stream`
Crée un nouveau flux à partir d'un tableau, d'un autre flux ou d'une structure itérable. Plusieurs sources peuvent être combinées en utilisant les paramètres `$others`.

## Méthodes d'instance

La plupart des méthodes d'instance modifient le flux actuel et retournent le flux lui-même, ce qui permet de les chaîner.

### Méthodes de manipulation

- `filter(?callable $callback = null) : Stream` : Filtre les éléments du flux en fonction du callback spécifié.
- `reverse() : Stream` : Inverse l'ordre des éléments dans le flux.
- `sort(callable $callback = NULL) : Stream` : Trie les éléments du flux en utilisant la fonction de comparaison fournie.
- `ksort(int $flags = SORT_REGULAR) : Stream` : Trie les éléments du flux par clés.
- `map(callable $callback) : Stream` : Applique une fonction à chaque élément du flux.
- `reduce(callable $callback, $initial = 0)` : Réduit les éléments du flux à une seule valeur en utilisant une fonction de réduction.
- `flatMap(callable $callback)` : Applique une fonction à chaque élément du flux et aplatit le résultat en un seul flux.
- `flatten() : Stream` : Aplatit le flux en un seul niveau.

### Méthodes de recherche

- `first($default = null)` : Retourne le premier élément du flux.
- `last($default = null)` : Retourne le dernier élément du flux.
- `indexOf($needle)` : Retourne l'indice de la première occurrence de l'élément donné dans le flux.

### Méthodes d'agrégation

- `min()` : Retourne la valeur minimale dans le flux.
- `max()` : Retourne la valeur maximale dans le flux.
- `sum(int $decimals = 2) : float` : Calcule la somme des éléments numériques dans le flux.

### Méthodes de transformation

- `toArray() : array` : Convertit le flux en un tableau.
- `json() : string` : Convertit le flux en une représentation JSON.
- `values() : array` : Retourne les valeurs du flux sous forme de tableau.

### Méthodes de vérification

- `isEmpty() : bool` : Vérifie si le flux est vide.
- `count() : int` : Retourne le nombre d'éléments dans le flux.

### Méthodes d'itération

- `each(callable $callback) : Stream` : Applique une fonction à chaque élément du flux.
- `join($glue) : string` : Joint les éléments du flux en une chaîne en utilisant un séparateur.

### Méthodes de vérification conditionnelle

- `every(callable $callback = null) : bool` : Vérifie si tous les éléments du flux satisfont une condition.
- `some(callable $callback) : bool` : Vérifie si au moins un élément du flux satisfait une condition.
- `hasAtLeast(callable $callback, int $limit) : bool` : Vérifie si au moins un certain nombre de élément du flux satisfait une condition.

### Méthodes d'accès aux éléments

- `offsetExists($offset) : bool` : Vérifie si un index existe dans le flux.
- `offsetGet($offset) : mixed` : Récupère la valeur d'un élément en utilisant son index.
- `offsetSet($offset, $value) : void` : Modifie la valeur d'un élément en utilisant son index.
- `offsetUnset($offset) : void` : Supprime un élément en utilisant son index.

### Méthodes de modification

- `push(mixed ...$values) : Stream` : Ajoute des éléments à la fin du flux.
- `unshift(mixed ...$values) : Stream` : Ajoute des éléments au début du flux.
- `set(string|int $key, mixed $value) : Stream` : Modifie la valeur d'un élément en utilisant sa clé.
- `unset(string|int $key) : Stream` : Supprime un élément en utilisant sa clé.

### Méthodes de recherche avancée

- `find(callable $callback) : mixed` : Retourne le premier élément du flux satisfaisant une condition.
- `findKey(callable $callback) : mixed` : Retourne la clé du premier élément du flux satisfaisant une condition.
- `intersect(array $array, bool $byKey = false) : Stream` : Intersecte les éléments du flux avec un tableau.

---

Cette documentation couvre les principales fonctionnalités et méthodes de la classe `Stream`. Vous pouvez maintenant utiliser cette classe pour manipuler et traiter vos données de manière plus efficace en utilisant les opérations de flux fournies.
