<?php
/**
 * A start on unit tests for the vCard class to avoid regression as changes
 * are made.
 * @author Eric Vought <evought@pobox.com>
 * 2014-11-16
 * @copyright Eric Vought 2014, Some rights reserved.
 * @license MIT http://opensource.org/licenses/MIT
 */

use vCardTools\vCard as vCard;
require_once 'vcard.php';

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
     * Return the property values to build a sample vcard.
     * @return array
     */
    public function getSeinarAPLInputs()
    {
    	$inputs = [
    	    'org_Name'    => 'Seinar Fleet Systems',
    	    'org_Unit1'   => 'Seinar Advanced Projects Laboratory',
    	    'org_Unit2'   => 'TIE AO1X Division',
    	    'fn'          => 'Seinar APL TIE AO1X Division',
    	    'logo'        => 'http://img1.wikia.nocookie.net/__cb20080311192948/starwars/images/3/39/Sienar.svg',
    	    'category1'   => 'military industrial',
    	    'category2'   => 'empire',
    	    'category3'   => 'Research and Development',
    	    'kind'        => 'organization'
    	];
    	 
    	return $inputs;
    }
    
    /**
     * Return the property values to build a sample vcard.
     * @return array
     */
    public function getDDBinksInputs()
    {
    	$inputs = [
    	            'n_FirstName' => 'Darth',
    	            'n_AdditionalNames' => 'Darth',
    	            'n_LastName'  => 'Binks',
    	            'org'       => 'Sith',
    	            'fn'          => 'Darth Darth Binks',
    	            'kind'        => 'individual'
    	];
    	return $inputs;
    }
    
    /**
     * Return the property values to build a sample vcard.
     * @return array
     */
    public function getRaithSeinarInputs()
    {
    	$inputs = [
    	'n_FirstName' => 'Raith',
    	'n_LastName'  => 'Seinar',
    	'org'       => 'Seinar Fleet Systems',
    	'title'	     => 'CEO',
    	'fn'          => 'Raith Seinar',
    	'category1'   => 'military industrial',
    	'category2'   => 'empire',
    	'kind'        => 'individual'
    			];
    	 
    	return $inputs;
    }

    /**
     * Property values for a complex vCard for developing further tests,
     * particularly with round-trip TYPE and other parameter support.
     * @return multitype:string multitype:string
     */
    public function getJohnDoeInputs()
    {
    	$inputs = [
		'n_FirstName'       => 'John',
		'n_LastName'        => 'Doe',
		'n_AdditionalNames' => 'Q., Public',
		'fn'                => 'John Doe',
		'fn_charset'        => 'UTF-8',
		'tel1'		    => '(111) 555-1212',
		'tel1_type'	    => 'WORK, VOICE',
		'tel2'              => '(404) 555-1212',
		'tel2_type'         => 'HOME, VOICE',
		'tel3'              => '(404) 555-1213',
		'tel3_type1'        => 'HOME',
		'tel3_type2'        => 'VOICE',
		'email1'            => 'forrestgump@example.com',
		'email1_type'       => 'PREF, INTERNET',
		'email2'            => 'example@example.com',
		'email2_type'       => 'INTERNET',
		'adr_StreetAddress' => '42 Plantation St.',
		'adr_Locality'      => 'Baytown',
		'adr_Region'        => 'LA',
		'adr_Postal'        => '30314',
		'adr_Country'       => 'United States of America',
		'adr_type'          => 'HOME',
		'url'               => 'https://www.google.com/',
		'photo'             => 'http://upload.wikimedia.org/wikipedia/commons/thumb/a/a5/Example_svg.svg/200px-Example_svg.svg.png',
		'photo_parameters'   => ['VALUE' => 'URL', 'TYPE' => 'PNG']			
    	];
    	
    	return $inputs;    	   
    }
    
    public function getDDBinks()
    {
    	$inputs = $this->getDDBinksInputs();
    	
    	$dDBinks = new vCard();
    	$dDBinks -> n($inputs['n_FirstName'], 'FirstName')
            -> n($inputs['n_LastName'], 'LastName')
            -> n($inputs['n_AdditionalNames'], 'AdditionalNames')
            -> org($inputs['org'], 'Name')
            -> fn($inputs['fn'])
            -> kind($inputs['kind']);

    	return $dDBinks; 
    }
    
    /**
     * Fetch a pre-constructed sample vcard.
     * @return an individual VCard.
     */
    public function getSeinarAPL()
    {
    	$inputs = $this->getSeinarAPLInputs();
    
    	$seinarAPL = new VCard();
    	$seinarAPL -> org($inputs['org_Name'], 'Name')
            -> org($inputs['org_Unit1'], 'Unit1')
            -> org($inputs['org_Unit2'], 'Unit2')
            -> fn($inputs['fn'])
            -> logo($inputs['logo'])
            -> categories($inputs['category1'])
            -> categories($inputs['category2'])
            -> categories($inputs['category3'])
            -> kind($inputs['kind']);
    	return $seinarAPL;
    }
    	
    /**
     * Fetch a pre-constructed sample vcard.
     * @return an individual VCard.
     */
    public function getRaithSeinar()
    {
    	$raithSeinar = new VCard();
    	$inputs = $this->getRaithSeinarInputs();
    	
    	$raithSeinar -> n($inputs['n_FirstName'], 'FirstName')
            -> n($inputs['n_LastName'], 'LastName')
            -> org($inputs['org'], 'Name')
            -> title($inputs['title'])
            -> fn($inputs['fn'])
            -> categories($inputs['category1'])
            -> categories($inputs['category2'])
            -> kind($inputs['kind']);
    	return $raithSeinar;
    }
    
    /**
     * @covers VCard::__construct
     * @covers VCARD::__get
     */
    public function testConstructEmptyVCard()
    {
	$vcard = new vCard();
	$this->assertInstanceOf('vCardTools\vCard', $vcard);
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
     * Test that the __call magic function is case-insenstive with keys.
     * @covers VCard::__call, VCard::__unset
     * @depends testSetFN
     */
    public function testSetFNUpperCase(vCard $vcard)
    {
    	$expected = "Test FN";
    	$vcard->FN($expected);
    	$this->assertNotEmpty($vcard->fn);
    	$this->assertEquals($expected, $vcard->fn);
    
    	unset($vcard->fn);
    	$this->assertEmpty($vcard->fn);
    	return $vcard;
    }

    /**
     * @covers vCard::__call, vCard::__unset, vCard::isset
     * @depends testSetFN
     */
    public function testIsSet(vCard $vcard)
    {
        $this->assertFalse(isset($vcard->fn), print_r($vcard, true));
	$vcard->fn("foo");
	$this->assertTrue(isset($vcard->fn));
        unset($vcard->fn);
        $this->assertFalse(isset($vcard->fn));
    }

    /**
     * @covers vCard::__set, vCard::__get.
     * @depends testNoFN
     */
    public function testAssignSingleValueElement(vCard $vcard)
    {
    	$properties = [ 'fn', 'kind', 'bday', 'anniversary',
    	                'prodid', 'rev', 'uid' ];
        $expected = 'foo';
        
        foreach ($properties as $property)
        {
            $this->assertEmpty($vcard->$property);
            $vcard->$property = $expected;
	    $this->assertNotEmpty($vcard->$property);
	    $this->assertEquals($expected, $vcard->$property);
	
	    unset($vcard->$property);
	    $this->assertEmpty($vcard->$property);
        }
	return $vcard;
    }

    /**
     * @covers vCard::__set, vCard::__get.
     * @depends testNoFN
     */
    public function testAssignNoValue(vCard $vcard)
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
     * @covers vCard::__set, vCard::__get.
     * @depends testNoFN
     */
    public function testAssignElement(vCard $vcard)
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
     * @covers vCard::__set, vCard::__get.
     * @depends testNoFN
     */
    public function testAssignStructuredElement(vCard $vcard)
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
     * @covers vCard::__set, vCard::__get.
     * @depends testNoFN
     * @expectedException DomainException
     */
    public function testAssignBadSingleValueElement(vCard $vcard)
    {
        $vcard->fn = array("foo");
	return $vcard;
    }

    /**
     * @covers vCard::__set, vCard::__get.
     * @depends testNoFN
     * @expectedException DomainException
     */
    public function testAssignBadElement(vCard $vcard)
    {
        $vcard->url = "foo";
	return $vcard;
    }

    /**
     * @covers vCard::__set, vCard::__get.
     * @depends testNoFN
     * @expectedException DomainException
     */
    public function testAssignBadStructuredElement1(vCard $vcard)
    {
        $vcard->adr = "foo";
	return $vcard;
    }

    /**
     * @covers vCard::__set, vCard::__get.
     * @depends testNoFN
     * @expectedException DomainException
     */
    public function testAssignBadStructuredElement2(vCard $vcard)
    {
        $vcard->adr = array("foo");
	return $vcard;
    }

    /**
     * @covers vCard::__call
     * @depends testSetFN
     * Because FN is a single value element, setting twice should
     * overwrite the first value rather than adding a new value.
     */
    public function testResetFN(vCard $vcard)
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
     * @covers vCard::__get
     * @depends testConstructEmptyVCard
     */
    public function testEmptyCategories(vCard $vcard)
    {
	$this->assertEmpty($vcard->categories);

	return $vcard;
    }

    /**
     * @covers vCard::__call, vCard->categories
     * @depends testEmptyCategories
     */
    public function testSetSingleCategory(vCard $vcard)
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
     * @covers vCard::__call, vCard->categories
     * @depends testSetSingleCategory
     */
    public function testSetTwoCategories(vCard $vcard)
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
     * @covers vCard::__construct
     * @covers vCard::__get
     * @depends testConstructEmptyVCard
     */
    public function testNoURL(vCard $vcard)
    {
	$this->assertEmpty($vcard->url);

	return $vcard;
    }

    /**
     * @covers vCard::__call
     * @covers vCard::__get
     * @depends testNoURL
     */
    public function testSetSingleURL(vCard $vcard)
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
     * @covers vCard::__call
     * @covers vCard::__get
     * @depends testSetSingleURL
     */
    public function testSetTwoURLs(vCard $vcard)
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
    public function testNoAdr(vCard $vcard)
    {
	$this->assertEmpty($vcard->adr);

	return $vcard;
    }

    /**
     * @depends testNoAdr
     */
    public function testSetAdrStreetAddress(vCard $vcard)
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
    public function testSetAdrFields(vCard $vcard)
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
    public function testSetAdrDeprecatedFields(vCard $vcard)
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
    public function testNoKind(vCard $vcard)
    {
	$this->assertEmpty($vcard->kind);

	return $vcard;
    }

    /**
     * @covers vCard::__call, vCard::__unset
     * @depends testNoKind
     */
    public function testSetKind(vCard $vcard)
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
     * @covers vCard::__construct
     * @covers vCard::__toString()
     * @depends testConstructEmptyVCard
     * FN appears because RFC6350 may not be omitted (and is not
     * supposed to be empty).
     */
    public function testToStringEmptyVCard()
    {
	$expected = 	[ self::$vcard_empty_fn ];
	$vcard = new vCard();

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
	$vcard = new vCard();
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

	$vcard = new vCard();
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

	$vcard = new vCard();
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
     * @covers vCard::__toString()
     * @depends testToStringEmptyVCard
     * @depends testSetSingleURL
     * @dataProvider stringEscapeProvider
     */
    public function testToStringOneURL($unescaped, $escaped)
    {
	$expected = [ self::$vcard_empty_fn, "URL:" . $escaped ];
	sort($expected);

	$vcard = new vCard();
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

	$vcard = new vCard();
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

	$vcard = new vCard();

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
     * @depends testToStringEmptyVCard
     */
    public function testToStringWithOneN()
    {
    	$name = [
    	          'FirstName'       => 'Luna',
    	          'AdditionalNames' => 'Charlotte',
    	          'LastName'        => 'Begtrup',
    	          'Prefixes'        => 'Ms.',
    	          'Suffixes'        => 'PhD'
    		];
    	$fn = 'Ms. Luna C. Begtrup PhD';
    	
    	$expected = [
    	              'N:' . $name['LastName'] . ';' . $name['FirstName']
    	                   . ';' . $name['AdditionalNames'] . ';'
    	                   . $name['Prefixes'] . ';' . $name['Suffixes'],
    	              'FN:' . $fn
    	            ];

    	sort($expected);
    	
    	$vcard = new vCard();
    	
    	foreach ($name as $key => $value)
    	{
    		$vcard->n($value, $key);
    	}
    	$vcard->fn($fn);
    	
    	$output = "";
    	$output .= $vcard;
    	
    	$lines = $this->checkAndRemoveSkeleton($output);
    	
    	// These can appear in any order
    	sort($lines);
    	$this->assertEquals($expected, $lines);
    }
    
    /**
     * @depends testToStringEmptyVCard
     */
    public function testToStringRaithSeinar()
    {
    	$inputs = $this->getRaithSeinarInputs();
    	$expected = [
    	'N:'.$inputs['n_LastName'].';'.$inputs['n_FirstName'].';;;',
        'ORG:'.$inputs['org'].';;',
        'TITLE:'.$inputs['title'],
        'FN:'.$inputs['fn'],
        'CATEGORIES:'.$inputs['category1'],
        'CATEGORIES:'.$inputs['category2'],
        'KIND:'.$inputs['kind']   
    	];
    	sort($expected);
    	
    	$vcard = $this->getRaithSeinar();
    	
    	$output = "";
    	$output .= $vcard;
    	$lines = $this->checkAndRemoveSkeleton($output);
    	
	// These can appear in any order
	sort($lines);
	$this->assertEquals($expected, $lines);
    }

    /**
     * @depends testToStringEmptyVCard
     */
    public function testToStringDDBinks()
    {
    	$inputs = $this->getDDBinksInputs();
    	$expected = [
    	    'N:' . $inputs['n_LastName'] . ';' . $inputs['n_FirstName']
    	         . ';' . $inputs['n_AdditionalNames'] . ';;',
            'ORG:'.$inputs['org'].';;',
            'FN:'.$inputs['fn'],
            'KIND:'.$inputs['kind']   
    	];
    	sort($expected);
    	
    	$vcard = $this->getDDBinks();
    	
    	$output = "";
    	$output .= $vcard;
    	$lines = $this->checkAndRemoveSkeleton($output);
    	
	// These can appear in any order
	sort($lines);
	$this->assertEquals($expected, $lines);
    }
    
    /**
     * @depends testToStringEmptyVCard
     */
    public function testToStringSeinarAPL()
    {
    	$inputs = $this->getSeinarAPLInputs();
    	$expected = [
    	'ORG:'.$inputs['org_Name'].';'.$inputs['org_Unit1'].';'.$inputs['org_Unit2'],
    	'FN:'.$inputs['fn'],
    	'LOGO:'.addcslashes($inputs['logo'], "\\\n,:;"),
    	'CATEGORIES:'.$inputs['category1'],
    	'CATEGORIES:'.$inputs['category2'],
    	'CATEGORIES:'.$inputs['category3'],
    	'KIND:'.$inputs['kind']
    	];
    	sort($expected);
    	 
    	$vcard = $this->getSeinarAPL();
    	 
    	$output = "";
    	$output .= $vcard;
    	$lines = $this->checkAndRemoveSkeleton($output);
    	 
    	// These can appear in any order
    	sort($lines);
    	$this->assertEquals($expected, $lines);
    }
    
    /**
     * @covers vCard::__construct
     * @depends testConstructEmptyVCard
     */
    public function testImportEmptyVCard()
    {
	$input =	self::$vcard_begin . "\n"
			. self::$vcard_version . "\n"
			. self::$vcard_end . "\n";

	$vcard = new vCard(false, $input);
	$this->assertInstanceOf('vCardTools\vCard', $vcard);
	$this->assertEmpty($vcard->fn);
    }

    /**
     * @covers vCard::__construct
     * @depends testImportEmptyVCard
     */
    public function testImportVCardEmptyFN()
    {
	$input =	self::$vcard_begin . "\n"
			. self::$vcard_version . "\n"
			. self::$vcard_empty_fn . "\n"
			. self::$vcard_end . "\n";

	$vcard = new vCard(false, $input);
	$this->assertInstanceOf('vCardTools\vCard', $vcard);
	$this->assertEmpty($vcard->fn);
    }

    /**
     * @covers vCard::__construct
     * @depends testImportVCardEmptyFN
     * @dataProvider stringEscapeProvider
     */
    public function testImportVCardFN($unescaped, $escaped)
    {
	$input =	self::$vcard_begin . "\n"
			. self::$vcard_version . "\n"
			. "FN:" . $escaped . "\n"
			. self::$vcard_end . "\n";

	$vcard = new vCard(false, $input);
	$this->assertInstanceOf('vCardTools\vCard', $vcard);
	$this->assertEquals($unescaped, $vcard->fn);
    }

    /**
     * @covers vCard::__construct
     * @depends testImportEmptyVCard
     */
    public function testImportVCardNoCategories()
    {
	$input =	self::$vcard_begin . "\n"
			. self::$vcard_version . "\n"
			. self::$vcard_end . "\n";

	$vcard = new vCard(false, $input);
	$this->assertInstanceOf('vCardTools\vCard', $vcard);
	$this->assertEmpty($vcard->categories);
    }

    /**
     * @covers vCard::__construct
     * @depends testImportEmptyVCard
     */
    public function testImportVCardEmptyCategories()
    {
	$input =	self::$vcard_begin . "\n"
			. self::$vcard_version . "\n"
			. "CATEGORIES:\n"
			. self::$vcard_end . "\n";

	$vcard = new vCard(false, $input);
	$this->assertInstanceOf('vCardTools\vCard', $vcard);

	$this->assertEmpty( $vcard->categories,
			    print_r($vcard->categories, true) );
    }

    /**
     * @covers vCard::__construct
     * @depends testImportEmptyVCard
     * @dataProvider stringEscapeProvider
     */
    public function testImportVCardOneCategory($unescaped, $escaped)
    {
	$input =	self::$vcard_begin . "\n"
			. self::$vcard_version . "\n"
			. "CATEGORIES:" . $escaped . "\n"
			. self::$vcard_end . "\n";

	$vcard = new vCard(false, $input);
	$this->assertInstanceOf('vCardTools\vCard', $vcard);

	$this->assertNotEmpty($vcard->categories);
	$this->assertInternalType("array", $vcard->categories);
	$this->assertCount(1, $vcard->categories);
	$this->assertContains( $unescaped, $vcard->categories,
				print_r($vcard->categories, true) );
   }

    /**
     * @covers vCard::__construct
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

	$vcard = new vCard(false, $input);
	$this->assertInstanceOf('vCardTools\vCard', $vcard);

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
     * @covers vCard::__construct
     * @depends testImportEmptyVCard
     */
    public function testImportVCardNoURL()
    {
	$input =	self::$vcard_begin . "\n"
			. self::$vcard_version . "\n"
			. self::$vcard_end . "\n";

	$vcard = new vCard(false, $input);
	$this->assertInstanceOf('vCardTools\vCard', $vcard);
	$this->assertEmpty($vcard->url);
    }

    /**
     * @covers vCard::__construct
     * @depends testImportEmptyVCard
     */
    public function testImportVCardEmptyURL()
    {
	$input =	self::$vcard_begin . "\n"
			. self::$vcard_version . "\n"
			. "URL:\n"
			. self::$vcard_end . "\n";

	$vcard = new vCard(false, $input);
	$this->assertInstanceOf('vCardTools\vCard', $vcard);

	$this->assertEmpty( $vcard->url,
			    print_r($vcard->url, true) );
    }

    /**
     * @covers vCard::__construct
     * @depends testImportEmptyVCard
     * @dataProvider stringEscapeProvider
     */
    public function testImportVCardOneURL($unescaped, $escaped)
    {
	$input =	self::$vcard_begin . "\n"
			. self::$vcard_version . "\n"
			. "URL:" . $escaped . "\n"
			. self::$vcard_end . "\n";

	$vcard = new vCard(false, $input);
	$this->assertInstanceOf('vCardTools\vCard', $vcard);

	$this->assertNotEmpty($vcard->url);
	$this->assertInternalType("array", $vcard->url);
	$this->assertCount(1, $vcard->url);
	$this->assertContains( $unescaped, $vcard->url,
				print_r($vcard->url, true) );
   }

    /**
     * @covers vCard::__construct
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

	$vcard = new vCard(false, $input);
	$this->assertInstanceOf('vCardTools\vCard', $vcard);

	$this->assertNotEmpty($vcard->url);
	$this->assertInternalType("array", $vcard->url);
	$this->assertCount(1, $vcard->url);
	$this->assertContains( $unescaped, $vcard->url,
				print_r($vcard->url, true) );
   }

    /**
     * @covers vCard::__construct
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

	$vcard = new vCard(false, $input);
	$this->assertInstanceOf('vCardTools\vCard', $vcard);

	$this->assertNotEmpty($vcard->url);
	$this->assertInternalType("array", $vcard->url);
	$this->assertCount(2, $vcard->url);
	$this->assertContains( $url1, $vcard->url,
				print_r($vcard->url, true) );
	$this->assertContains( $url2, $vcard->url,
				print_r($vcard->url, true) );

   }
   
   /**
    * @depends testImportEmptyVCard
    * @depends testToStringDDBinks
    */
   public function testImportVCardDDBinks()
   {
       $vcard = $this->getDDBinks();
       $vcard_string = '' . $vcard;
       
       $vcard2 = new vCard(null, $vcard_string);
       unset($vcard2->version);
       $this->assertEquals($vcard, $vcard2);
   }
   
   /**
    * @depends testImportEmptyVCard
    * @depends testToStringRaithSeinar
    */
   public function testImportVCardRaithSeinar()
   {
   	$vcard = $this->getRaithSeinar();
   	$vcard_string = '' . $vcard;
   	 
   	$vcard2 = new vCard(null, $vcard_string);
   	unset($vcard2->version);
   	$this->assertEquals($vcard, $vcard2);
   }

   /**
    * @depends testImportEmptyVCard
    * @depends testToStringSeinarAPL
    */
   public function testImportVCardSeinarAPL()
   {
   	$vcard = $this->getSeinarAPL();
   	$vcard_string = '' . $vcard;
   	 
   	$vcard2 = new vCard(null, $vcard_string);
   	unset($vcard2->version);
   	$this->assertEquals($vcard, $vcard2);
   }

   /**
    * @depends testImportEmptyVCard
    * @depends testToStringDDBinks
    */
   public function testImportVCardDDBinksFromFile()
   {
   	$path = 'tests/vcards/DDBinks.vcf';
   	$vcard = $this->getDDBinks();
   	 
   	$vcard2 = new vCard($path);
   	unset($vcard2->version);
   	 
   	$this->assertEquals($vcard, $vcard2);
   }   
   
   /**
    * @depends testImportEmptyVCard
    * @depends testToStringRaithSeinar
    */
   public function testImportVCardRaithSeinarFromFile()
   {
   	$path = 'tests/vcards/RaithSeinar.vcf';
   	$vcard = $this->getRaithSeinar();
   	 
   	$vcard2 = new vCard($path);
   	unset($vcard2->version);
   
   	$this->assertEquals($vcard, $vcard2);
   }
    
   
   /**
    * @depends testImportEmptyVCard
    * @depends testToStringSeinarAPL
    */
   public function testImportVCardSeinarAPLFromFile()
   {
   	$path = 'tests/vcards/SeinarAPL.vcf';
   	$vcard = $this->getSeinarAPL();
   	 
   	$vcard2 = new vCard($path);
   	unset($vcard2->version);
   	
   	$this->assertEquals($vcard, $vcard2);
   }
   
   /**
    * Make sure the magic __call method works correctly for call chaining.
    * @covers vCard::__set
    * @depends testSetTwoURLs
    */
   public function testSetChaining(vCard $vcard)
   {
   	$url1 = "foo";
   	$url2 = "baz";
   	$tel  = "999-454-3212";
   	
   	$vcard  ->url($url1)
   	        ->url($url2)
   	        ->tel($tel);
   	
   	$this->assertNotEmpty($vcard->url);
   	$this->assertInternalType("array", $vcard->url);
   	$this->assertCount(2, $vcard->url);
   	$this->assertContains( $url1, $vcard->url,
   			print_r($vcard->url, true) );
   	$this->assertContains( $url2, $vcard->url,
   			print_r($vcard->url, true) );
   	
   	$this->assertNotEmpty($vcard->tel);
   	$this->assertInternalType("array", $vcard->tel);
   	$this->assertCount(1, $vcard->tel);
   	$this->assertContains( $tel, $vcard->tel,
   			print_r($vcard->tel, true) );
   	
   }
}
