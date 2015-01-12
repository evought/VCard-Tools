README.md

Eric Vought

2014-12-8 VCard-Tools PHP toolbox

MIT License http://opensource.org/licenses/MIT

#What This Project Is#

This effort is a set of tools for manipulating VCards (RFC 6350) in PHP,
including in-memory representation, database persistence, and HTML templating.
The tools build on two other works, the VCard PHP class by Martins Pilsetnieks,
Roberts Bruveris (https://github.com/nuovo/vCard-parser) and a sample VCard SQL
schema by George_h on Stackoverflow for RFC6868/VCard 3.0.
VCard-Tools marries an improved VCard class with a set of classes for database
persistence (based on George_h's schema) and flexible HTML output targeting
VCard 4.0 (RFC 6350) compliance, defined in the EVought\vCardTools namespace.
It also includes a growing suite of PHPUnit tests.

This file describes the project, setup, and basic usage.
Also see the [Wiki](https://github.com/evought/VCard-Tools/wiki) for additional
information, including class hierarchy and design discussion.

The package is currently a pre-release and should not be considered production
code.
The GitHub repository lays out milestones and schedule toward a 1.0 release and
the anticipated features.

#License#

All components are under the [MIT License](license.txt).

#Components#

The project consists of php classes in the EVought\vCardTools namespace.
The primary classes of interest are vCard, VCardDB, and Template.
The sql schema (MySQL) is defined in sql/, documentation materials in doc/, and
PHPUnit test cases under tests/.
Pre-defined templates are in src/templates/.
Setting up the database is described below.
There is a [Phing](http://www.phing.info/) script for automating project
maintenance tasks. I also set things up to be usable from NetBeans IDE.

Internal dependencies are partitioned out.
VCard may be used without the Template or VCardDB classes.
The templating code can be used along with the original VCard class *with or
without the database persistence*, and the database persistance does not require
the use of the Template class.

The HTML templating is designed with CSS styling in mind.
Classes and roles are clearly indicated in the markup as styling hooks and it
is simple to present different output for, say, Individual versus Organization
or summary versus full information.
In a live website, I intend that it should be easy for the user to go from a
search-list to individual detailed listings, export the raw .vcf text or even,
potentially, generate a QR code.
I expect to add a template for proper hcard output, but it should not be
difficult (at all) for someone to use the mechanism to produce a conformant
hcard using the existing code.

The Database code is intended to be adaptable to somewhat different database
setups, and this will continue as a future design direction.
SQL queries used to fetch/store/search VCards are defined in an external .ini
file to both ease maintenance and enable customization.

##Autoloading##

The source files and classes are laid out to be PSR-4 compliant and should
therefore work with compliant autoloaders.
Composer generates such an autoloader automatically, which may be included
from vendor/autoload.php.

##Requirements##

The project was developed against
* PHP 5.5 (5.5.18)
* MySQL 5.5 (5.5.38)

Dependencies are tracked through [Composer](https://getcomposer.org/).
The Composer tool, with its configuration in composer.json obtains, tracks, and
installs the components needed to build and use VCardTools.
Composer also provides the autoloading script required to use the vCardTools
classes in an application.

_NOTE_: Due to issue [#94](https://github.com/evought/VCard-Tools/issues/94) the
latest Composer snapshot is required.
The underlying defect in Composer has been fixed but has not yet been packaged
in a regular release.

Composer will locate and install two other php libraries required by VCardTools,

* [rhumsaa/uuid](https://github.com/ramsey/uuid): A tool for generating
RFC 4122 version 1, 3, 4, and 5 universally unique identifiers (UUID).

* [evought/data-uri](https://gist.github.com/evought/b817103313f5ddff5817): 
A convenient class for working with Data URIs in PHP

To develop and run tests, composer will also install:

* Phing
* PHPUnit
* PHPDocumentor

and their dependencies.

I am doing most of my development on a Intel-based Fedora Linux 20 workstation.

# Installation of Sources #

After checking the package out, "composer install" should fetch and install the
required dependencies into the vendor/ subdirectory. Various tools will be
installed into vendor/bin. If you are running in a Unix-like environment, you
can:

    $ . ./dotme.sh

To add vendor/bin to your path.

The dependency versions will be locked to those specified in the composer.lock
file. To force update to newer version of dependencies, "composer update" is
required.

#Database Setup#

To use the software, a database (e.g. VCARD) will be needed to contain the
tables and a user with appropriate permissions to access it.
Given appropriate settings and a user with appropriate permissions in the
database, phing will do the actual loading of the schema for you from the schema
definition in src/sql/vcard.sql.

First off, you need a MySQL user account with permissions to create a database and
grant privileges on that database to a test user.
I refer to this as the *developer account*.
You could use the MySQL *root* user to do this, but on general principle, it is
better to have a developer account with less that super-user powers and yet
more than your unit test account.
In some environments, such as a shared database, you may not have the option of
using the root account.
Depending on your setup, this can be done with a tool such as *PHPMyAdmin* or
from the command-line. In most environments:

    $ mysql --user=root -p

Will pull up a SQL shell as the MySQL root user, prompting for the password.
You may then create the desired account and grant it privileges:

    mysql> create user 'developer'@'localhost' identified by 'password';
    mysql> grant all on VCARD.* to 'developer'@'localhost' with grant option;

Substituting whatever is appropriate for 'developer' and 'password'.
At the same time, create a *test account* for actually running the tests:

    mysql> create user 'test-vcard'@'localhost' identified by 'password';

Create a ${env.USER}.properties file in your project folder, copying and editing
values from db.properties to set the username, password, host, database name, etc., for your database. (In other words, I would put these settings in 'evought.properties'). As your personal property file will not be under control of git, you won't have to worry about committing your settings (and password!) back to the repository. The phing build script, build.xml will use these settings
to build some configuration files, initialize the database, and run the tests.

Running 'phing config' will build the configuration files (such as database.php) needed. Anytime you change these settings, you will want to run:

    $ phing cleanConfig && phing config

To force them to be rebuilt. You can then:

    $ phing unitDBSchema

This will automatically invoke "phing createUnitDB" to create the database and
grant permissions to your test user (SELECT, INSERT, UPDATE, and DELETE privileges on VCARD.*) and then it will load the schema definitions from sql/vcard.sql. If you need to, you can always load vcard.sql manually from a terminal or by pasting it into a query in PHPMyAdmin.

The unit tests will automatically delete new rows and reset the table state after each run, so you *should not* have to clean and reset the database yourself unless something has happened to disrupt its state or you have intentionally made changes to the data or the schema. If that happens:

   $ phing dropUnitDB && phing createUnitDB

Will recreate the tables. The unit tests rely on an xml table dump to set/reset
the initial state for the testcases. If the schema has changed, you will want to recreate this file by running (e.g.):

    $mysqldump --xml -t -u [username] -p [database] > tests/emptyVCARDDB.xml

See the PHPUnit Manual, [Database Testing Chapter](https://phpunit.de/manual/current/en/database.html#database.available-implementations) for more information.

## Customizing Queries##

Individual SQL queries are configured in src/sql/VCardDBQueries.ini, but you
should not need to make changes in that file.
Create your own .ini file containing only the queries you need to customize,
and pass that .ini file to the VCardDB constructor.
VCardDB will use the default queries in VCardDBQueries.ini for anything it
cannot find in your custom file.
Work from the default.ini file to ensure that expected return columns and
named parameters match.
Within that constraint, it should be possible to join/split tables or invoke
stored procedures if desired to integrate VCardDB into a larger application
schema.

#Documentation and Examples#

Aside from the code comments, one of the best resources for using the classes
are the test cases.
There are as yet no publicly visible demonstrations of the class.

API documentation is generated by the doc phing task:

    $ phing doc

and will be placed in docs/api. cleanDocs will remove the generated documents.


# Unit Tests #

Running the unit tests requires [PhpUnit](https://phpunit.de/) to be installed.
If it is in the path, the full test suite can be run with the *test* phing task:

    $ phing test

This will also generate a test coverage report in the reports/ directory.

_NOTE_: See Issue #59 if you have failing testcases.

A phpunit.xml file is provided to run tests manually from the command-line:

    $ phpunit tests/VCardTests.php

Should work and will give more detailed output when tests actually fail.
I also routinely run tests under NetBeans IDE. Running the entire suite or
individual tests under NetBeans will work smoothly if you configure the
project to use bootstrap.php with phpunit (Project->Properties->Testing).
