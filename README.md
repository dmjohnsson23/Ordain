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

Ordain seeks to overcome the least common denominator problem, common to all "universal abstraction" tools, by way of two principles:

1. Grant extensibility and fine-grained control of different targets by way of "Cannon tags"
2. Tools are not required to implement the full spec, and should ignore any directives they do not understand

The project currently exists as a rough draft of a basic specification, and a partially-implemented proof-of-concept parser for the syntax written in Python. Right now things are still very much conceptual.

## Some Terminology

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

The actual concrete data types a target will use to represent these types is up to the target. A target in a language with multiple list and/or mapping types could choose to represent that data in whichever concrete implementation makes sense, or expose the choice to the user. By contrast, a relational database target would likely need to create a secondary reference table to store the data, or serialize the data as a string.

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

### Inheritance

Struct inheritance is possible by specifying a typdef as having another underlying type, as follows:

```ordain
type Parent: struct{
    parent_field: int
}

type Child: Parent{
    child_field: string
}
```

When translating however, targets may choose to not implement this as "real" inheritance in the target's native type system, and instead merely copy the parent's fields and tags into the child. In fact, this behavior is actually encouraged to be the default behavior, given that many targets will not have a concept of inheritance.

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

Cannon tags can also be applied to the whole type definition:

```ordain
// Tell targets recognizing the SQL cannon that the table name for this data type is "users"
#sql.name users
type User: struct{
    // ... //
}
```

This can be useful to consolidate a common set of tags to be defined on a single type alias, rather than on every instance of the type.

```ordain
#check < 150
#check > 14
type Age: int

type User: struct{
    // The 'age' property is an int inheriting both of the previously defined check tags
    age: Age
}
```

Tags containing dots are considered "denominational", meaning they are not defined as part of the core set of tags, but rather belong to one or more specific targets. Other targets will ignore these tags. Targets may choose to recognize any arbitrary number of denominational cannons, or none at all. For example, if a code generation tool were created for the SQLAlchemy ORM, it may choose to recognize the general `python` and `sql` namespaces, as well as a more specific `sqlalchemy` namespace. Depending on its configuration, it may also recognize, for example, the `mysql` or `postgres` prefix.

Tags without dots are universal. This means that all applications should recognize these tags. However, a tool may still choose to ignore universal tags which are either not relevant to its function, or not possible for it to implement.

Following is a list of universal tags, along with their syntax and purpose:

* `only-if`/`not-if`: Specify a list of denominational prefixes that a target must recognize (or not recognize) to include this object or property. Other targets should ignore it.
* `only-in`/`not-in`: Specify a list of user-defined contexts which tools can use to conditionally include or exclude this property.
* `check`: Specify a boolean expression that should be checked (asserted) by targets implementing this object or property.
* `validate`: Specify a named validation rule that should be applied to user input. The validation rules may come from a core set, or be cannon-prefixed.
* `label`: A human-readable in-application label for this datapoint
* `required`: Takes no arguments, indicates a field is not nullable (or required)
* `readonly`: Takes no arguments, indicates a field is not read-only

Additionally, it is recommended that denominational cannons define tags with the following signatures, and targets recognizing those cannons should implement them.

* `target.get`: Code (in the target language) which should be executed when retrieving the value from the underlying representation.
* `target.set`: Code (in the target language) which should be executed when updating the value in the underlying representation, either for validation or transformation.
* `target.type`: Tell the target to implement the object or property with a certain native type.
* `target.repr`: Specify a format or method that should be used to represent the value in the target system. For example, to tell an ORM how to store a mapping in a relational database, or a JSON serializer how to store a datetime. Where relevant, targets should generally provide a way for users to define their own custom repr functions.
* `target.name`: Use a different name for this property or object in the target system.
* Cannon-scoped variants of all the universal tags, except for `only-if`/`not-if`.

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

### Repr

The repr tag is intended to allow specifying different ways of representing data in a target. Some examples of repr tags for an SQL database could be:

* `sql.repr: inline`: Embed the fields of one struct in the other when a table is created
* `sql.repr: foreign`: Create a separate table to represent a sub-struct's fields
* `sql.repr json`: Encode the field as a JSON string column
* `sql.repr php.serialize`: Language-specific serialization format for PHP
* `sql.repr py.pickle`: Language-specific serialization format for Python


### Docblock

Docblocks, in a format similar to those in Java or PHP, can be added to any definition as follows:

```ordain
/**
 * This is the documentation for the user object, in Markdown format.
 */
type User: struct{
    /**
     * Every user must have a username
     */
    username: string
}
```
## Ramblings and notes

