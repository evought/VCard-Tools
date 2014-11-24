<?php

/**
 * A start on unit tests for the VCard class to avoid regression as changes
 * are made.
 * @author Eric Vought <evought@pobox.com>
 * 2014-11-16
 * @copyright Eric Vought 2014, Some rights reserved.
 * @license http://creativecommons.org/licenses/by/4.0/ CC-BY 4.0 
 */
require "vcard.php";

class VCardTest extends PHPUnit_Framework_TestCase {

    // some components of expected values
    static $vcard_begin = "BEGIN:VCARD";
    static $vcard_end = "END:VCARD";
    static $vcard_version = "VERSION:4.0";
    static $vcard_empty_fn = "FN:";

    /**
     * Check the format of the VCARD skeleton (begin, end, version),
     * remove those lines, and return the remaining lines. Utility for use
     * in __toString() tests.
     * @arg $vcard_string The vcard output to check
     * @return An array of the remaining lines (no newlines).
     */
    public function checkAndRemoveSkeleton($vcard_string)
    {
	$lines = explode("\n", $vcard_string);
	$this->assertGreaterThan(4, count($lines));

        $line = array_pop($lines);
	$this->assertEmpty($line);
	$line = array_pop($lines);
	$this->assertEquals(self::$vcard_end, $line);

	$line = array_shift($lines);
	$this->assertEquals(self::$vcard_begin, $line);
	$line = array_shift($lines);
	$this->assertEquals(self::$vcard_version, $line);

	return $lines;
    }

    /**
     * Provides a variety of string values to ensure that parsing is correct
     * and dangerous values are escaped on output (comma, colon, newline,
     * semi-colon, and backslash). RFC 6350 Sec. 3.4.
     */
    public function stringEscapeProvider()
    {
	// unescaped, escaped
	return array (
	    array ( 'LettersAnd0123456789', 	'LettersAnd0123456789' ),
	    array ( 'Com,ma',			'Com\,ma' ),
	    array ( 'Col:on',			'Col\:on' ),
	    array ( 'Semi;colon',		'Semi\;colon' ),
	    array ( "A \newline",			'A \newline' ),
	    array ( 'backs\\ash',		'backs\\\\ash' ),
	    array ( 's/ash',			's/ash' ),
	    array ( 'angle bra<ket',		'angle bra<ket' ),
	    array ( '(&other $tuff)',		'(&other $tuff)' ),
	    array ( 'http://foobar.baz?yut=boo', 'http\://foobar.baz?yut=boo'),
	    array ( 'BEGIN:VCARD',		'BEGIN\:VCARD' )
	);
    }

    /**
     * @covers VCard::__construct
     * @covers VCARD::__get
     */
    public function testConstructEmptyVCard()
    {
	$vcard = new VCard();
	$this->assertInstanceOf('VCard', $vcard);
	return $vcard;
    }

    /**
     * @depends testConstructEmptyVCard
     */
    public function testKeyIsStructuredElement($vcard)
    {
	$this->assertTrue($vcard->keyIsStructuredElement('org'));
	$this->assertFalse($vcard->keyIsStructuredElement('fn'));
    }

    /**
     * @depends testConstructEmptyVCard
     */
    public function testKeyIsSingleValueElement($vcard)
    {
        $this->assertTrue($vcard->keyIsSingleValueElement('fn'));
	$this->assertFalse($vcard->keyIsSingleValueElement('url'));
    }

    /**
     * @depends testConstructEmptyVCard
     */
    public function testKeyIsMultipleValueElement($vcard)
    {
        $this->assertTrue($vcard->keyIsMultipleValueElement('categories'));
	$this->assertFalse($vcard->keyIsMultipleValueElement('n'));
    }

    /**
     * @depends testConstructEmptyVCard
     */
    public function testKeyIsFileElement($vcard)
    {
        $this->assertTrue($vcard->keyIsFileElement('photo'));
	$this->assertFalse($vcard->keyIsFileElement('email'));
    }

    /**
     * @covers VCard::__construct
     * @covers VCARD::__get
     * @depends testConstructEmptyVCard
     */
    public function testNoFN(VCard $vcard)
    {
	$this->assertEmpty($vcard->fn);
	return $vcard;
    }

    /**
     * @covers VCard::__call, VCard::__unset
     * @depends testNoFN
     */
    public function testSetFN(VCard $vcard)
    {
	$expected = "Test FN";
	$vcard->fn($expected);
	$this->assertNotEmpty($vcard->fn);
	$this->assertEquals($expected, $vcard->fn);

	unset($vcard->fn);
	$this->assertEmpty($vcard->fn);
	return $vcard;
    }

