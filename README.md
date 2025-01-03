# Ordain

*Your single source of truth schema*

Ordain is a data definition language (DDL). Or, perhaps more precisely, a "meta-DDL" designed to eliminate the need for all the *other* DDLs in your project.

Imagine a simple web application. Your application processes some sort of structured data. Let's imagine for a moment how that data might work it's way through your application.
1. There is an HTML form for the user to enter the data, with some client-side validation
2. Javascript on the application page uses this form data to perform some action with the data
3. The data is submitted to the server, where it is validated again
4. The server sends the data to your database, which of course also has its own definition of your data structure
5. The data is requested again, this time via an API rather than an HTML webpage, and the data is serialized and sent to a client

Think of all the different places you may have to largely re-write the same schema code in slightly different languages or formats:
* Client-side validation
* Server-side validation
* Front-end data model
* Back-end data model
* Database creation
* API definition
* JSON serialization

Depending on your toolset, you may be able to combine *some* of these into a single schema, but it's unlikely you can combine all of them. As you update things, you may find these different schemas becoming "out of sync". Best-case scenario, it's repetitive boilerplate code.

That's where Ordain comes in. Ordain is not designed as a single tool, but rather a common language that could theoretically be used by several different tools or libraries to perform tasks such as validation, code generation, and so forth.

The project currently exists as a rough draft of a basic specification, and a partially-implemented proof-of-concept parser for the syntax written in Python.

# Some Terminology

An *Ordination* is a schema written in the Ordain format.

A *Target* is an umbrella term used to refer to any tool, library, framework, database engine, or application which intends to directly read or interact with ordinations. Sticking with the theme, we may also sometimes refer to these as *Priests*.

The *Cannon* is the set of all tags recognized by a given target, as will be explained later.

## Ordination Files

Your Ordain schema definition file, or Ordination, is the centralized definition of your data format. The Ordination format aims to be highly declarative, and to support all the most common features among the myriad of supported programming languages. The format also supports language-specific features via Cannon tags.

### Data structures

At it's most basic, your Ordination will likely looks something like this:

```ordain
type SomeDataType: struct{
   val1: int
   val2: string
}
```

New types are declared using the `type` keyword, followed by the type name, then by a type definition. In this example, we define the type as a struct with two fields: an int and a string.

Of course, your data structures don't have to be so simple:

```ordain
type ComplexProperties: struct{
    // A list is an arbitrary-length ordered collection of a specific type 
    a_list: list of string
    // A mapping is a key-value pair mapping any primitive type to any other type
    a_mapping: mapping of string to int
    // And an array is a fixed-length collection of a specific type
    an_array: array of 5 string
    // You can nest structs within other structs, either a named type...
    some_data: SomeDataType
    //...or and anonymous type
    nested_struct: struct{
        other_thing: float
        created: datetime
    }
}
```

The actual concrete data types a target will use to represent these types is up to the target. A target in a language with multiple list and/or mapping types could choose to represent that data in whichever concrete implementation makes sense, or expose the choice to the user. By contrast, a relational database target would likely need to create a secondary reference table to store the data, as relational database systems do not have a native list or mapping datatype.

Targets may also choose to treat inline types differently than named types. For example, a database engine might implement named types as foreign keys, but inline types merely as additional columns in the outer table.

Targets could of course customize these behavior based on Cannon tags (see "Cannon" below).

You can also create enums:

```ordain
type SomeEnum: enum {thing1 thing2}
// Enums don't have to use ints, you can specify a different backing type
// String enums use the element name as the value unless otherwise specified
type StringEnum: enum of string {stuff things}
// You can manually specify both the type and value
// Manually-specified values are required for any types except int and string
type EnumWithManualValues: enum of int{one=1 two=2}
```

Types can be aliased as well. You'll see how this can be useful once we get into type parameters and cannon tags.

```ordain
type Age: int
```

The full list of data types is:

