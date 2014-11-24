README.md
Eric Vought
2014-11-24 VCard-Tools PHP toolbox

#What This Project Is#

This effort is a set of tools for manipulating VCards (RFC 6350) in PHP, including in-memory representation, database persistence, and HTML templating. The tools build on two other works, the VCard PHP class by Martins Pilsetnieks, Roberts Bruveris (https://github.com/nuovo/vCard-parser) and a sample VCard SQL schema by George_h on Stackoverflow for RFC6868/VCard 3.0. VCard-Tools marries an improved VCard class with a set of routines for database persistence (based on George_h's schema) and flexible HTML output targeting VCard 4.0 (RFC 6350) compliance. It also includes a growing suite of PHPUnit tests.

At its outset, the VCard class is one of the more advanced PHP classes for the purpose. I extended it for use in the prototype site where I first used VCard-Tools, added support for some VCard 4.0 behavior, fixed border parsing cases, added the unit tests, rounded out the interface, and performed cleanup. It is therefore heading toward being a fairly robust tool, albeit with a few oddities brought about by the sheer complexity of the VCard 4.0 schema (which I have some ideas about improving). The schema, also adapted toward VCard 4.0 and improved, is likewise reasonably stable. I intend to extend it to handle the remainder of the VCard 4.0 properties and possibly adapt it to handle more than 1 N property.

The vcard-tools.php routines for database persistence, by comparison, are green, in need of refactoring, cleanup, and testing. I anticipate substantial interface repacking/standardization and internal cleanup in the near future. They should be viewed as a demonstration project at this time, *not production code*. The templating mechanism has a great deal of power and flexibility. The format of template definitions themselves are reasonably stable, but the interface will also undergo cleanup.

#Components#

The project consists of the php modules in vcard-tools.php and vcard.php, as well as the default template definitions in vcard-templates.php. The sql schema (MySQL) is defined in sql/, documentation materials in doc/, and PHPUnit test cases under tests/. Setting up the database *should* be relatively straightforward but I will add some notes to that effect. I also intend to add an Ant script for maintenance tasks for my own sanity but have not yet done so.

The templating code can be used along with the original VCard class *with or without the database persistence*.

The HTML templating is designed with CSS styling in mind. Classes and roles are clearly indicated in the markup as styling hooks and it is simple to present different output for, say, Individual versus Organization or summary versus full information. In a live website, I intend that it should be easy for the user to go from a search-list to individual detailed listings, export the raw .vcf text or even, potentially, generate a QR code. I expect to add a template for proper hcard output, but it should not be difficult (at all) for someone to use the mechanism to produce a conformant hcard using the existing code.

#Documentation and Examples#

Aside from the code comments, one of the best resources for using the vcard-tools.php functions will be the test cases, which will be improved as I clean up and refactor the code. There are as yet no publicly visible demonstrations of the class.