    /**
     * @covers VCard::__call, VCard::__unset, VCard::isset
     * @depends testSetFN
     */
    public function testIsSet(VCard $vcard)
    {
        $this->assertFalse(isset($vcard->fn), print_r($vcard, true));
	$vcard->fn("foo");
	$this->assertTrue(isset($vcard->fn));
        unset($vcard->fn);
        $this->assertFalse(isset($vcard->fn));
    }

    /**
     * @covers VCard::__set, VCard::__get.
     * @depends testNoFN
     */
    public function testAssignSingleValueElement(VCard $vcard)
    {
        $expected = 'foo';
        $this->assertEmpty($vcard->fn);
        $vcard->fn = $expected;
	$this->assertNotEmpty($vcard->fn);
	$this->assertEquals($expected, $vcard->fn);

	unset($vcard->fn);
	$this->assertEmpty($vcard->fn);
	return $vcard;
    }

    /**
     * @covers VCard::__set, VCard::__get.
     * @depends testNoFN
     */
    public function testAssignNoValue(VCard $vcard)
    {
        $expected = 'foo';
        $this->assertEmpty($vcard->fn);
        $vcard->fn = $expected;
	$this->assertNotEmpty($vcard->fn);

	$vcard->fn = "";
	$this->assertEmpty($vcard->fn);
	return $vcard;
    }

    /**
     * @covers VCard::__set, VCard::__get.
     * @depends testNoFN
     */
    public function testAssignElement(VCard $vcard)
    {
        $expected = array('foo');
        $this->assertEmpty($vcard->fn);
        $vcard->url = $expected;
	$this->assertNotEmpty($vcard->url);
	$this->assertEquals($expected, $vcard->url);

	unset($vcard->url);
	$this->assertEmpty($vcard->url);
	return $vcard;
    }

    /**
     * @covers VCard::__set, VCard::__get.
     * @depends testNoFN
     */
    public function testAssignStructuredElement(VCard $vcard)
    {
        $expected = array(array("StreetAddress" => 'foo'));
        $this->assertEmpty($vcard->adr);
        $vcard->adr = $expected;
	$this->assertNotEmpty($vcard->adr);
	$this->assertEquals($expected, $vcard->adr);

	unset($vcard->adr);
	$this->assertEmpty($vcard->adr);
	return $vcard;
    }

    /**
     * @covers VCard::__set, VCard::__get.
     * @depends testNoFN
     * @expectedException DomainException
     */
    public function testAssignBadSingleValueElement(VCard $vcard)
    {
        $vcard->fn = array("foo");
	return $vcard;
    }

    /**
     * @covers VCard::__set, VCard::__get.
     * @depends testNoFN
     * @expectedException DomainException
     */
    public function testAssignBadElement(VCard $vcard)
    {
        $vcard->url = "foo";
	return $vcard;
    }

    /**
     * @covers VCard::__set, VCard::__get.
     * @depends testNoFN
     * @expectedException DomainException
     */
    public function testAssignBadStructuredElement1(VCard $vcard)
    {
        $vcard->adr = "foo";
	return $vcard;
    }

    /**
     * @covers VCard::__set, VCard::__get.
     * @depends testNoFN
     * @expectedException DomainException
     */
    public function testAssignBadStructuredElement2(VCard $vcard)
    {
        $vcard->adr = array("foo");
	return $vcard;
    }

    /**
     * @covers VCard::__call
     * @depends testSetFN
     * Because FN is a single value element, setting twice should
     * overwrite the first value rather than adding a new value.
     */
    public function testResetFN(VCard $vcard)
    {
	$fn1 = "First FN";
	$fn2 = "New FN";

	$vcard->fn($fn1);
	$vcard->fn($fn2);
	$this->assertNotEmpty($vcard->fn);
	$this->assertNotInternalType("array", $vcard->fn);
	$this->assertEquals($fn2, $vcard->fn);

	unset($vcard->fn);
	return $vcard;
    }

    /**
     * @covers VCard::__get
     * @depends testConstructEmptyVCard
     */
    public function testEmptyCategories(VCard $vcard)
    {
	$this->assertEmpty($vcard->categories);

	return $vcard;
    }

