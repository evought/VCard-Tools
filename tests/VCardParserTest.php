<?php
/**
 * VCardParserTest
 * @author Eric Vought <evought@pobox.com>
 * 2014-11-16
 * @copyright Eric Vought 2014, Some rights reserved.
 * @license MIT http://opensource.org/licenses/MIT
 */

/*
 * The MIT License
 *
 * Copyright 2014 evought.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace EVought\vCardTools;

/**
 * Tests for VCardParser
 */
class VCardParserTest extends \PHPUnit_Framework_TestCase
{
    // some components of expected values
    static $vcard_begin = "BEGIN:VCARD";
    static $vcard_end = "END:VCARD";
    static $vcard_version = "VERSION:4.0";
    static $vcard_empty_fn = "FN:";
    
    /**
     * @var VCardParser
     */
    protected $parser;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->parser = new VCardParser;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        
    }
    
    /**
     * Provides a variety of string values to ensure that parsing is correct
     * and dangerous values are escaped on output (comma, colon, newline,
     * semi-colon, and backslash). RFC 6350 Sec. 3.4.
     */
    public function stringEscapeProvider()
    {
	// unescaped, escaped
	return [
	    [ 'LettersAnd0123456789', 	'LettersAnd0123456789' ],
	    [ 'Com,ma',			'Com\,ma' ],
	    [ 'Col:on',			'Col\:on' ],
	    ['Semi;colon',		'Semi\;colon'],
	    ["A \newline",              'A \newline'],
	    ['backs\\ash',		'backs\\\\ash'],
	    ['s/ash',			's/ash'],
	    ['angle bra<ket',		'angle bra<ket'],
	    ['(&other $tuff)',		'(&other $tuff)'],
	    ['http://foobar.baz?yut=boo', 'http\://foobar.baz?yut=boo'],
	    ['BEGIN:VCARD',		'BEGIN\:VCARD']
	];
    }
    
    /**
     * Property values for a complex vCard for developing further tests,
     * particularly with round-trip TYPE and other parameter support.
     * @return multitype:string multitype:string
     */
    public function getJohnDoeInputs()
    {
    	$inputs = [
                'n'     => [
                        'GivenName'         => 'John',
                        'FamilyName'        => 'Doe',
                        'AdditionalNames'   => 'Q., Public'
                    ],
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
                'adr'   => [
                        'StreetAddress' => '42 Plantation St.',
                        'Locality'      => 'Baytown',
                        'Region'        => 'LA',
                        'PostalCode'    => '30314',
                        'Country'       => 'United States of America'
                    ],
		'adr_type'          => 'HOME',
		'url'               => 'https://www.google.com/',
		'photo'             => 'http://upload.wikimedia.org/wikipedia/commons/thumb/a/a5/Example_svg.svg/200px-Example_svg.svg.png',
		'photo_parameters'   => ['VALUE' => 'URL', 'TYPE' => 'PNG']			
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
                    'uid'           => 'urn:uuid:c5b5735e-9217-11e4-94de-40167e365cc1',
    	            'n' => [
                        'GivenName' => 'Darth',
                        'AdditionalNames' => 'Darth',
                        'FamilyName'  => 'Binks'
                    ],
                    'org'       => 'Sith',
    	            'fn'          => 'Darth Darth Binks',
    	            'kind'        => 'individual'
    	];
    	return $inputs;
    }
    
    public function getDDBinks()
    {
    	$inputs = $this->getDDBinksInputs();
    	
    	$vcard = new VCard();

        $vcard->setUID($inputs['uid']);
        VCard::builder('n')->setValue($inputs['n'])->pushTo($vcard);
        VCard::builder('org')->setField('Name', $inputs['org'])->pushTo($vcard);
        VCard::builder('fn')->setValue($inputs['fn'])->pushTo($vcard);
        VCard::builder('kind')->setValue($inputs['kind'])->pushTo($vcard);

    	return $vcard; 
    }
    
    /**
     * Return the property values to build a sample vcard.
     * @return array
     */
    public function getRaithSeinarInputs()
    {
    	$inputs = [
                    'uid'         => 'urn:uuid:d7b14fc0-922a-11e4-aa1c-40167e365cc1',
                    'n' => [
                        'GivenName'     => 'Raith',
                        'FamilyName'    => 'Seinar'
                        ],
                    'org' => [
                        'Name'          => 'Seinar Fleet Systems'
                        ],
                    'title'	  => 'CEO',
                    'fn'          => 'Raith Seinar',
                    'category1'   => 'military industrial',
                    'category2'   => 'empire',
                    'kind'        => 'individual'
                    ];
    	 
    	return $inputs;
    }
    
    /**
     * Fetch a pre-constructed sample vcard.
     * @return an individual VCard.
     */
    public function getRaithSeinar()
    {
    	$vcard = new VCard();
        
    	$inputs = $this->getRaithSeinarInputs();    	
        $vcard->setUID($inputs['uid']);
        
        VCard::builder('n')->setValue($inputs['n'])->pushTo($vcard);
        VCard::builder('org')->setValue($inputs['org'])->pushTo($vcard);
        VCard::builder('title')->setValue($inputs['title'])->pushTo($vcard);
        VCard::builder('fn')->setValue($inputs['fn'])->pushTo($vcard);
        VCard::builder('categories')->setValue($inputs['category1'])
            ->pushTo($vcard);
        VCard::builder('categories')->setValue($inputs['category2'])
            ->pushTo($vcard);
        VCard::builder('kind')->setValue($inputs['kind'])->pushTo($vcard);

        return $vcard;
    }
    
    /**
     * Return the property values to build a sample vcard.
     * @return array
     */
    public function getSeinarAPLInputs()
    {
    	$inputs = [
            'uid'         => 'urn:uuid:7a5148c4-922c-11e4-9246-40167e365cc1',
    	    'org' => [
                    'Name'    => 'Seinar Fleet Systems',
                    'Unit1'   => 'Seinar Advanced Projects Laboratory',
                    'Unit2'   => 'TIE AO1X Division'
                ],
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
     * Fetch a pre-constructed sample vcard.
     * @return an individual VCard.
     */
    public function getSeinarAPL()
    {
    	$inputs = $this->getSeinarAPLInputs();
    
    	$vcard = new VCard();
        $vcard->setUID($inputs['uid']);
        VCard::builder('org')->setValue($inputs['org'])->pushTo($vcard);
        VCard::builder('fn')->setValue($inputs['fn'])->pushTo($vcard);
        VCard::builder('logo')->setValue($inputs['logo'])->pushTo($vcard);
        VCard::builder('categories')
                ->setValue($inputs['category1'])->pushTo($vcard);
        VCard::builder('categories')
                ->setValue($inputs['category2'])->pushTo($vcard);
        VCard::builder('categories')
                    ->setValue($inputs['category3'])->pushTo($vcard);
        VCard::builder('kind')->setValue($inputs['kind'])->pushTo($vcard);

        return $vcard;
    }
    
    /**
     * @group default
     */
    public function testGetUIDsEmpty()
    {
        $this->assertEmpty($this->parser->getUIDs());
    }

    /**
     * @group default
     */
    public function testClear()
    {
        $this->parser->clear();
        $this->assertEmpty($this->parser->getUIDs());
    }

    /**
     * @group default
     * @expectedException \DomainException
     */
    public function testImportBadCardGarbage()
    {
        $this->parser->importCards('Garbage');
    }
    
    /**
     * @group default
     * @expectedException \DomainException
     */
    public function testImportBadVCardNoVersion()
    {
        $input =    self::$vcard_begin . "\r\n"
                    . self::$vcard_end . "\r\n";
        $this->parser->importCards($input);
    }

    /**
     * @group default
     * @expectedException \DomainException
     */
    public function testImportBadVCardNoEnd()
    {
        $input =    self::$vcard_begin . "\r\n"
                    . self::$vcard_version . "\r\n";
        $this->parser->importCards($input);
    }
    
    /**
     * @group default
     */
    public function testImportEmptyVCard()
    {
	$input =	self::$vcard_begin . "\r\n"
			. self::$vcard_version . "\r\n"
			. self::$vcard_end . "\r\n";

        $vcards = $this->parser->importCards($input);
        
        $this->assertCount(1, $vcards);
        
        $this->assertInstanceOf('EVought\vCardTools\VCard', $vcards[0]);
        $this->assertCount(0, $vcards[0]);
        $this->assertEquals( $this->parser->getCard($vcards[0]->getUID()),
                             $vcards[0] );
    }

    /**
     * @group default
     * @depends testImportEmptyVCard
     * @expectedException EVought\vCardTools\Exceptions\MalformedPropertyException
     */
    public function testImportVCardEmptyFN()
    {
	$input =	self::$vcard_begin . "\r\n"
			. self::$vcard_version . "\r\n"
			. self::$vcard_empty_fn . "\r\n"
			. self::$vcard_end . "\r\n";

	$this->parser->importCards($input);
    }
    
    /**
     * @group default
     * @depends testImportEmptyVCard
     */
    public function testImportVCardUndefinedProperty()
    {
	$input =	self::$vcard_begin . "\r\n"
			. self::$vcard_version . "\r\n"
			. 'FOO:bar' . "\r\n"
			. self::$vcard_end . "\r\n";

        $vcards = $this->parser->importCards($input);
        
        $this->assertCount(1, $vcards);
        $this->assertCount(1, $vcards[0]);
        
        $this->assertNotNull($vcards[0]->foo);
        $this->assertEquals('bar', $vcards[0]->foo[0]->getValue());
    }
    
    /**
     * @group default
     * @depends testImportVCardEmptyFN
     * @dataProvider stringEscapeProvider
     */
    public function testImportVCardFN($unescaped, $escaped)
    {
	$input =	self::$vcard_begin . "\r\n"
			. self::$vcard_version . "\r\n"
			. "FN:" . $escaped . "\r\n"
			. self::$vcard_end . "\r\n";

	$vcards = $this->parser->importCards($input);
        
        $this->assertCount(1, $vcards);
        
        $this->assertCount(1, $vcards[0]);
        $this->assertCount(1, $vcards[0]->fn);
	$this->assertEquals($unescaped, $vcards[0]->fn[0]->getValue());
    }
    
    /**
     * @group default
     * @depends testImportEmptyVCard
     */
    public function testImportVCardUID()
    {
	$input =	self::$vcard_begin . "\r\n"
			. self::$vcard_version . "\r\n"
			. "FN:Foo\r\n"
                        . "UID:Bar\r\n"
			. self::$vcard_end . "\r\n";

        $vcards = $this->parser->importCards($input);

        $this->assertCount(1, $vcards);
	$this->assertEquals('Bar', $vcards[0]->getUID());
    }
    
    /**
     * Verify that import works the same with just newlines instead of CRLF.
     * @group default
     * @depends testImportVCardEmptyFN
     * @dataProvider stringEscapeProvider
     */
    public function testImportVCardFNNL($unescaped, $escaped)
    {
	$input =	self::$vcard_begin . "\n"
			. self::$vcard_version . "\n"
			. "FN:" . $escaped . "\n"
			. self::$vcard_end . "\n";

        $vcards = $this->parser->importCards($input);
        $this->assertCount(1, $vcards);
        $this->assertCount(1, $vcards[0]->fn);
	$this->assertEquals($unescaped, $vcards[0]->fn[0]->getValue());
    }
    
    /**
     * Verify that import works the same with just carriage returns (Mac)
     * instead of CRLF.
     * @group default
     * @depends testImportVCardEmptyFN
     * @dataProvider stringEscapeProvider
     */
    public function testImportVCardFNCR($unescaped, $escaped)
    {
	$input =	self::$vcard_begin . "\r"
			. self::$vcard_version . "\r"
			. "FN:" . $escaped . "\r"
			. self::$vcard_end . "\r";

        $vcards = $this->parser->importCards($input);
        $this->assertCount(1, $vcards);
        $this->assertCount(1, $vcards[0]->fn);
	$this->assertEquals($unescaped, $vcards[0]->fn[0]->getValue());
    }
    
    /**
     * @group default
     * @depends testImportVCardFN
     */
    public function testImportVCardTelDefaultPref()
    {
	$input =	self::$vcard_begin . "\r\n"
			. self::$vcard_version . "\r\n"
			. 'TEL:555-1212' . "\r\n"
			. self::$vcard_end . "\r\n";

	$vcards = $this->parser->importCards($input);
        
        $this->assertCount(1, $vcards);
        
        $this->assertCount(1, $vcards[0]);
        $this->assertCount(1, $vcards[0]->tel);
        $tel = $vcards[0]->tel[0];
	$this->assertEquals('555-1212', $tel->getValue());
        $this->assertEquals(100, $tel->getPref());
    }
    
    /**
     * @group default
     * @depends testImportVCardFN
     */
    public function testImportVCardTelPref()
    {
	$input =	self::$vcard_begin . "\r\n"
			. self::$vcard_version . "\r\n"
			. 'TEL;PREF=1:555-1212' . "\r\n"
			. self::$vcard_end . "\r\n";

	$vcards = $this->parser->importCards($input);
        
        $this->assertCount(1, $vcards);
        
        $this->assertCount(1, $vcards[0]);
        $this->assertCount(1, $vcards[0]->tel);
        $tel = $vcards[0]->tel[0];
	$this->assertEquals('555-1212', $tel->getValue());
        $this->assertEquals(1, $tel->getPref());
    }
    
    /**
     * @group default
     * @group vcard30
     * @depends testImportVCardFN
     */
    public function testImportVCardTelPrefAsType30()
    {
	$input =	self::$vcard_begin . "\r\n"
			. 'VERSION:3.0' . "\r\n"
			. 'TEL;TYPE=PREF:555-1212' . "\r\n"
			. self::$vcard_end . "\r\n";

	$vcards = $this->parser->importCards($input);
        
        $this->assertCount(1, $vcards);
        
        $this->assertCount(1, $vcards[0]);
        $this->assertCount(1, $vcards[0]->tel);
        $tel = $vcards[0]->tel[0];
	$this->assertEquals('555-1212', $tel->getValue());
        $this->assertEquals(1, $tel->getPref());
    }
    
    /**
     * @group default
     * @depends testImportVCardFN
     */
    public function testImportVCardAdr()
    {
        $jDoeInputs = $this->getJohnDoeInputs();
        
	$input =	self::$vcard_begin . "\r\n"
			. self::$vcard_version . "\r\n"
			. 'ADR:;;'
                            . $jDoeInputs['adr']['StreetAddress']
                            . ';' . $jDoeInputs['adr']['Locality']
                            . ';' . $jDoeInputs['adr']['Region']
                            . ';' . $jDoeInputs['adr']['PostalCode']
                            . ';' . $jDoeInputs['adr']['Country'] . "\r\n"
			. self::$vcard_end . "\r\n";

        $expectedAdr = VCard::builder('adr')->setValue($jDoeInputs['adr'])
                            ->build();

        $vcards = $this->parser->importCards($input);
        $this->assertCount(1, $vcards);
        $this->assertCount(1, $vcards[0]->adr);
	$this->assertEquals($expectedAdr, $vcards[0]->adr[0]);
    }
    
    /**
     * @group default
     * @depends testImportVCardAdr
     */
    public function testImportVCardAdrWType()
    {
        $jDoeInputs = $this->getJohnDoeInputs();
        
	$input = self::$vcard_begin . "\r\n"
		. self::$vcard_version . "\r\n"
		. 'ADR;TYPE=HOME:;;42 Plantation St.;Baytown;LA;30314;United States of America' . "\r\n"
		. self::$vcard_end . "\r\n";

        $expectedAdr = VCard::builder('adr')
            ->setValue($jDoeInputs['adr'])
            ->addType(strtolower($jDoeInputs['adr_type']))
            ->build();

        $vcards = $this->parser->importCards($input);
        $this->assertCount(1, $vcards);
        $this->assertCount(1, $vcards[0]->adr);
	$this->assertEquals($expectedAdr, $vcards[0]->adr[0]);
    }

    /**
     * @group default
     * @group vcard21
     * @depends testImportVCardAdr
     */
    public function testImportVCardAdrWBareType21()
    {
        $jDoeInputs = $this->getJohnDoeInputs();
        
	$input = self::$vcard_begin . "\r\n"
		. "VERSION:2.1" . "\r\n"
		. 'ADR;HOME:;;42 Plantation St.;Baytown;LA;30314;United States of America' . "\r\n"
		. self::$vcard_end . "\r\n";

        $expectedAdr = VCard::builder('adr')
            ->setValue($jDoeInputs['adr'])
            ->addType(strtolower($jDoeInputs['adr_type']))
            ->build();

        $vcards = $this->parser->importCards($input);
        $this->assertCount(1, $vcards);
        $this->assertCount(1, $vcards[0]->adr);
	$this->assertEquals($expectedAdr, $vcards[0]->adr[0]);
    }

    /**
     * @group default
     * @depends testImportVCardAdr
     * @expectedException EVought\vCardTools\Exceptions\MalformedParameterException
     */
    public function testImportVCardAdrWBareType40()
    {
        $jDoeInputs = $this->getJohnDoeInputs();
        
	$input = self::$vcard_begin . "\r\n"
		. self::$vcard_version . "\r\n"
		. 'ADR;HOME:;;42 Plantation St.;Baytown;LA;30314;United States of America' . "\r\n"
		. self::$vcard_end . "\r\n";
        $vcards = $this->parser->importCards($input);
    }
    
    /**
     * @group default
     * @depends testImportVCardFN
     * @expectedException EVought\vCardTools\Exceptions\MalformedPropertyException
     * @expectedExceptionMessage CATEGORIES
     */
    public function testImportVCardEmptyCategories()
    {
	$input =	self::$vcard_begin . "\r\n"
			. self::$vcard_version . "\r\n"
			. "CATEGORIES:\r\n"
			. self::$vcard_end . "\r\n";

        $vcards = $this->parser->importCards($input);
    }
    
    /**
     * @group default
     * @depends testImportVCardFN
     * @dataProvider stringEscapeProvider
     */
    public function testImportVCardOneCategory($unescaped, $escaped)
    {
	$input =	self::$vcard_begin . "\r\n"
			. self::$vcard_version . "\r\n"
			. 'CATEGORIES:"' . $escaped . '"'."\r\n"
			. self::$vcard_end . "\r\n";

        $vcards = $this->parser->importCards($input);
        
        $vcards = $this->parser->importCards($input);
        $this->assertCount(1, $vcards);
        $this->assertCount(1, $vcards[0]->categories);
	$this->assertEquals($unescaped, $vcards[0]->categories[0]->getValue());
   }
   
       /**
     * @group default
     * @depends testImportVCardOneCategory
     */
    public function testImportVCardTwoCategories()
    {
	$category1 = "farrier";
	$category2 = "smurf";
	$input =	self::$vcard_begin . "\r\n"
			. self::$vcard_version . "\r\n"
			. "CATEGORIES:" . $category1 . "\r\n"
			. "CATEGORIES:" . $category2 . "\r\n"
			. self::$vcard_end . "\r\n";

        $vcards = $this->parser->importCards($input);
        
        $builder = VCard::builder('categories');
        $catProp1 = $builder->setValue($category1)->build();
        $catProp2 = $builder->setValue($category2)->build();

        $this->assertCount(1, $vcards);
        $this->assertCount(2, $vcards[0]->categories);
	$this->assertEquals([$catProp1, $catProp2], $vcards[0]->categories);
    }
    
    /**
     * @group default
     * @depends testImportVCardOneCategory
     */
    public function testImportVCardTwoCategoriesComma()
    {
	$category1 = "farrier";
	$category2 = "smurf";
	$input =	self::$vcard_begin . "\r\n"
			. self::$vcard_version . "\r\n"
			.'CATEGORIES;TYPE=WORK:'.$category1.','.$category2."\r\n"
			. self::$vcard_end . "\r\n";

        $vcards = $this->parser->importCards($input);
        
        $builder = VCard::builder('categories')->addType('work');
        $catProp1 = $builder->setValue($category1)->build();
        $catProp2 = $builder->setValue($category2)->build();

        $this->assertCount(1, $vcards);
        $this->assertCount(2, $vcards[0]->categories);
	$this->assertEquals([$catProp1, $catProp2], $vcards[0]->categories);
    }
    
    /**
     * @group default
     * @depends testImportVCardFN
     * @expectedException EVought\vCardTools\Exceptions\MalformedPropertyException
     * @expectedExceptionMessage URL
     */
    public function testImportVCardEmptyURL()
    {
	$input =	self::$vcard_begin . "\r\n"
			. self::$vcard_version . "\r\n"
			. "URL:\r\n"
			. self::$vcard_end . "\r\n";

        $vcards = $this->parser->importCards($input);
    }
    
    /**
     * @group default
     * @depends testImportVCardFN
     * @dataProvider stringEscapeProvider
     */
    public function testImportVCardOneURL($unescaped, $escaped)
    {
	$input =	self::$vcard_begin . "\r\n"
			. self::$vcard_version . "\r\n"
			. "URL:" . $escaped . "\r\n"
			. self::$vcard_end . "\r\n";

        $vcards = $this->parser->importCards($input);
        $this->assertCount(1, $vcards);
        $this->assertCount(1, $vcards[0]->url);
	$this->assertEquals($unescaped, $vcards[0]->url[0]->getValue());
    }

    /**
     * @group default
     * @depends testImportVCardOneURL
     */
    public function testImportVCardTwoURLs()
    {
	$url1 = "tweedldee";
	$url2 = "tweedledum";
	$input =	self::$vcard_begin . "\r\n"
			. self::$vcard_version . "\r\n"
			. "URL:" . $url1 . "\r\n"
			. "URL:" . $url2 . "\r\n"
			. self::$vcard_end . "\r\n";

        $vcards = $this->parser->importCards($input);
        
        $builder = VCard::builder('url');
        $prop1 = $builder->setValue($url1)->build();
        $prop2 = $builder->setValue($url2)->build();

        $this->assertCount(1, $vcards);
        $this->assertCount(2, $vcards[0]->url);
	$this->assertEquals([$prop1, $prop2], $vcards[0]->url);
    }

    /**
     * @group default
     * @depends testImportVCardFN
     */
    public function testImportVCardAgentURI()
    {
    	$input =	self::$vcard_begin . "\r\n"
			. self::$vcard_version . "\r\n"
			. 'AGENT;VALUE=uri:CID:JQPUBLIC.part3.960129T083020.xyzMail@host3.com' . "\r\n"
			. self::$vcard_end . "\r\n";

        $vcards = $this->parser->importCards($input);
        $this->assertCount(1, $vcards);
        $this->assertCount(1, $vcards[0]->related);
	$this->assertEquals(
            'CID:JQPUBLIC.part3.960129T083020.xyzMail@host3.com',
            $vcards[0]->related[0]->getValue() );
        $this->assertEquals('uri', $vcards[0]->related[0]->getValueType());
        $this->assertEquals(['agent'], $vcards[0]->related[0]->getTypes());
    }
    
    /**
     * @group default
     * @depends testImportVCardFN
     */
    public function testImportVCardAgent()
    {
    	$input =	self::$vcard_begin . "\r\n"
			. self::$vcard_version . "\r\n"
                        . 'UID:test1' . "\r\n"
			. 'AGENT:BEGIN:VCARD\nUID:test2\nFN:Susan Thomas\nTEL:+1-919-555-1234\nEMAIL\;TYPE=INTERNET:sthomas@host.com\nEND:VCARD\n' . "\r\n"
			. self::$vcard_end . "\r\n";

        $vcards = $this->parser->importCards($input);
        $this->assertCount(2, $vcards);
        
        $test1 = $this->parser->getCard('test1');
        $this->assertNotNull($test1);
        $this->assertCount(1, $test1->related);
        $this->assertEquals(['agent'], $test1->related[0]->getTypes());
        $this->assertEquals('test2', $test1->related[0]->getValue());
        $this->assertEquals('uri', $test1->related[0]->getValueType());
        
        $test2 = $this->parser->getCard('test2');
        $this->assertNotNull($test2);
        $this->assertCount(1, $test2->fn);
        $this->assertEquals('Susan Thomas', $test2->fn[0]->getValue());
        $this->assertCount(1, $test2->tel);
        $this->assertEquals('+1-919-555-1234', $test2->tel[0]->getValue());
        $this->assertCount(1, $test2->email);
        $this->assertEquals('sthomas@host.com', $test2->email[0]->getValue());
        $this->assertEquals(['internet'], $test2->email[0]->getTypes());
    }
    
    /**
     * @group default
     * @group vcard30
     * @depends testImportVCardFN
     */
    public function testImportMediaTypeInType30()
    {
        $input =	self::$vcard_begin . "\r\n"
			. 'VERSION:3.0' . "\r\n"
			. 'PHOTO;TYPE=GIF:http\://example.com/photo.gif' . "\r\n"
			. self::$vcard_end . "\r\n";

        $vcards = $this->parser->importCards($input);
        $this->assertCount(1, $vcards);
        $this->assertCount(1, $vcards[0]->photo);
        $photo = $vcards[0]->photo[0];
	$this->assertEquals('http://example.com/photo.gif', $photo->getValue());
        $this->assertEquals('image/gif', $photo->getMediaType());
    }

    
    /**
     * @group default
     * @depends testImportVCardFN
     */
    public function testImportVCardDDBinks()
    {
        $vcard = $this->getDDBinks();
        $vcard_string = $vcard->output();
       
        $vcards = $this->parser->importCards($vcard_string);
        $this->assertCount(1, $vcards);
        $this->assertEquals($vcard, $vcards[0]);
    }
    
    /**
     * @group default
     * @depends testImportVCardFN
     */
    public function testImportVCardRaithSeinar()
    {
   	$vcard = $this->getRaithSeinar();
   	$vcard_string = $vcard->output();
   	 
        $vcards = $this->parser->importCards($vcard_string);
        $this->assertCount(1, $vcards);
        $this->assertEquals($vcard, $vcards[0]);
    }
    
    /**
     * @group default
     * @depends testImportVCardFN
     */
    public function testImportVCardSeinarAPL()
    {
   	$vcard = $this->getSeinarAPL();
   	$vcard_string = $vcard->output();
   	 
        $vcards = $this->parser->importCards($vcard_string);
        $this->assertCount(1, $vcards);
        $this->assertEquals($vcard, $vcards[0]);
    }
    
    /**
     * @group default
     * @depends testImportVCardDDBinks
     */
    public function testImportVCardDDBinksFromFile()
    {
   	$path = __DIR__ . '/vcards/DDBinks.vcf';
   	$vcard = $this->getDDBinks();
   	 
        $vcards = $this->parser->importFromFile($path);
        $this->assertCount(1, $vcards);
        $this->assertEquals($vcard, $vcards[0]);
    }
    
    /**
     * @group default
     * @depends testImportVCardRaithSeinar
     */
    public function testImportVCardRaithSeinarFromFile()
    {
   	$path = __DIR__ . '/vcards/RaithSeinar.vcf';
   	$vcard = $this->getRaithSeinar();
   	 
        $vcards = $this->parser->importFromFile($path);
        $this->assertCount(1, $vcards);
        $this->assertEquals($vcard, $vcards[0], $vcards[0]->getUID());
    }
    
       
    /**
     * @group default
     * @depends testImportVCardSeinarAPL
     */
    public function testImportVCardSeinarAPLFromFile()
    {
   	$path = __DIR__ . '/vcards/SeinarAPL.vcf';
   	$vcard = $this->getSeinarAPL();
   	 
        $vcards = $this->parser->importFromFile($path);
        $this->assertCount(1, $vcards);
        $this->assertEquals($vcard, $vcards[0], $vcards[0]->getUID());
    }

    /**
     * @group default
     * @depends testImportVCardSeinarAPLFromFile
     * @depends testImportVCardRaithSeinarFromFile
     * @depends testImportVCardDDBinksFromFile
     */
    public function testImportMultipleVCards()
    {
        foreach (['DDBinks.vcf', 'RaithSeinar.vcf', 'SeinarAPL.vcf'] as $file)
        {
            $path = __DIR__ . '/vcards/' . $file;
            $vcards = $this->parser->importFromFile($path);
            $this->assertCount(1, $vcards);
        }
        $ddBinks = $this->getDDBinks();
        $raithSeinar = $this->getRaithSeinar();
        $seinarAPL = $this->getSeinarAPL();
   	 
        $uids = $this->parser->getUIDs();
        $this->assertCount(3, $uids);
        $this->assertContains($ddBinks->getUID(), $uids);
        $this->assertContains($raithSeinar->getUID(), $uids);
        $this->assertContains($seinarAPL->getUID(), $uids);
        $this->assertEquals( $ddBinks,
                $this->parser->getCard($ddBinks->getUID()) );
        $this->assertEquals( $raithSeinar,
                $this->parser->getCard($raithSeinar->getUID()) );
        $this->assertEquals( $seinarAPL,
                $this->parser->getCard($seinarAPL->getUID()) );
    }

    /**
     * @group default
     * @depends testImportVCardSeinarAPLFromFile
     * @depends testImportVCardRaithSeinarFromFile
     * @depends testImportVCardDDBinksFromFile
     */
    public function testImportMultipleVCardsOnePass()
    {
        $inputString = '';
        foreach (['DDBinks.vcf', 'RaithSeinar.vcf', 'SeinarAPL.vcf'] as $file)
        {
            $path = __DIR__ . '/vcards/' . $file;
            $inputString .= \file_get_contents($path);
            $vcards = $this->parser->importCards($inputString);
        }
        $this->assertCount(3, $vcards);

        $ddBinks = $this->getDDBinks();
        $raithSeinar = $this->getRaithSeinar();
        $seinarAPL = $this->getSeinarAPL();

        $this->assertEquals($ddBinks, $vcards[0]);
        $this->assertEquals($raithSeinar, $vcards[1]);
        $this->assertEquals($seinarAPL, $vcards[2]);
        
        $uids = $this->parser->getUIDs();
        $this->assertCount(3, $uids);
        $this->assertContains($ddBinks->getUID(), $uids);
        $this->assertContains($raithSeinar->getUID(), $uids);
        $this->assertContains($seinarAPL->getUID(), $uids);
        $this->assertEquals( $ddBinks,
                $this->parser->getCard($ddBinks->getUID()) );
        $this->assertEquals( $raithSeinar,
                $this->parser->getCard($raithSeinar->getUID()) );
        $this->assertEquals( $seinarAPL,
                $this->parser->getCard($seinarAPL->getUID()) );
    }
    
    /**
     * @group default
     */
    public function testGetCardBody()
    {
        $input =   'BEGIN:VCARD'."\n"
                 . 'VERSION:4.0'."\n"
                 . 'FN:foo'."\n"
                 . 'END:VCARD'."\n";
        $components = $this->parser->getCardBody($input);
        $this->assertArrayHasKey('version', $components);
        $this->assertEquals('4.0', $components['version']);
        $this->assertArrayHasKey('body', $components);
        $this->assertEquals("FN:foo\n", $components['body']);
    }
    
    /**
     * @group default
     * @depends testGetCardBody
     */
    public function testGetCardBodiesJustOne()
    {
        $input =   'BEGIN:VCARD'."\n"
                 . 'VERSION:4.0'."\n"
                 . 'FN:foo'."\n"
                 . 'END:VCARD'."\n";
        $matches = $this->parser->getCardBodies($input);
        $this->assertCount(1, $matches);
        
        $components = $matches[0];
        $this->assertArrayHasKey('version', $components);
        $this->assertEquals('4.0', $components['version']);
        $this->assertArrayHasKey('body', $components);
        $this->assertEquals("FN:foo\n", $components['body']);
    }
    
    /**
     * @group default
     * @depends testGetCardBody
     */
    public function testGetCardBodies()
    {
        $input =   'BEGIN:VCARD'."\n"
                 . 'VERSION:4.0'."\n"
                 . 'FN:foo'."\n"
                 . 'END:VCARD'."\n"
                 . 'BEGIN:VCARD'."\n"
                 . 'VERSION:4.0'."\n"
                 . 'FN:foo'."\n"
                 . 'END:VCARD'."\n"
                 . 'BEGIN:VCARD'."\n"
                 . 'VERSION:4.0'."\n"
                 . 'FN:foo'."\n"
                 . 'END:VCARD'."\n";
        
        $matches = $this->parser->getCardBodies($input);
        $this->assertCount(3, $matches, print_r($matches, true));
        
        foreach ($matches as $components)
        {
            $this->assertArrayHasKey('version', $components);
            $this->assertEquals('4.0', $components['version']);
            $this->assertArrayHasKey('body', $components);
            $this->assertEquals("FN:foo\n", $components['body']);
        }
    }

    public function unfold4Provider()
    {
        // folded, unfolded
        return [
            ["Text \n with soft wrap.", "Text with soft wrap."],
            ["Tab\n\t wrap",            "Tab wrap"],
            ["No following \nLWSP",     "No following \nLWSP"],
            ["Nothing interesting",     "Nothing interesting"],
            ["\n \n\t\n ",              ""]
        ];
    }
   
    /**
     * @group default
     * @dataProvider unfold4Provider
     */
    public function testUnfold4($folded, $unfolded)
    {
        $output = VCardParser::unfold4($folded);
        $this->assertEquals($unfolded, $output);
    }

    public function unfold21Provider()
    {
        // folded, unfolded
        return [
            ["Text\n with soft wrap.", "Text with soft wrap."],
            ["Tab\n\t wrap",           "Tab\t wrap"],
            ["No following \nLWSP",    "No following \nLWSP"],
            ["Nothing interesting",    "Nothing interesting"],
            ["\n \n\t\n ",             " \t "]
        ];
    }
   
   /**
    * @group default
    * @group vcard21
    * @dataProvider unfold21Provider
    */
   public function testUnfold21($folded, $unfolded)
   {
       $output = VCardParser::unfold21($folded);
       $this->assertEquals($unfolded, $output);
   }
}
