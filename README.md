Ohana
=====

Ohana (the oral history accession number API) is a service that maintains a list of
accession numbers for oral histories and certain related items, mints new accession
numbers on demand, and asserts whether given accession numbers are in circulation.
This service adheres to the rules used for accessioning oral histories in the
Louie B. Nunn Center for Oral History, University of Kentucky Libraries, and may not
be appropriate for all uses.


Structure of accession numbers
------------------------------

A canonically-formed accession number consists of five components:

1. Year (numeric; always at least four
2. Type abbreviation (alphabetic, lowercase, usually 'oh')
3. Year counter (numeric, zero-padded if fewer than three digits)
4. Collection abbreviation (alphabetic, lowercase)
5. Collection counter (numeric, zero-padded if fewer than three digits)

The five components are concatenated, with an underscore placed between the third and
fourth components, to form the accession number.

For example, given the following components:

1. Year = 1985
2. Type abbreviation = OH
3. Year counter = 44
4. Collection abbreviation = A/F
5. Collection counter = 202

the resulting canonical accession number is "1985oh044_af202".


Actions supported
-----------------

The Ohana service supports the following operations:

* Mint a new accession number.
* Check the state of an accession number.
* Record a preexisting accession number.
* Circulate an accession number.
* Revoke an accession number.

The preconditions and results of each operation are described in the
file [api/ohana.php](api/ohana.php) .


Required properties
-------------------

Accession numbers must satisfy the following properties:

* No two accession numbers are identical.
* The actions of circulating and revoking existing accession numbers must not have any effect
  on any other accession numbers, whether currently existing or minted later.
* When Ohana accepts a preexisting accession number, it stores not only the canonical rendering
  of the accession number but also the exact form in which it was submitted.
* No two accession numbers with the same type abbreviation and year may have the same year counter.
* No two accession numbers with the same type abbreviation and collection may have the same
  collection counter.

Moreover, Ohana must handle exactly one request at a time.  Clients must wait their turn, timing out
if necessary.


Files
-----

* main library - [api/ohana.php](api/ohana.php)
* tests - [api/test.php](api/test.php)
* database config - config/database.yml, example in [config/database.yml.example](config/database.yml.example]
* database schema - [config/ohana.sql](config/ohana.sql)


License
-------

This program is copyright (C) 2015 Michael Slone.  See [LICENSE](LICENSE) for details.