    /**
     * @covers VCard::__call, VCard->categories
     * @depends testEmptyCategories
     */
    public function testSetSingleCategory(VCard $vcard)
    {
	$category_expected = "computers";
	$vcard->categories($category_expected);
	$this->assertNotEmpty($vcard->categories);
	$this->assertInternalType("array", $vcard->categories);
	$this->assertCount(1, $vcard->categories);
	$this->assertContains($category_expected, $vcard->categories);

	unset($vcard->categories);
	$this->assertEmpty($vcard->categories);
	return $vcard;
    }

    /**
     * @covers VCard::__call, VCard->categories
     * @depends testSetSingleCategory
     */
    public function testSetTwoCategories(VCard $vcard)
    {
	$category1 = "computers";
	$category2 = "electronics";

	$vcard->categories($category1);
	$vcard->categories($category2);
	$this->assertNotEmpty($vcard->categories);
	$this->assertInternalType("array", $vcard->categories);
	$this->assertCount( 2, $vcard->categories,
		print_r($vcard->categories, true) );
	$this->assertContains($category1, $vcard->categories);
	$this->assertContains($category2, $vcard->categories);

	unset($vcard->categories);

	return $vcard;
    }

    /**
     * @covers VCard::__construct
     * @covers VCARD::__get
     * @depends testConstructEmptyVCard
     */
    public function testNoURL(VCard $vcard)
    {
	$this->assertEmpty($vcard->url);

	return $vcard;
    }

    /**
     * @covers VCard::__call
     * @covers VCARD::__get
     * @depends testNoURL
     */
    public function testSetSingleURL(VCard $vcard)
    {
	$url_expected = "http://golf.com";
	$vcard->url($url_expected);
	$this->assertNotEmpty($vcard->url);
	$this->assertInternalType("array", $vcard->url);
	$this->assertCount(1, $vcard->url);
	$this->assertContains($url_expected, $vcard->url);

	unset($vcard->url);
	$this->assertEmpty($vcard->url);
	return $vcard;
    }

    /**
     * @covers VCard::__call
     * @covers VCARD::__get
     * @depends testSetSingleURL
     */
    public function testSetTwoURLs(VCard $vcard)
    {
	$url1 = "http://golf.com";
	$url2 = "http://espn.com";

	$vcard->url($url1);
	$vcard->url($url2);

	$this->assertNotEmpty($vcard->url);
	$this->assertInternalType("array", $vcard->url);
	$this->assertCount(2, $vcard->url);
	$this->assertContains($url1, $vcard->url);
	$this->assertContains($url2, $vcard->url);

	unset($vcard->url);
	$this->assertEmpty($vcard->url);
	return $vcard;
    }

    /**
     * @depends testConstructEmptyVCard
     */
    public function testNoAdr(VCard $vcard)
    {
	$this->assertEmpty($vcard->adr);

	return $vcard;
    }

    /**
     * @depends testNoAdr
     */
    public function testSetAdrStreetAddress(VCard $vcard)
    {
	$adr_street = "Some Street";
	$expected = [
			"StreetAddress" => $adr_street
		    ];
	$vcard->adr($adr_street, "StreetAddress");
	$this->assertNotEmpty($vcard->adr);
	$this->assertInternalType("array", $vcard->adr);
	$this->assertCount(1, $vcard->adr);

	$this->assertContains($expected, $vcard->adr, print_r($vcard->adr, true));

	unset($vcard->adr);
	$this->assertEmpty($vcard->adr);
	return $vcard;
    }

    /**
     * @depends testNoAdr
     */
    public function testSetAdrFields(VCard $vcard)
    {
	$address = [
			'StreetAddress' => '123 Sesame Street',
			'Locality' => 'Hooville',
			'Region' => 'Bear-ever',
			'PostalCode' => '31337',
			'Country' => 'Elbonia'
		    ];

	foreach ($address as $key => $value)
	{
	    $vcard->adr($value, $key);
	}
	$this->assertNotEmpty($vcard->adr);
	$this->assertInternalType("array", $vcard->adr);
	$this->assertCount(1, $vcard->adr);

	$this->assertContains($address, $vcard->adr, print_r($vcard->adr, true));

	unset($vcard->adr);
	$this->assertEmpty($vcard->adr);
	return $vcard;
    }