From here on out is where I vomit out thoughts and ideas so they don't fall out my ears and get lost somewhere on the side of the road. The rest of this document is less a readme than it is an illegible stream of consciousness. But, read on if that interests you!

### Undeveloped concepts

* There will likely be a need for abstract types. (Could just be implemented with a tag? Except that tags are inherited...)
* It would be useful to allow union types.
* If we implemented traits and/or multiple inheritance, intersection types could also be useful, though I question if we want such things.
* If a property or object has a lot of tags, it could get hard to read, especially considering all the tags are before the actual definition. There could be a better syntax.
* There needs to be a concept of "contexts", which can be used similar to cannons to conditionally hide or show fields, but which are user-defined.


### Using YAML

I have also considered simply using an existing format like YAML or TOML for the Ordination files. This could provide several benefits. I think ultimately we do want a custom syntax, simply for the sake of ergonomics, but using another format as an intermediate representation could simplify development. A YAML-based format could be fairly close to what I've already defined. It is (only slightly) more verbose, but actually feels more readable than the current WIP syntax:

```yaml
Age:
    type: int
    tags:
        - check: "> 14"
        - check: "< 150"
User:
    docs: |-
        This is the documentation for the user object, in Markdown format.
    type: struct
    tags:
        - sql.name: users
    fields:
        username:
            type: string
            tags:
                - sql.primary-key: true
        password:
            type: string
            tags:
                - sql.type: "VARCHAR(255)"
                - php.set: |-
                    return password_hash($value);
        age:
            type: Age
        nested_struct:
            type: struct
            fields:
                other_thing: 
                    type: float
                created: 
                    type: datetime
```

This would also allow faster iteration: I could try out new features without needing to worry about a parser, then develop the syntax afterward, informed by the needs of actual tools.

## The hard problem of type conversion

One of the big advantages of using a system like this would be the automatic conversion functionality. The system sees both the target and destination data structure, and thus can, for example, convert the data structure to and from JSON, or to HTML inputs and from POST form data, or to parameters in a prepared SQL statement and from a received SQL result row.

However, as simple as it seems conceptually, and as easy as it is to specify such things in the syntax, it's hard to generalize "take an x and make it a y".

Essentially, we are making a highly feature-rich and type-safe serialization and deserialization framework with a shared schema. Such things exist (though usually not with a shared schema for all targets like we want) but architecturally I'm not settled on how such a framework would be built. I don't just want some massive nest of if statements. But I also don't want some over-engineered behemoth for a task that, 90% of the time, will only need a single fairly straightforward operation for each field in the structure.

Some constraints:

* The system must be recursive to allow conversion of structures with non-primitive members.
* We need to avoid trying to do jobs for which there are already better tools - I don't want this to also become an ORM with extra steps.
* However, we do need to allow deep control of the database structures that a third-party ORM would generate. The idea being code generated for any ORM would result in the same SQL table structure (or at least a compatible one).

I think we may need a phased approach to functionality. For now, I think a tool the simply takes the Ordain schema and generates code in various formats for other libraries and tools to use is all we should aim for. Let's use a JSON serialization library, an actual ORM, a form validation library, and so forth, rather than try and do much ourselves.

My current proof-of-concept is in PHP, since my current project at work is a PHP project and I would like to use this tool in that project. So, here are some popular libraries in PHP I think we could do code generation for. (Obviously I'll just pick the few I find most useful for now, not all of these.)

* respect/validation
* symfony/validation and symfony/forms
* nette/forms
* symfony/serializer
* doctrine/orm
* illuminate/database 
* cakephp/cakephp (The ORM portion of it anyway)
* propel/propel 
* nextras/orm 

Of course, Ordain is not intended as a PHP-specific tool. I know for sure I want to support Python, especially the SqlAlchemy ORM. Probably some Java-based tools as well.


## Out-of-scope concepts

Ordain is intended only to define the structure of data. We do not intend to provide features to add methods to objects, to query a database, or anything of that nature. Ordain is not a programming language or a query language. However, other tools are allowed (even encouraged) to provide this functionality while using Ordain to define the structure. For example, an Ordain-based tool would generate models for your ORM, but your ORM would be responsible to use those models to communicate with the database. An Ordain-based tool would generate the JSON schema for you API, as well as code to parse and validate that JSON. However, it would not generate the actual endpoint HTTP code, nor would it generate a full OpenAPI spec. You would use other tools better suited to that job. An Ordain-based tool would generate dataclasses or "plain old objects" in your target language, but you would write your actual business logic *in* the target language.
