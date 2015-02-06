README.md

Eric Vought

2014-12-8 VCard-Tools PHP toolbox

MIT License http://opensource.org/licenses/MIT

Composer Package: evought/vcard-tools

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
* PHP 5.5 (5.5.20)
* MySQL 5.5 (5.5.38)

The library is known to _not_ work with PHP 5.4, but the Continuous Integration
server does regularly test against [hhvm](http://hhvm.com/) (Hip Hop Virtual
Machine).

Dependencies are tracked through [Composer](https://getcomposer.org/).
The Composer tool, with its configuration in composer.json obtains, tracks, and
installs the components needed to build and use VCardTools.
Composer also provides the autoloading script required to use the vCardTools
classes in an application.

Composer will locate and install two other php libraries required by VCardTools,

* [rhumsaa/uuid](https://github.com/ramsey/uuid): A tool for generating
RFC 4122 version 1, 3, 4, and 5 universally unique identifiers (UUID).

* [evought/data-uri](https://github.com/evought/DataURI): 
A convenient class for working with Data URIs in PHP.

To develop and run tests, composer will also install:

* Phing
* PHPUnit
* PHPDocumentor

and their dependencies.

I am doing most of my development on a Intel-based Fedora Linux 20 workstation
and the Continuous Integration server regularly tests the code in a clean
Ubuntu Linux environment.

# Including Via Composer #

To include this library in a larger project via Composer, you first must have
a [composer.json](https://getcomposer.org/doc/00-intro.md) for your project.
That file must include the following extra setting:

    "require": {
            "evought/vcard-tools": "dev-master"
        }

If you are only using the non-database portions of VCard-Tools, this should
be sufficient to get you going and a "composer install" should fetch and install
this package. If you are also using the database portions, things are a bit
more complicated because you will have to install the database schema into
your database as well and be able to update the schema as you upgrade to
new versions of VCard-Tools. VCard-Tools provides utilities to make that
process easier and allows you to customize the process if your situation is
unusual. You will want to read the Database Setup section thoroughly.
If you check out the sources for this package in a separate
directory, set it up, and run its tests to see how it works stand-alone, that
may help you understand how to set up your project around it.

If using the database components, you will need to add additional dependencies
to your project's composer.json file:

    "require-dev": {
        "phing/phing": "2.*",
        "robmorgan/phinx": "*"
    },

These settings will install the Phing project automation tool and the Phinx
database migration tool.

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
database, phing and phinx will do the actual loading of the schema for you.

These notes are targeted at the user working with the checked-out source of
VCard-Tools. In that case, the tasks to setup the database are already in
the build.xml file. If you are including this library in another project,
you will need to make your own build.xml. Notes on how to do that are in
the section "Your Project's build.xml".

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

Create a ${env.USER}.properties file in your project folder, copying and editing
values from db.properties to set the username, password, host, database name,
etc., for your database.
(In other words, I would put these settings in 'evought.properties').
As your personal property file will not be under control of git, you won't have
to worry about committing your settings (and password!) back to the repository.
The phing build script, build.xml will use these settings
to build some configuration files, initialize the database, and run the tests.
Notice the settings for a unit test user and see below.

Running 'phing config' will build the configuration files (such as database.php)
needed.
Any time you change these settings, you will want to run:

    $ phing cleanConfig && phing config

To force them to be rebuilt. You can then:

    $ phing createUnitDBUser

To create the unit test user for you according to the db.properties settings
using the development user credentials.
If your development user is permitted to create users and grant privileges, then
you should not need to modify these values. Otherwise, override the settings in
your ${env.USER}.properties file and use whatever process you used to create the
development user to do the same with the unit test user. Appropriate privileges
for that user will be granted in the next step.

    $ phing creatUnitDB
    $ phing migrateDevelopment

To create the database and grant permissions to your test user
(SELECT, INSERT, UPDATE, and DELETE privileges on VCARD.*) and then run all
database schema migrations to bring the database up to the current state.

The unit tests will automatically delete new rows and reset the table state
after each run, so you *should not* have to clean and reset the database
yourself unless something has happened to disrupt its state or you have
intentionally made changes to the data or the schema. If that happens:

   $ phing rollbackDevelopment && phing migrateDevelopment

Will recreate the tables, or, in an extreme case:

   $ phing dropUnitDB
   $ phing createUnitDB && phing migrateDevelopment

The unit tests rely on an xml table dump to set/reset the initial state for the
testcases. If the schema has changed, you will want to recreate this file by
running (e.g.):

    $mysqldump --xml -t -u [username] -p [database] > tests/emptyVCARDDB.xml

See the PHPUnit Manual, [Database Testing Chapter](https://phpunit.de/manual/current/en/database.html#database.available-implementations) for more information.

## Database Migrations ##

VCardTools uses [Phinx](https://github.com/robmorgan/phinx) to manage changes to
the database schema. Database changesets ("migrations") are defined in
src/sql/migrations as ordered php scripts.
The script template for each migration is created by running the
["phinx create"](http://docs.phinx.org/en/latest/commands.html#the-create-command)
command and then filling in the up()/down() or change() methods in the template.

All non-applied migrations are applied by running the phing task
"phing migrateDevelopment" by calling "phinx migrate development" and all
defined migrations are rolled back by the "phing rollbackDevelopment" task
which runs "phinx rollback development".
"phinx status" will display a list of which migrations have been applied against
the current database.
"phing migrate" and "phing rollback" with appropriate arguments will bring the
database to a specific state.

All changes to the database schema, therefore, should be done (or at least
made permanent after being prototyped) by adding appropriate migrations scripts
to the project.

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

## Your Project's build.xml ##

If you are including this library in a larger project, you will want a
[build.xml](http://www.phing.info/docs/master/hlhtml/index.html#d5e837) to tell
phing how to automate your project tasks.
VCard-Tools provides a build file which you can include in your build.xml to
automate the database portions needed to use VCard-Tools.
To do this, you use the import task in your build.xml:

    <property name="vcardtools.dir" value="${vendor.dir}/evought/vcard-tools"/>
    <import file="${vcardtools.dir}/phingDBTasks.xml"/>

Import will load the xml file we provide, setting properties and tasks for you,
such as the createUnitDB task we mention in the Database Setup section above.
Your project's build.xml might therefore look something like this:

    <project name="project" default="dist" basedir="."
         description="Project including VCard-Tools">
      <!-- get dependencies from Composer -->
      <php expression="include('vendor/autoload.php')"/>

      <!-- set global properties for this build -->
      <property name="src.dir" value="src"/>
      <property name="test.dir" value="tests"/>
      <property name="dist.dir"  value="dist"/>
      <property name="doc.dir"  value="docs"/>
      <property name="apidoc.dir" value="docs/api"/>
      <property name="vendor.dir" value="vendor"/>
      <property name="vendor.bin.dir" value="${vendor.dir}/bin"/>
      <property name="reports.dir" value="reports"/>
  
      <!-- per-user properties for overriding settings, such as db connection -->
      <property file="${env.USER}.properties"/>

      <property name="vcardtools.dir" value="${vendor.dir}/evought/vcard-tools"/>
      <import file="${vcardtools.dir}/phingDBTasks.xml"/>

      <!-- Your project's other task definitions -->
    </project>

You can simply copy db.properties, database.php.in, and phinx.yml.in from
vendor/evought/vcard-tools to your project directory and edit them to suit as
described in Database Setup.
An effort has been made to name all of the VCard-Tools properties and tasks
so that they will not interfere with any additional database tasks your project
may need in addition to what we provide.
You can then define your own settings for a production database, connect to
additional databases, or even create your own migrations (rename our phinx.yml
file, create your own, and use the -c option to phinx to select which one to
use). If you need to heavily customize the behavior, you may forgo including
phingDBTasks.xml and define your own phing task, cutting and pasting from our
example. You may, of course, also copy and modify parts of the VCard-Tools
build.xml file as needed.

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

_NOTE_: See Issue [#59](https://github.com/evought/VCard-Tools/issues/59) if
you have failing testcases.

A phpunit.xml file is provided to run tests manually from the command-line:

    $ phpunit tests/VCardTests.php

Should work and will give more detailed output when tests actually fail.
I also routinely run tests under NetBeans IDE. Running the entire suite or
individual tests under NetBeans will work smoothly if you configure the
project to use bootstrap.php with phpunit (Project->Properties->Testing).

## Continuous Integration Server ##

VCard-Tools uses the [Travis Continuous Integration Server](http://docs.travis-ci.com/).
This means that every time changes are committed to this library, Travis CI
checks out a clean copy of the changes, builds them, sets up the database,
and runs the unit test suite. It actually goes through this process several
times to run the tests with different versions of PHP.
This process helps ensure that committed changes will not break existing tests
and that idiosyncrasies of the developer's workstation do not hide errors.

If the build fails, the developer gets a cranky email telling him to fix it.