    /**
     * Test ADR fields deprecated by RFC 6350. Should still be supported for the
     * moment.
     * @depends testNoAdr
     */
    public function testSetAdrDeprecatedFields(VCard $vcard)
    {
	$address = [
			'POBox' => '41218',
			'ExtendedAddress' => 'Suite Dreams',
			'StreetAddress' => '123 Sesame Street',
			'Locality' => 'Hooville',
			'Region' => 'Bear-ever',
			'PostalCode' => '31337',
			'Country' => 'Elbonia'
		    ];

	foreach ($address as $key => $value)
	{
	    $vcard->adr($value, $key);
	}
	$this->assertNotEmpty($vcard->adr);
	$this->assertInternalType("array", $vcard->adr);
	$this->assertCount(1, $vcard->adr);

	$this->assertContains($address, $vcard->adr, print_r($vcard->adr, true));

	unset($vcard->adr);
	$this->assertEmpty($vcard->adr);
	return $vcard;
    }

    /**
     * @depends testConstructEmptyVCard
     */
    public function testNoKind(VCard $vcard)
    {
	$this->assertEmpty($vcard->kind);

	return $vcard;
    }

    /**
     * @covers VCard::__call, VCard::__unset
     * @depends testNoKind
     */
    public function testSetKind(VCard $vcard)
    {
	$expected = "Individual";
	$vcard->kind($expected);
	$this->assertNotEmpty($vcard->kind);
	$this->assertEquals($expected, $vcard->kind);

	unset($vcard->kind);
	$this->assertEmpty($vcard->kind);
	return $vcard;
    }

    /**
     * @covers VCard::__construct
     * @covers VCARD::__toString()
     * @depends testConstructEmptyVCard
     * FN appears because RFC6350 may not be omitted (and is not
     * supposed to be empty).
     */
    public function testToStringEmptyVCard()
    {
	$expected = 	[ self::$vcard_empty_fn ];
	$vcard = new VCard();

	$output = "";
	$output .= $vcard;
	$this->assertNotEmpty($output);

	$lines = $this->checkAndRemoveSkeleton($output);

	$this->assertEquals($expected, $lines);
    }

    /**
     * @covers VCARD::__toString()
     * @depends testToStringEmptyVCard
     * @depends testSetFN
     * @dataProvider stringEscapeProvider
     */
    public function testToStringFN($unescaped, $escaped)
    {
	$expected = 	[ "FN:" . $escaped ];
	$vcard = new VCard();
	$vcard->fn($unescaped);

	$output = "";
	$output .= $vcard;
	$this->assertNotEmpty($output);

	$lines = $this->checkAndRemoveSkeleton($output);

	$this->assertEquals($expected, $lines);
    }

    /**
     * @covers VCARD::__toString()
     * @depends testToStringEmptyVCard
     * @depends testSetSingleCategory
     * @dataProvider stringEscapeProvider
     */
    public function testToStringOneCategory($unescaped, $escaped)
    {
	$expected = [ self::$vcard_empty_fn, "CATEGORIES:" . $escaped ];
	sort($expected);

	$vcard = new VCard();
	$vcard->categories($unescaped);

	$output = "";
	$output .= $vcard;

	$lines = $this->checkAndRemoveSkeleton($output);

	// These can appear in any order
	sort($lines);
	$this->assertEquals($expected, $lines);
    }

    /**
     * @covers VCARD::__toString()
     * @depends testToStringOneCategory
     * @depends testSetTwoCategories
     * We assume it will output multiple categories one per line
     * rather than separated by commas as also allowed in the spec.
     */
    public function testToStringTwoCategories()
    {
	$category1 = "sporting goods";
	$category2 = "telephone sanitizing";
	$expected = [
			self::$vcard_empty_fn,
			"CATEGORIES:" . $category1,
			"CATEGORIES:" . $category2
	 ];
	sort($expected);

	$vcard = new VCard();
	$vcard->categories($category1);
	$vcard->categories($category2);

	$output = "";
	$output .= $vcard;

	$lines = $this->checkAndRemoveSkeleton($output);

	// These can appear in any order
	sort($lines);
	$this->assertEquals($expected, $lines);
    }

    /**
     * @covers VCARD::__toString()
     * @depends testToStringEmptyVCard
     * @depends testSetSingleURL
     * @dataProvider stringEscapeProvider
     */
    public function testToStringOneURL($unescaped, $escaped)
    {
	$expected = [ self::$vcard_empty_fn, "URL:" . $escaped ];
	sort($expected);

	$vcard = new VCard();
	$vcard->url($unescaped);

	$output = "";
	$output .= $vcard;

	$lines = $this->checkAndRemoveSkeleton($output);

	// These can appear in any order
	sort($lines);
	$this->assertEquals($expected, $lines);
    }