* `int` (This does not distinguish different binary lengths or signed/unsigned variants; but this can be done with cannon tags.)
* `float`
* `decimal`
* `string` (Targets should deal with text encoding as they see fit, and allow specifying encoding via cannon tags if needed. UTF-8 is the recommended default, unless the target has good reason to use a different encoding by default.)
* `binary`
* `date`
* `time`
* `datetime`
* `bool`
* `struct` (Equivalent to an object; a definition is required.)
* `list` (Variable-length sequence.)
* `array` (Fixed-length sequence.)
* `mapping`
* `enum` (Has a more concrete backing type; a definition is required.)
* `any` (Use this in conjunction with cannon tags to define a type when Ordain's type system is insufficient to represent your data.)

Targets are free to "downcast" types the can't natively support. For example, a JSON serialization tool would have to represent `datetime` with a string.

### The Cannon

There may be features supported by one target but not others. Or perhaps you want to use slightly different data types on different platforms. Or have certain fields present in only certain contexts. That's where cannon tags come in. For example, your User schema might look something like this:

```ordain
type User: struct{
    username: string
    // The only-if tag tag tells the target to omit this field if it doesn't recognize one of the listed cannons
    #only-if php sql
    // Prefixed tags are unique to each cannon, and ignored by targets that don't recognize the cannon. 
    // For example, one might allow including arbitrary code in the target language:
    #php.set(
        return password_hash($value)
    )
    // Another might specify a more specific data type for this context that, other than the one the target might use by default
    #sql.type VARCHAR(255)
    password:string
}
```

Tags containing dots are considered "denominational", meaning they are not defined as part of the core set of tags, but rather belong to one specific target. Other tools or libraries will ignore these tags. Targets may choose to recognize any arbitrary number of denominational cannons, or none at all. For example, if a code generation tool where created for the SQLAlchemy ORM, it may choose to recognize the general `python` and `sql` namespaces, as well as a more specific `sqlalchemy` namespace. Depending on its configuration, it may also recognize, for example, the `mysql` or `postgres` prefix.

Tags without dots are universal. This means that all applications should recognize these tags. However, a tool may still choose to ignore universal tags which are either not relevant to its function, or not possible for it to implement.

Following is a list of universal tags, along with their syntax and purpose:

* `only-if`/`not-if`: Specify a list of denominational prefixes that a target must recognize (or not recognize) to include this object or property. Other targets should ignore it.
* `check`: Specify a boolean expression that should be checked (asserted) by targets implementing this object or property.
* `validate`: Specify a named validation rule that should be applied to user input. The validation rules may come from a core set, or be cannon-prefixed.

Additionally, it is recommended that denominational cannons implement tags with the following signatures:

* `target.get`: Code (in the target language) which should be executed when retrieving the value from the underlying representation.
* `target.set`: Code (in the target language) which should be executed when updating the value in the underlying representation, either for validation or transformation.
* `target.type`: Tell the target to implement the object or property with a certain native type.
* `target.check` and `target.validate`: Cannon-scoped variants of the universal tags by the same name.

If a target recognizes multiple cannon prefixes, situations may arise where tags conflict. Targets should use the following process to resolve conflicts:

1. If one cannon is more specific than the other, the more specific cannon wins. For example, if both `sql.type` and `postgres.type` were specified, a Postgres-related target would use the `postgres.type` tag.
2. If the tool considers both tags to have equal specificity, whichever tag appears *last* should be used.

Targets are free to define specificity however makes sense to them. However, it is recommended to follow this hierarchy, in order from least to most specific:

1. The target's base language (e.g. `python`, `sql`)
2. A more specific language dialect (e.g. `mysql`)
3. A specific library or framework (e.g. `php-doctrine`)
4. The specific tool or action in use

This conflict resolution procedure is only needed in situations where there is actually a conflict. There is likely no reason why, for example, two different `check` conditions could not both be applied to the same object, but it is certainly not possible for two different `type` expressions to simultaneously apply.

### Basic constraints

Basic constraints and validation can be implemented using tags. Targets should recognize the universal `check` and `validate`, though may choose to ignore validation rules they aren't able to accommodate. They may define additional denominational validation rules that other targets would not recognize. If needed, they may also define entirely new denominational tags to represent more complex constraints.

```ordain
type ShowSomeConstraint: struct{
    #check-range 0 150
    age: int

    #check <= $now.year
    birth_year: int

    #check $length < 50
    #check regex /[A-Za-z]+/
    first_name: string

    #validate email
    email: string
}
```

* Type constraints appear after type, all separated by commas
* Built-in utility variables are prefixed with `$`
* Member access done via dot notation
* If the constraint begins with an operator, the value to test is always the first operand
* The value can also be referred to elsewhere as `@`

Check tags are used for simple checks that would be preformed at every step of the application; and are intended more as assertions than validation tools. For more serious validation, more special-purpose denominational tags are likely what is wanted.

***

Everything after this point is from a previous draft and will be revised or removed.



### Stores

In addition to defining your data structures, you can also define stores. A store is an arbitrary name used to identify some source that data of a specific type can be saved to and loaded from, such as a table in your SQL database

```ordain
store new_data_store: SomeDataType
```

Stores enable you to define foreign-key type references in your data structure. For example:

```ordain
type Order: struct{
    // A database system target will interpret this as a foreign key reference rather than as "inline" data in the table (or equivalent)
    // Syntax is &Typename from store
    customer: &Customer from customers
    items: list of struct{
        item: &Item from inventory
        quantity: int = 1
        price: decimal
        discount: ?decimal
    }
    total: decimal
}
```

References can optionally be set to allow objects only from a specific store with the `from` keyword. There is no dereference operator, as dereferencing is done automatically within ordain code. In the host language, it may be a different story however. Options for how this might be done in various host languages:


## Encapsulation

```ordain
type Address: struct{
    number: string
    street: string
    city: string
    state: string
    zip: string
}
type Contact: struct{
    name: string
    email: string
    address: Address
    phones: list of type PhoneNumber: struct{
        number: string
        label: enum of string {cell, home, work}
        preferred: bool, default false
    }, (count preferred) <= 1
}
```

* Encapsulated types can be declared internally or externally
* Type name is optional if type is declared internally (see label enum)
* Parenthesis set order of operations, optional otherwise

## Type Unions and Dynamic Type

```ordain
type JsonEncodeable: 
    int |
    float |
    bool |
    string |
    list of JsonEncodable |
    mapping of string to JsonEncodable


store key_value_store: {
    key: string
    value: any
}, primary_key key
```

* Union datatypes delinated with the | symbol
* Special `any` datatype is a union of all types
* Incomplete statement causes newline not to be interpreted as end of statement

## Queries

Queries are a feature of stores. The intention is for them to be static, saved endpoints that are to be queried. Because of the Ordain's nature and purpose, ad-hoc queries don't make a lot of sense.

As a feature of stores, they are part of the store definition. Hence the comma that appears after `Customer` below, which causes the definition to continue to the next line.

```ordain
store customers: Customer,
query active_by_state{
    params: state
    filter: address.state=state and active
    return: name, address
}
```

The query will be called in the host language, not in Ordain syntax, therefore calling syntax will very depending on the programming langauge being used. But it might look something like `db.customers.active_by_state("NY")`

Search queries, which may or may not be named, perform weighted full-text searches against predefined fields. An unnamed search query will be automatically assigned the name "search"

```ordain
store articles: Article,
search{
    match: title 20, summary 5, content
}
```

Search queries may also optionally take parameters to use as weights, instead of hard-coded weights.

Indexes are query endpoints that take a key or range of keys.

```ordain
store articles: Article,
index by_date: date
```

```python
articles_this_month = db.articles.by_date(first_of_year, end_of_year)
```

Indexes are not unique unless specified to be. More complex indexes (e.g. more than just a simple key) require full bodies in braces. Also, square brackets indicate that the list is to be destructured and operations applied to each member.

```ordain
store shopping_carts: Cart,
index payments_by_date: {
    key: payments[].date,
    order: desc
    return: payments[]
}
```

```python
payments_made_today = db.shopping_carts.payments_by_date(date.today())
payments_made_this_month = db.shopping_carts.payments_by_date(first_of_month, last_of_month)
```

A `primary_key` is also a unique index, though it is not named and its index method calls are made directly on the store object in the host langauge.

```ordain
store key_value_store: {
    key: string
    value: any
}, primary_key{
    key: key
    return: value
}
```

```python
value = db.key_value_store(key)
## OR ##
value = db.key_value_store[key]
# (However the host langague wants to do it. Ordain doesn't care.)
```

## Foreign Keys / Pointers

Linking to an object of another type can be done via encapsulation (as seen above) or alternatviely, though a reference / pointer / foreign key.

```ordain
type Order: struct{
    customer: &Customer
    items: list of struct{
        item: &Item from inventory
        quantity: int, default 1
        price: decimal
        discount: decimal?
    }
    total: decimal
}
```

References can optionally be set to allow objects only from a specific store with the `from` keyword. There is no dereference operator, as dereferencing is done automatically within ordain code. In the host language, it may be a different story however. Options for how this might be done in various host languages:

* Getter / Setter methods
* Python-style properties
* Rust-style smart pointers
* Proxy objects
