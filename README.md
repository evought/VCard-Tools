README.md

Eric Vought

2014-11-24 VCard-Tools PHP toolbox

MIT License http://opensource.org/licenses/MIT

#What This Project Is#

This effort is a set of tools for manipulating VCards (RFC 6350) in PHP, including in-memory representation, database persistence, and HTML templating. The tools build on two other works, the VCard PHP class by Martins Pilsetnieks, Roberts Bruveris (https://github.com/nuovo/vCard-parser) and a sample VCard SQL schema by George_h on Stackoverflow for RFC6868/VCard 3.0. VCard-Tools marries an improved VCard class with a set of routines for database persistence (based on George_h's schema) and flexible HTML output targeting VCard 4.0 (RFC 6350) compliance. It also includes a growing suite of PHPUnit tests.

At its outset, the VCard class is one of the more advanced PHP classes for the purpose. I extended it for use in the prototype site where I first used VCard-Tools, added support for some VCard 4.0 behavior, fixed border parsing cases, added the unit tests, rounded out the interface, and performed cleanup. It is therefore heading toward being a fairly robust tool, albeit with a few oddities brought about by the sheer complexity of the VCard 4.0 schema (which I have some ideas about improving). The schema, also adapted toward VCard 4.0 and improved, is likewise reasonably stable. I intend to extend it to handle the remainder of the VCard 4.0 properties and possibly adapt it to handle more than 1 N property.

The vcard-tools.php routines for database persistence, by comparison, are green, in need of refactoring, cleanup, and testing. I anticipate substantial interface repacking/standardization and internal cleanup in the near future. They should be viewed as a demonstration project at this time, *not production code*. The templating mechanism has a great deal of power and flexibility. The format of template definitions themselves are reasonably stable, but the interface will also undergo cleanup.

#License#

All components are under the [MIT License](license.txt).

#Components#

The project consists of the php modules in vcard-tools.php and vcard.php, as well as the default template definitions in vcard-templates.php. The sql schema (MySQL) is defined in sql/, documentation materials in doc/, and PHPUnit test cases under tests/. Setting up the database is described below. There is the beginnings of an [Ant](http://ant.apache.org) script for automating project maintenance tasks.

The templating code can be used along with the original VCard class *with or without the database persistence*.

The HTML templating is designed with CSS styling in mind. Classes and roles are clearly indicated in the markup as styling hooks and it is simple to present different output for, say, Individual versus Organization or summary versus full information. In a live website, I intend that it should be easy for the user to go from a search-list to individual detailed listings, export the raw .vcf text or even, potentially, generate a QR code. I expect to add a template for proper hcard output, but it should not be difficult (at all) for someone to use the mechanism to produce a conformant hcard using the existing code.

##Requirements##

The project was developed against
* PHP 5.5 (5.5.18)
* MySQL 5.5 (5.5.38)

This should be all that is needed to use VCard-Tools.

To develop and run tests, you will additionally need:

* Apache Ant >= 1.9.2 for generating config files and maintenance tasks
* PHPUnit >= 4.3.5 for running the tests
* Apache Ant will in turn require a compatible Java Development Kit (I am using
openJDK 1.8.0, but any JDK which will run Ant should be fine.)
* The MySQL jdbc driver (used by ant to execute database tasks).

I am doing most of my development on a Intel-based Fedora Linux 20 workstation.

#Documentation and Examples#

Aside from the code comments, one of the best resources for using the vcard-tools.php functions will be the test cases, which will be improved as I clean up and refactor the code. There are as yet no publicly visible demonstrations of the class.

#Database Setup#

To use the software, a database (e.g. VCARD) will be needed to contain the tables and a user with appropriate permissions to access it. Given appropriate settings
and a user with appropriate permissions in the database, ant will do the actual
loading of the schema for you.

First off, you need a MySQL user account with permissions to create a database and
grant privileges on that database to a test user. I refer to this as the *developer account*. You could use the MySQL *root* user to do this, but on
general principle, it is better to have a developer account with less that super-user powers and yet more than your unit test account. In some environments, such as a shared database, you may not have the option of using the root account. Depending on your setup, this can be done with a tool such as *PHPMyAdmin* or from the command-line. In most environments:

    $ mysql --user=root -p

Will pull up a SQL shell as the MySQL root user, prompting for the password. You may then create the desired account and grant it privileges:

    mysql> create user 'developer'@'localhost' identified by 'password';
    mysql> grant all on VCARD.* to 'developer'@'localhost' with grant option;

Substituting whatever is appropriate for 'developer' and 'password'. At the same time, create a *test account* for actually running the tests:

    mysql> create user 'test-vcard'@'localhost' identified by 'password';

Create a ${username}.properties file in your project folder, copying and editing
values from db.properties to set the username, password, host, database name, etc., for your database. (In other words, I would put these settings in 'evought.properties'). As your personal property file will not be under control of git, you won't have to worry about committing your settings (and password!) back to the repository. The ant build script, build.xml will use these settings
to build some configuration files, initialize the database, and run the tests.

Running 'ant config' will build the configuration files (such as phpunit.xml and database.php) needed. Anytime you change these settings, you will want to run:

    $ ant cleanConfig && ant config

To force them to be rebuilt. You can then:

    $ ant unitDBSchema

This will automatically invoke "ant createUnitDB" to create the database and
grant permissions to your test user (SELECT, INSERT, UPDATE, and DELETE privileges on VCARD.*) and then it will load the schema definitions from sql/vcard.sql. If you need to, you can always load vcard.sql manually from a terminal or by pasting it into a query in PHPMyAdmin.

The unit tests will automatically delete new rows and reset the table state after each run, so you *should not* have to clean and reset the database yourself unless something has happened to disrupt its state or you have intentionally made changes to the data or the schema. If that happens:

   $ ant dropUnitDB && ant createUnitDB

Will recreate the tables. The unit tests rely on an xml table dump to set/reset
the initial state for the testcases. If the schema has changed, you will want to recreate this file by running (e.g.):

    $mysqldump --xml -t -u [username] -p [database] > tests/emptyVCARDDB.xml

See the PHPUnit Manual, [Database Testing Chapter](https://phpunit.de/manual/current/en/database.html#database.available-implementations) for more information.

# Unit Tests #

Running the unit tests requires [PhpUnit](https://phpunit.de/) to be installed. If it is in the path, the full test suite can be run with the *test* ant task:

    $ ant test