    /**
     * @covers VCARD::__toString()
     * @depends testToStringOneURL
     * @depends testSetSingleURL
     */
    public function testToStringTwoURLs()
    {
	$url1 = "something";
	$url2 = "somethingElse";
	$expected = [
			self::$vcard_empty_fn,
			"URL:" . $url1,
			"URL:" . $url2
		];
	sort($expected);

	$vcard = new VCard();
	$vcard->url($url1);
	$vcard->url($url2);

	$output = "";
	$output .= $vcard;

	$lines = $this->checkAndRemoveSkeleton($output);

	// These can appear in any order
	sort($lines);
	$this->assertEquals($expected, $lines);
    }

    /**
     * @depends testToStringEmptyVCard
     * @depends testSetAdrFields
     * RFC 6350 Sec 6.3.1
     */
    public function testToStringOneAdr()
    {
	$address = [
			'StreetAddress' => '123 Sesame Street',
			'Locality' => 'Hooville',
			'Region' => 'Bear-ever',
			'PostalCode' => '31337',
			'Country' => 'Elbonia'
		    ];

	$expected = [ self::$vcard_empty_fn,
			"ADR:" . ';;'  // POBox & ExtendedAddress
			. $address['StreetAddress'] . ';'
			. $address['Locality'] . ';'
			. $address['Region'] . ';'
			. $address['PostalCode'] . ';'
			. $address['Country']
			];
	sort($expected);

	$vcard = new VCard();

	foreach ($address as $key => $value)
	{
	    $vcard->adr($value, $key);
	}


	$output = "";
	$output .= $vcard;

	$lines = $this->checkAndRemoveSkeleton($output);

	// These can appear in any order
	sort($lines);
	$this->assertEquals($expected, $lines);
    }

    /**
     * @covers VCard::__construct
     * @depends testConstructEmptyVCard
     */
    public function testImportEmptyVCard()
    {
	$input =	self::$vcard_begin . "\n"
			. self::$vcard_version . "\n"
			. self::$vcard_end . "\n";

	$vcard = new VCard(false, $input);
	$this->assertInstanceOf('VCard', $vcard);
	$this->assertEmpty($vcard->fn);
    }

    /**
     * @covers VCard::__construct
     * @depends testImportEmptyVCard
     */
    public function testImportVCardEmptyFN()
    {
	$input =	self::$vcard_begin . "\n"
			. self::$vcard_version . "\n"
			. self::$vcard_empty_fn . "\n"
			. self::$vcard_end . "\n";

	$vcard = new VCard(false, $input);
	$this->assertInstanceOf('VCard', $vcard);
	$this->assertEmpty($vcard->fn);
    }

    /**
     * @covers VCard::__construct
     * @depends testImportVCardEmptyFN
     * @dataProvider stringEscapeProvider
     */
    public function testImportVCardFN($unescaped, $escaped)
    {
	$input =	self::$vcard_begin . "\n"
			. self::$vcard_version . "\n"
			. "FN:" . $escaped . "\n"
			. self::$vcard_end . "\n";

	$vcard = new VCard(false, $input);
	$this->assertInstanceOf('VCard', $vcard);
	$this->assertEquals($unescaped, $vcard->fn);
    }

    /**
     * @covers VCard::__construct
     * @depends testImportEmptyVCard
     */
    public function testImportVCardNoCategories()
    {
	$input =	self::$vcard_begin . "\n"
			. self::$vcard_version . "\n"
			. self::$vcard_end . "\n";

	$vcard = new VCard(false, $input);
	$this->assertInstanceOf('VCard', $vcard);
	$this->assertEmpty($vcard->categories);
    }

    /**
     * @covers VCard::__construct
     * @depends testImportEmptyVCard
     */
    public function testImportVCardEmptyCategories()
    {
	$input =	self::$vcard_begin . "\n"
			. self::$vcard_version . "\n"
			. "CATEGORIES:\n"
			. self::$vcard_end . "\n";

	$vcard = new VCard(false, $input);
	$this->assertInstanceOf('VCard', $vcard);

	$this->assertEmpty( $vcard->categories,
			    print_r($vcard->categories, true) );
    }

    /**
     * @covers VCard::__construct
     * @depends testImportEmptyVCard
     * @dataProvider stringEscapeProvider
     */
    public function testImportVCardOneCategory($unescaped, $escaped)
    {
	$input =	self::$vcard_begin . "\n"
			. self::$vcard_version . "\n"
			. "CATEGORIES:" . $escaped . "\n"
			. self::$vcard_end . "\n";

	$vcard = new VCard(false, $input);
	$this->assertInstanceOf('VCard', $vcard);

	$this->assertNotEmpty($vcard->categories);
	$this->assertInternalType("array", $vcard->categories);
	$this->assertCount(1, $vcard->categories);
	$this->assertContains( $unescaped, $vcard->categories,
				print_r($vcard->categories, true) );
   }

