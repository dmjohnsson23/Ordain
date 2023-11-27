# Ordain

*Your single source of truth schema*

Ordain is a data definition langage (DDL). Or, perhaps more prescisely, a "meta-DDL" designed to eliminate the need for all the *other* DDLs in your project.

Imagine a simple web application. Your application processes some sort of structured data. Let's imagine for a moment how that data might work it's way through your application.
1. There is an HTML form for the user to enter the data, with some client-side validation
2. Javascript on the application page uses this form data to perform some action with the data
3. The data is submitted to the server, where it is validated again
4. The server sends the data to your database, which of course also has it's own definition of your data scructure
5. The data is requested again, this time via an API rather than an HTML webpage, and the data is serialized and sent to a client

Think of all the different places you may have to largely re-write the same schema code in slightly different languages or formats:
* Client-side validation
* Server-side validation
* Front-end data model
* Back-end data model
* Database creation
* API definition
* JSON serialization

Depending on your toolset, you may be able to combine *some* of these into a single schema, but it's unlikely you can combine all of them. As you update things, you may find these different schemas becoming "out of sync". Best-case scenario, it's repetetive boilerplate code.

That's where Ordain comes in. This plugin-based system is designed to allow you to define your schema once, and various plugins can perform code generation to build the nessesary bits in whatever language or library you choose.

The project currently exists as a rough draft of a basic specification, and a partially-implemented parser for the syntax.

## Some terminology

* Ordination: A schema written in Ordain format
* Priest: A plugin or tool cabable of reading and acting on an Ordination (e.g. the `php-doctrine` integration)
* Rite: A specific task performed by a priest (e.g. the `ordain php-doctrine generate` command)
* Cannon: A context which the priest recognizes, used for language and/or plugin-specific features (The PHP Doctrine plugin might recognize the `php`, `orm`, and `php-doctrine` cannons)
* High Priest: The core application suppling the root-level `ordain` commands, parsing library, and general tooling

## Ordination Files

Your Ordain schema definition file, or Ordination, is the template from which all your other schemas will be generated. The Ordination format aims to be highly declaritive, and to support all the most common features amoung the myriad of supported programming languages. The format also supports language-specific features via Cannon tags.

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
    an_array: array of 5 string'
    // You can nest structs within other structs, either a named type...
    some_data: SomeDataType
    //...or and anonymous type
    nested_struct: struct{
        other_thing: float
        created: datetime
    }
}
```

Note that this inline types are just that: inline. That means, for example, your database target would include the fields in the inline types as part of the main table (or equivilent) rather than as foreign keys, if possible in the particular database you are using.

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


### Stores

In addition to defining your data structures, you can also define stores. A store is an arbitrary name used to identify some source that data of a specific type can be saved to and loaded from, such as a table in your SQL database

```ordain
store new_data_store: SomeDataType
```

Stores enable you to define foreign-key type references in your data structure. For example:

```ordain
type Order: struct{
    // A preist targeting a databse system will interpret this as a foreign key reference rather than as "inline" data in the table (or equivilent)
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

### Cannon

There may be features supported by one target but not others. Or perhaps you want to use slightly different data types on different platforms. Or have certain fields present in only certain contexts. That's where cannon tags come in. For example, your User schema might look something like this:

```ordain
type User: struct{
    username: string
    // The only-if tag tag tells the priest to omit this field if it doesn't recognize one of the listed cannons
    #only-if php sql
    // Prefixed tags are unique to each cannon, and ignored by preiests that don't recognize the cannon. 
    // For example, one might allow incuding arbitrary code in the target language:
    #php-before-store(
        return password_hash($value)
    )
    // Another might specifiy a more specific data type for this context that, other than the once the priest might use by default
    #sql-type VARCHAR(255)
    password:string
}
```

## Basic constraints

Basic constraints and validation can be implemented using tags. Priests should recognize a set of core `check` tags, plus any additional validation tags they may wish to implement.

```ordain
type ShowSomeContraint: struct{
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

* Type contraints appear after type, all seperated by commas
* Built-in utility variables are prefixed with `$`
* Member access done via dot notation
* If the contraint begins with an operator, the value to test is always the first operand
* The value can also be referred to elsewhere as `@`





***

Everything after this point is from a previous draft and will be revised or removed.






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