    /**
     * @covers VCard::__construct
     * @depends testImportVCardOneCategory
     */
    public function testImportVCardTwoCategories()
    {
	$category1 = "farrier";
	$category2 = "smurf";
	$input =	self::$vcard_begin . "\n"
			. self::$vcard_version . "\n"
			. "CATEGORIES:" . $category1 . "\n"
			. "CATEGORIES:" . $category2 . "\n"
			. self::$vcard_end . "\n";

	$vcard = new VCard(false, $input);
	$this->assertInstanceOf('VCard', $vcard);

	$this->assertNotEmpty($vcard->categories);
	$this->assertInternalType("array", $vcard->categories);
	$this->assertCount(2, $vcard->categories,
		print_r($vcard->categories, true) );
	$this->assertContains( $category1, $vcard->categories,
				print_r($vcard->categories, true) );
	$this->assertContains( $category2, $vcard->categories,
				print_r($vcard->categories, true) );
    }

    /**
     * @covers VCard::__construct
     * @depends testImportEmptyVCard
     */
    public function testImportVCardNoURL()
    {
	$input =	self::$vcard_begin . "\n"
			. self::$vcard_version . "\n"
			. self::$vcard_end . "\n";

	$vcard = new VCard(false, $input);
	$this->assertInstanceOf('VCard', $vcard);
	$this->assertEmpty($vcard->url);
    }

    /**
     * @covers VCard::__construct
     * @depends testImportEmptyVCard
     */
    public function testImportVCardEmptyURL()
    {
	$input =	self::$vcard_begin . "\n"
			. self::$vcard_version . "\n"
			. "URL:\n"
			. self::$vcard_end . "\n";

	$vcard = new VCard(false, $input);
	$this->assertInstanceOf('VCard', $vcard);

	$this->assertEmpty( $vcard->url,
			    print_r($vcard->url, true) );
    }

    /**
     * @covers VCard::__construct
     * @depends testImportEmptyVCard
     * @dataProvider stringEscapeProvider
     */
    public function testImportVCardOneURL($unescaped, $escaped)
    {
	$input =	self::$vcard_begin . "\n"
			. self::$vcard_version . "\n"
			. "URL:" . $escaped . "\n"
			. self::$vcard_end . "\n";

	$vcard = new VCard(false, $input);
	$this->assertInstanceOf('VCard', $vcard);

	$this->assertNotEmpty($vcard->url);
	$this->assertInternalType("array", $vcard->url);
	$this->assertCount(1, $vcard->url);
	$this->assertContains( $unescaped, $vcard->url,
				print_r($vcard->url, true) );
   }

    /**
     * @covers VCard::__construct
     * @depends testImportVCardOneURL
     */
    public function testImportVCardOneURLUnescape()
    {
	$url = "http\://somewhere";
	$unescaped = "http://somewhere";

	$input =	self::$vcard_begin . "\n"
			. self::$vcard_version . "\n"
			. "URL:" . $url . "\n"
			. self::$vcard_end . "\n";

	$vcard = new VCard(false, $input);
	$this->assertInstanceOf('VCard', $vcard);

	$this->assertNotEmpty($vcard->url);
	$this->assertInternalType("array", $vcard->url);
	$this->assertCount(1, $vcard->url);
	$this->assertContains( $unescaped, $vcard->url,
				print_r($vcard->url, true) );
   }

    /**
     * @covers VCard::__construct
     * @depends testImportEmptyVCard
     */
    public function testImportVCardTwoURLs()
    {
	$url1 = "tweedldee";
	$url2 = "tweedledum";
	$input =	self::$vcard_begin . "\n"
			. self::$vcard_version . "\n"
			. "URL:" . $url1 . "\n"
			. "URL:" . $url2 . "\n"
			. self::$vcard_end . "\n";

	$vcard = new VCard(false, $input);
	$this->assertInstanceOf('VCard', $vcard);

	$this->assertNotEmpty($vcard->url);
	$this->assertInternalType("array", $vcard->url);
	$this->assertCount(2, $vcard->url);
	$this->assertContains( $url1, $vcard->url,
				print_r($vcard->url, true) );
	$this->assertContains( $url2, $vcard->url,
				print_r($vcard->url, true) );

   }


}
?>
