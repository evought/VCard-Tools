<?php
/**
 * A start on unit tests for the vCard class to avoid regression as changes
 * are made.
 * @author Eric Vought <evought@pobox.com>
 * 2014-11-16
 * @copyright Eric Vought 2014, Some rights reserved.
 * @license MIT http://opensource.org/licenses/MIT
 */

namespace EVought\vCardTools;

use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

class VCardTest extends \PHPUnit_Framework_TestCase
{
    // some components of expected values
    static $vcard_begin = "BEGIN:VCARD";
    static $vcard_end = "END:VCARD";
    static $vcard_version = "VERSION:4.0";
    static $vcard_empty_fn = "FN:";

    /**
     * Check the format of the VCARD skeleton (begin, end, version),
     * remove those lines, and return the remaining lines (sorted).
     * Utility for use in __toString() tests.
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
        
        sort($lines);

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
    	            'n_GivenName' => 'Darth',
    	            'n_AdditionalNames' => 'Darth',
    	            'n_FamilyName'  => 'Binks',
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
    	'n_GivenName' => 'Raith',
    	'n_FamilyName'  => 'Seinar',
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
		'n_GivenName'       => 'John',
		'n_FamilyName'        => 'Doe',
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
    	
    	$dDBinks = new VCard();
        $dDBinks->push(
                VCard::builder('n')
                    ->setField('GivenName', $inputs['n_GivenName'])
                    ->setField('FamilyName', $inputs['n_FamilyName'])
                    ->setField('AdditionalNames', $inputs['n_AdditionalNames'])
                    ->build()
            );
        $dDBinks->push(
                VCard::builder('org')->setField('Name', $inputs['org'])->build()
            );
        $dDBinks->push(VCard::builder('fn')->setValue($inputs['fn'])->build());
        $dDBinks->push(VCard::builder('kind')->setValue($inputs['kind'])
                ->build());

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
    	$seinarAPL->push(
                VCard::builder('org')
                    ->setField('Name', $inputs['org_Name'])
                    ->setField('Unit1', $inputs['org_Unit1'])
                    ->setField('Unit2', $inputs['org_Unit2'])
                    ->build()
            );
        $seinarAPL->push(
                VCard::builder('fn')->setValue($inputs['fn'])->build() );
        $seinarAPL->push(
                VCard::builder('logo')->setValue($inputs['logo'])->build()
            );
        $seinarAPL->push(
                VCard::builder('categories')
                    ->setValue($inputs['category1'])
                    ->build()
            );
        $seinarAPL->push(
                VCard::builder('categories')
                    ->setValue($inputs['category2'])
                    ->build()
            );
        $seinarAPL->push(
                VCard::builder('categories')
                    ->setValue($inputs['category3'])
                    ->build()
            );
        $seinarAPL->push(
                VCard::builder('kind')->setValue($inputs['kind'])->build() );

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
    	
    	$raithSeinar->push(
                VCard::builder('n')
                    ->setField('GivenName', $inputs['n_GivenName'])
                    ->setField('FamilyName', $inputs['n_FamilyName'])
                    ->build()
            );
        $raithSeinar->push(
                VCard::builder('org')->setField('Name', $inputs['org'])->build()
            );
        $raithSeinar->push(
                VCard::builder('title')->setValue($inputs['title'])->build()
            );
        $raithSeinar->push(
                VCard::builder('fn')->setValue($inputs['fn'])->build()
            );
        $raithSeinar->push(
                VCard::builder('categories')->setValue($inputs['category1'])
                    ->build()
            );
        $raithSeinar->push(
                VCard::builder('categories')->setValue($inputs['category2'])
                    ->build()
            );
        $raithSeinar->push(
                VCard::builder('kind')->setValue($inputs['kind'])->build()
            );
    	return $raithSeinar;
    }
    
    /**
     * @group default
     */
    public function testConstructEmptyVCard()
    {
	$vcard = new VCard();
	$this->assertInstanceOf(__NAMESPACE__ . '\VCard', $vcard);
        $this->assertCount(0, $vcard);
	return $vcard;
    }

    /**
     * @group default
     */
    public function testGetSpecifications()
    {
        foreach (VCard::getSpecifications() as $name=>$specification)
        {
            $this->assertEquals($name, $specification->getName());
            $builder = $specification->getBuilder();
            $this->assertInstanceOf( 'EVought\vCardTools\PropertyBuilder',
                                        $builder, $name );
        }
    }
    
    /**
     * @depends testGetSpecifications
     * @group default
     */
    public function testGetSpecification()
    {
        $specification = VCard::getSpecification('adr');
        $this->assertNotEmpty($specification);
        $this->assertEquals('adr', $specification->getName());
    }
    
    /**
     * @depends testGetSpecifications
     * @group default
     */
    public function testIsSpecified()
    {
        $this->assertTrue(VCard::isSpecified('fn'));
    }
    
    /**
     * @group default
     * @depends testConstructEmptyVCard
     */
    public function testKeyIsStructuredElement()
    {
	$this->assertTrue(VCard::keyIsStructuredElement('org'));
	$this->assertFalse(VCard::keyIsStructuredElement('fn'));
    }
    
    /**
     * @group default
     * @depends testConstructEmptyVCard
     */
    public function testKeyIsSingleValueElement()
    {
        $this->assertTrue(VCard::keyIsSingleValueElement('fn'));
	$this->assertFalse(VCard::keyIsSingleValueElement('url'));
    }

    /**
     * @group default
     * @depends testConstructEmptyVCard
     */
    public function testKeyIsTypeAble()
    {
        $this->assertTrue(VCard::keyIsTypeAble('adr'));
	$this->assertTrue(VCard::keyIsTypeAble('org'));
	$this->assertFalse(VCard::keyIsTypeAble('n'));
    }

    /**
     * @group default
     * @depends testConstructEmptyVCard
     */
    public function testKeyAllowedTypes($vcard)
    {
        $this->assertContains('work', $vcard::keyAllowedTypes('tel'));
    }

    /**
     * @group default
     * @depends testConstructEmptyVCard
     */
    public function testKeyIsMultipleValueElement($vcard)
    {
        $this->assertTrue(VCard::keyIsMultipleValueElement('categories'));
	$this->assertFalse(VCard::keyIsMultipleValueElement('n'));
    }

    /**
     * @group default
     * @depends testConstructEmptyVCard
     */
    public function testKeyIsFileElement()
    {
        $this->assertTrue(VCard::keyIsFileElement('photo'));
	$this->assertFalse(VCard::keyIsFileElement('email'));
    }

    /**
     * @group default
     * @depends testConstructEmptyVCard
     */
    public function testKeyAllowedFields($vcard)
    {
        $this->assertContains('GivenName', $vcard::keyAllowedFields('n'));
    }
    
    /**
     * @group default
     * @depends testConstructEmptyVCard
     */
    public function testNoFN(VCard $vcard)
    {
	$this->assertEmpty($vcard->fn);
	return $vcard;
    }

    /**
     * Push a property required to have zero or one value. Get should return
     * the bare property, not an array.
     * @group default
     * @depends testNoFN
     */
    public function testPushSpeccedSingle(VCard $vcard)
    {
	$expected = 'Test FN';
	$vcard->push(VCard::builder('fn')->setValue($expected)->build());
        $this->assertCount(1, $vcard);
	$this->assertNotEmpty($vcard->fn);
	$this->assertEquals($expected, $vcard->fn->getValue());

	unset($vcard->fn);
	$this->assertEmpty($vcard->fn);
	return $vcard;
    }

    /**
     * @group default
     * @depends testNoFN
     */
    public function testPushSpeccedMultiple(VCard $vcard)
    {
	$expected = '555-1212';
	$vcard->push(VCard::builder('tel')->setValue($expected)->build());
        $this->assertCount(1, $vcard, print_r($vcard, true));
	$this->assertNotEmpty($vcard->tel);
        $this->assertInternalType('array', $vcard->tel);
        $this->assertCount(1, $vcard->tel);
	$this->assertEquals($expected, $vcard->tel[0]->getValue());

	unset($vcard->tel);
	$this->assertEmpty($vcard->tel);
	return $vcard;
    }
    
    /**
     * @group default
     * @depends testConstructEmptyVCard
     */
    public function testPushUIDMagic(VCard $vcard)
    {
        $uid = VCard::builder('uid')->setValue('Fake UID')->build();
        $vcard->push($uid);
        $this->assertEquals('Fake UID', $vcard->getUID());
        $vcard->clearUID();
    }

    /**
     * @group default
     * @depends testPushSpeccedMultiple
     * @param \EVought\vCardTools\VCard $vcard
     */
    public function testClear(VCard $vcard)
    {
        $this->assertCount(0, $vcard);
        $vcard->push(
            VCard::builder('tel')->setValue('$expected','555-1212')->build() );
        $vcard->checkSetUID();
        $this->assertCount(1, $vcard);
        
        $vcard->clear();
        $this->assertCount(0, $vcard);
        $this->assertEmpty($vcard->getUID());
    }
    
    /**
     * @group default
     * @depends testPushSpeccedSingle
     */
    public function testIsSet(vCard $vcard)
    {
        $this->assertFalse(isset($vcard->fn), print_r($vcard, true));
	$vcard->push(VCard::builder('fn')->setValue('foo')->build());
	$this->assertTrue(isset($vcard->fn));
        unset($vcard->fn);
        $this->assertFalse(isset($vcard->fn));
    }

    /**
     * @group default
     * @depends testNoFN
     */
    public function testAssignSingleValueElement(vCard $vcard)
    {
    	$properties = [ 'fn', 'kind', 'bday', 'anniversary',
    	                'prodid', 'rev'];
        $expected = 'foo';
        
        foreach ($properties as $property)
        {
            $this->assertEmpty($vcard->$property);
            $vcard->push(
                VCard::builder($property)->setValue($expected)->build()) ;
            $this->assertCount(1, $vcard, print_r($vcard, true));
	    $this->assertNotEmpty($vcard->$property);
	    $this->assertEquals($expected, $vcard->$property->getValue());
	
	    unset($vcard->$property);
	    $this->assertEmpty($vcard->$property);
        }
	return $vcard;
    }

    /**
     * @group default
     * @depends testNoFN
     */
    public function testAssignNoValue(vCard $vcard)
    {
        $expected = 'foo';
        $this->assertEmpty($vcard->fn);
        $vcard->fn = VCard::builder('fn')->setValue($expected)->build();
        $this->assertCount(1, $vcard);
	$this->assertNotEmpty($vcard->fn);

	$vcard->fn = null;
        $this->assertCount(0, $vcard);
	$this->assertEmpty($vcard->fn);
	return $vcard;
    }

    /**
     * @group default
     * @depends testNoFN
     */
    public function testAssignElement(vCard $vcard)
    {
        $expected = [VCard::builder('url')->setValue('foo')->build()];
        $this->assertEmpty($vcard->url);
        $vcard->url = $expected;
	$this->assertNotEmpty($vcard->url);
	$this->assertEquals($expected, $vcard->url);

	unset($vcard->url);
	$this->assertEmpty($vcard->url);
	return $vcard;
    }
    
    /**
     * @group default
     * @depends testAssignElement
     */
    public function testAssignMultiple(vCard $vcard)
    {
        /* @var $builder TypedStructuredPropertyBuilder */
        $builder = VCard::builder('adr');
        $adr1 = $builder->setValue(['StreetAddress' => 'foo'])->build();
        $adr2 = $builder->setValue(['StreetAddress' => 'bar'])->build();
        
        $this->assertEmpty($vcard->adr);
        $vcard->adr = [$adr1, $adr2];
	$this->assertNotEmpty($vcard->adr);
        $this->assertCount(2, $vcard->adr);
	$this->assertContains($adr1, $vcard->adr);
	$this->assertContains($adr2, $vcard->adr);
        
	unset($vcard->adr);
	$this->assertEmpty($vcard->adr);
	return $vcard;
    }
    
    /**
     * @group default
     * @depends testNoFN
     * @expectedException DomainException
     */
    public function testAssignBadSingleValue(vCard $vcard)
    {
        $vcard->fn = ["foo"];
	return $vcard;
    }

    /**
     * @group default
     * @depends testNoFN
     * @expectedException DomainException
     */
    public function testAssignBadProperty(vCard $vcard)
    {
        $vcard->url = "foo";
	return $vcard;
    }

    /**
     * @group default
     * @depends testNoFN
     * @expectedException DomainException
     */
    public function testAssignWrongProperty(vCard $vcard)
    {
        $vcard->adr = [VCard::builder('fn')->setValue('foo')->build()];
	return $vcard;
    }

    /**
     * @group default
     * @depends testNoFN
     * @expectedException DomainException
     */
    public function testAssignSingleToMultiple(vCard $vcard)
    {
        $vcard->adr = VCard::builder('adr')
                ->setValue(['Locality'=>'Cheesequake'])->build();
	return $vcard;
    }

    /**
     * @group default
     * @depends testPushSpeccedSingle
     * Because FN is a single value element, setting twice should
     * overwrite the first value rather than adding a new value.
     */
    public function testResetFN(vCard $vcard)
    {
	$fn1 = VCard::builder('fn')->setValue('First FN')->build();
	$fn2 = VCard::builder('fn')->setValue('New FN')->build();

	$vcard->push($fn1, $fn2);
	$this->assertNotEmpty($vcard->fn);
	$this->assertNotInternalType("array", $vcard->fn);
	$this->assertSame($fn2, $vcard->fn);

	unset($vcard->fn);
	return $vcard;
    }
    
    /**
     * @group default
     * @depends testConstructEmptyVCard
     */
    public function testSetN(vCard $vcard)
    {
        $n = VCard::builder('n')
            ->setValue([ 'GivenName'=>'John',
                         'AdditionalNames'=>'Jacob Jingleheimer',
                         'FamilyName'=>'Smith' ])
            ->build();
        $vcard->push($n);
        
        $this->assertNotEmpty($vcard->n);
        $this->assertInternalType('array', $vcard->n);
        $this->assertEquals([$n], $vcard->n);
        
        unset($vcard->n);
        return $vcard;
    }
    
    /**
     * @group default
     * @depends testConstructEmptyVCard
     */
    public function testSetOrg(vCard $vcard)
    {
        $org = VCard::builder('org')
            ->setValue(['Name'=>'Church of the Militant Agnostic'])
            ->build();
        $vcard->push($org);
        
        $this->assertNotEmpty($vcard->org);
        $this->assertInternalType('array', $vcard->org);
        $this->assertEquals([$org], $vcard->org);
        
        unset($vcard->org);
        return $vcard;
    }

    /**
     * @group default
     * @depends testSetOrg
     */
    public function testSetTwoOrgs(vCard $vcard)
    {
        $org1 = VCard::builder('org')
            ->setValue(['Name'=>'Church of the Militant Agnostic'])
            ->build();
        $org2 = VCard::builder('org')
            ->setValue(['Name'=>'State University of North Carolina'])
            ->build();

        $vcard->push($org1, $org2);
        
        $this->assertNotEmpty($vcard->org);
        $this->assertInternalType('array', $vcard->org);
        $this->assertEquals([$org1, $org2], $vcard->org);
        
        unset($vcard->org);
        return $vcard;
    }

    
    /**
     * @group default
     * @depends testConstructEmptyVCard
     */
    public function testEmptyCategories(vCard $vcard)
    {
	$this->assertEmpty($vcard->categories);

	return $vcard;
    }

    /**
     * @group default
     * @depends testEmptyCategories
     */
    public function testSetSingleCategory(vCard $vcard)
    {
	$category_expected = VCard::builder('categories')
                ->setValue('computers')->build();
	$vcard->push($category_expected);
	$this->assertNotEmpty($vcard->categories);
	$this->assertInternalType('array', $vcard->categories);
	$this->assertCount(1, $vcard->categories);
	$this->assertContains($category_expected, $vcard->categories);

	unset($vcard->categories);
	$this->assertEmpty($vcard->categories);
	return $vcard;
    }

    /**
     * @group default
     * @depends testSetSingleCategory
     */
    public function testSetTwoCategories(vCard $vcard)
    {
        $builder = VCard::builder('categories');
	$category1 = $builder->setValue('computers')->build();
	$category2 = $builder->setValue('electronics')->build();

	$vcard->push($category1);
	$vcard->push($category2);
	$this->assertNotEmpty($vcard->categories);
	$this->assertInternalType('array', $vcard->categories);
	$this->assertCount( 2, $vcard->categories,
		print_r($vcard->categories, true) );
	$this->assertContains($category1, $vcard->categories);
	$this->assertContains($category2, $vcard->categories);

	unset($vcard->categories);

	return $vcard;
    }

    /**
     * @group default
     * @depends testConstructEmptyVCard
     */
    public function testNoURL(vCard $vcard)
    {
	$this->assertEmpty($vcard->url);

	return $vcard;
    }

    /**
     * @group default
     * @depends testNoURL
     */
    public function testSetSingleURL(vCard $vcard)
    {
	$url_expected = VCard::builder('url')->setValue('http://golf.com')
                ->build();
	$vcard->push($url_expected);
	$this->assertNotEmpty($vcard->url);
	$this->assertInternalType('array', $vcard->url);
	$this->assertCount(1, $vcard->url);
	$this->assertContains($url_expected, $vcard->url);

	unset($vcard->url);
	$this->assertEmpty($vcard->url);
	return $vcard;
    }
    
    /**
     * @group default
     * @depends testSetSingleURL
     */
    public function testSetTwoURLs(vCard $vcard)
    {
        $builder = VCard::builder('url');
	$url1 = $builder->setValue('http://golf.com')->build();
	$url2 = $builder->setValue('http://espn.com')->build();
        
        $this->assertCount(0, $vcard);

	$vcard->push($url1, $url2);

        $this->assertCount(2, $vcard);
	$this->assertNotEmpty($vcard->url);
	$this->assertInternalType('array', $vcard->url);
	$this->assertCount(2, $vcard->url);
	$this->assertContains($url1, $vcard->url);
	$this->assertContains($url2, $vcard->url);

	unset($vcard->url);
	$this->assertEmpty($vcard->url);
	return $vcard;
    }
    
    /**
     * @group default
     * @depends testConstructEmptyVCard
     */
    public function testSetSingleGeo(vCard $vcard)
    {
        $this->assertEmpty($vcard->geo); // precondition
	$geo = VCard::builder('geo')->setValue('geo:48.2010,16.3695,183')
                ->build();
	$vcard->push($geo);
	$this->assertNotEmpty($vcard->geo);
	$this->assertInternalType('array', $vcard->geo);
	$this->assertCount(1, $vcard->geo);
	$this->assertEquals([$geo], $vcard->geo);

	unset($vcard->geo);
	$this->assertEmpty($vcard->geo);
	return $vcard;
    }
    
    /**
     * @group default
     * @depends testSetSingleGeo
     */
    public function testSetTwoGeos(vCard $vcard)
    {
        $this->assertEmpty($vcard->geo); // precondition
        $builder = VCard::builder('geo');
	$geo1 = $builder->setValue('geo:48.2010,16.3695,183')->build();
        $geo2 = $builder->setValue('geo:48.198634,16.371648;crs=wgs84;u=40')
                ->build();
	$vcard->push($geo1, $geo2);
	$this->assertNotEmpty($vcard->geo);
	$this->assertInternalType("array", $vcard->geo);
	$this->assertCount(2, $vcard->geo);
	$this->assertContains($geo1, $vcard->geo);
        $this->assertContains($geo2, $vcard->geo);

	unset($vcard->geo);
	$this->assertEmpty($vcard->geo);
	return $vcard;
    }
    
    /**
     * @group default
     * @depends testConstructEmptyVCard
     */
    public function testNoAdr(vCard $vcard)
    {
	$this->assertEmpty($vcard->adr);

	return $vcard;
    }

    /**
     * @group default
     * @depends testNoAdr
     */
    public function testSetAdr(vCard $vcard)
    {
	$adr = VCard::builder('adr')
                ->setValue(['StreetAddress' => 'Some Street'])->build();
	$vcard->push($adr);
	$this->assertNotEmpty($vcard->adr);
	$this->assertInternalType('array', $vcard->adr);
	$this->assertCount(1, $vcard->adr);

	$this->assertContains($adr, $vcard->adr, print_r($vcard->adr, true));

	unset($vcard->adr);
	$this->assertEmpty($vcard->adr);
	return $vcard;
    }
    
    /**
     * @group default
     * @depends testNoAdr
     */
    public function testPushMultipleAdr(vCard $vcard)
    {
        $builder = VCard::builder('adr');
        $adr1 = $builder->setValue(['StreetAddress' => 'Some Street'])->build();
        $adr2 = $builder->build();
	$vcard->push($adr1, $adr2);
        
	$this->assertNotEmpty($vcard->adr);
	$this->assertInternalType("array", $vcard->adr);
	$this->assertCount(2, $vcard->adr);

	$this->assertContains($adr1, $vcard->adr, \print_r($vcard->adr, true));
        $this->assertContains($adr2, $vcard->adr, \print_r($vcard->adr, true));

	unset($vcard->adr);
	$this->assertEmpty($vcard->adr);
	return $vcard;
    }

    /**
     * @group default
     * @depends testConstructEmptyVCard
     */
    public function testNoKind(vCard $vcard)
    {
	$this->assertEmpty($vcard->kind);

	return $vcard;
    }

    /**
     * VCard parameter KEY ('key') cannot be accessed via the __call(..)
     * mechanism because it conflicts with the Iterator interface's key()
     * method! __set(..) and get(..) work fine.
     * @group default
     * @depends testConstructEmptyVCard
     */
    public function testSetKey(vCard $vcard)
    {
        $this->assertEmpty($vcard->key);
	$key = VCard::builder('key')
                ->setValue('http://www.example.com/keys/jdoe.cer')->build();
	$vcard->push($key);
	$this->assertNotEmpty($vcard->key, print_r($vcard, true));
        $this->assertCount(1, $vcard->key);
	$this->assertContains($key, $vcard->key);

	unset($vcard->key);
	$this->assertEmpty($vcard->key);
	return $vcard;
    }

    /**
     * @depends testConstructEmptyVCard
     * @group default
     */
    public function testSetKind(vCard $vcard)
    {
	$expected = VCard::builder('kind')->setValue('Individual')->build();
	$vcard->push($expected);
	$this->assertNotEmpty($vcard->kind);
	$this->assertEquals($expected, $vcard->kind);

	unset($vcard->kind);
	$this->assertEmpty($vcard->kind);
	return $vcard;
    }
    
    /**
     * @depends testConstructEmptyVCard
     * @group default
     */
    public function testIteratorEmpty(vCard $vcard)
    {
        $this->assertCount(0, $vcard);
        $vcard->rewind();
        $this->assertFalse($vcard->valid());
        
        return $vcard;
    }
    
    /**
     * @depends testIteratorEmpty
     * @depends testPushSpeccedSingle
     * @group default
     */
    public function testIteratorOneSingle(vCard $vcard)
    {
        $this->assertCount(0, $vcard);
        $fn = VCard::builder('fn')->setValue('foo')->build();
        $vcard->push($fn);
        $this->assertCount(1, $vcard);
        $vcard->rewind();
        $this->assertTrue($vcard->valid());
        $this->assertEquals($fn, $vcard->current());
        $vcard->next();
        $this->assertFalse($vcard->valid());
        
        unset($vcard->fn);
        return $vcard;
    }
    
    /**
     * @depends testIteratorOneSingle
     * @depends testPushSpeccedMultiple
     * @group default
     */
    public function testIteratorSingleAndMultiple(vCard $vcard)
    {
        $this->assertCount(0, $vcard);
        $fn = VCard::builder('fn')->setValue('foo')->build();
        $adr1 = VCard::builder('adr')->setField('Locality', 'Albequerque')
                ->build();
        $adr2 = VCard::builder('adr')->setField('Locality', 'Austin')
                ->build();
        $org  = VCard::builder('org')->setField('Name', 'FooCorp')->build();
        $properties = [$fn, $adr1, $adr2, $org];
        
        $vcard->push($fn, $adr1, $adr2, $org);
        
        $this->assertCount(4, $vcard);
        
        foreach ($vcard as $property)
        {
            // check off properties as we find them
            $this->assertContains($property, $properties);
            $properties = \array_values(\array_diff($properties, [$property]));
        }
        
        unset($vcard->fn);
        unset($vcard->adr);
        unset($vcard->org);
        return $vcard;
    }
    
    /**
     * @depends testConstructEmptyVCard
     * @group default
     */
    public function testUID(VCard $vcard)
    {
        $vcard->setUID('Globally Unique');
        $this->assertEquals('Globally Unique', $vcard->getUID());
        
        $vcard2 = new VCard();
        $vcard2->setUID();
        $this->assertTrue(Uuid::isValid($vcard2->getUID()));
        
        $vcard3 = new VCard();
        $vcard3->checkSetUID();
        $this->assertTrue(Uuid::isValid($vcard3->getUID()));
        
        $vcard->checkSetUID();
        $this->assertEquals('Globally Unique', $vcard->getUID());
        
        $this->assertNotEquals($vcard2->getUID(), $vcard3->getUID());
        
        $vcard->clearUID();
        return $vcard;
    }
    
    /**
     * @depends testUID
     * @group default
     */
    public function testGetUIDMagic(vCard $vcard)
    {
        // UID, when presented as a property, *always has a value* so that the
        // VCard will always have a primary key when output.
        $this->assertNull($vcard->getUID());
        
        /* @var $property1 Property */
        $property1 = $vcard->uid;
        $this->assertNotEmpty($property1);
        $this->assertInstanceOf(__NAMESPACE__ . '\Property', $property1);
        $this->assertEquals('uid', $property1->getName());
        
        $vcard->setUID('Some UID');
        
        /* @var $property2 Property */
        $property2 = $vcard->uid;
        $this->assertNotEmpty($property2);
        $this->assertInstanceOf(__NAMESPACE__ . '\Property', $property2);
        $this->assertEquals('uid', $property2->getName());
        $this->assertEquals('Some UID', $property2->getValue());
        
        $vcard->clearUID();
        return $vcard;
    }

    /**
     * When neither N nor ORG are set, can't come up with a useful value.
     * @depends testConstructEmptyVCard
     * @group default
     */
    public function testSetFNAppropriatelyNoHint(VCard $vcard)
    {
        $vcard->setFNAppropriately();
        $this->assertNotEmpty($vcard->fn);
        $this->assertEmpty($vcard->fn->getValue());
        
        unset($vcard->fn);
        return $vcard;
    }
    
    /**
     * @depends testSetN
     * @depends testSetFNAppropriatelyNoHint
     * @group default
     */
    public function testSetFNAppropriatelyIndividual(VCard $vcard)
    {
        $n = VCard::builder('n')
            ->setValue([ 'GivenName'=>'John',
                         'AdditionalNames'=>'Jacob Jingleheimer',
                         'FamilyName'=>'Smith' ])
            ->build();
        $kind = VCard::builder('kind')->setValue('individual')->build();
        
        $vcard->push($n)->push($kind);
        $vcard->setFNAppropriately();
        $this->assertNotEmpty($vcard->fn);
        $this->assertEquals((string) $n, $vcard->fn->getValue());
        
        unset($vcard->fn);
        unset($vcard->n);
        unset($vcard->kind);
        return $vcard;
    }
    
    /**
     * @depends testSetOrg
     * @depends testSetFNAppropriatelyNoHint
     * @group default
     */
    public function testSetFNAppropriatelyOrganization(VCard $vcard)
    {
        $org = VCard::builder('org')
            ->setValue(['Name'=>'Society For The Appreciation of Beefsteak'])
            ->build();
        $kind = VCard::builder('kind')->setValue('organization')->build();
        
        $vcard->push($org)->push($kind);
        $vcard->setFNAppropriately();
        $this->assertNotEmpty($vcard->fn);
        $this->assertEquals((string) $org, $vcard->fn->getValue());
        
        unset($vcard->fn);
        unset($vcard->org);
        unset($vcard->kind);
        return $vcard;
    }

    /**
     * @depends testConstructEmptyVCard
     * @group default
     * FN appears because RFC6350 may not be omitted (and is not
     * supposed to be empty).
     */
    public function testOutputEmptyVCard()
    {
	$vcard = new vCard();

	$output = '';
	$output .= $vcard;
	$this->assertNotEmpty($output);

	$lines = $this->checkAndRemoveSkeleton($output);
        
        $expected = 	[
                            self::$vcard_empty_fn,
                            'UID:' . VCard::escape($vcard->getUID())
                        ];
        
	$this->assertEquals($expected, $lines, $output);
    }

    /**
     * @group default
     * @depends testOutputEmptyVCard
     * @depends testPushSpeccedSingle
     * @dataProvider stringEscapeProvider
     */
    public function testOutputFN($unescaped, $escaped)
    {
	$vcard = new vCard();
	$vcard->push(VCard::builder('fn')->setValue($unescaped)->build());
        
	$output = $vcard->output();
	$this->assertNotEmpty($output);

        $expected = 	[
                            'FN:' . $escaped,
                            'UID:' . VCard::escape($vcard->getUID())
                        ];
	$lines = $this->checkAndRemoveSkeleton($output);

	$this->assertEquals($expected, $lines);
    }

    /**
     * @group default
     * @depends testOutputEmptyVCard
     * @depends testSetSingleCategory
     * @dataProvider stringEscapeProvider
     */
    public function testOutputOneCategory($unescaped, $escaped)
    {
	$vcard = new vCard();
	$vcard->push(
                VCard::builder('categories')->setValue($unescaped)->build() );

	$output = $vcard->output();

        $expected = [
                        self::$vcard_empty_fn,
                        'CATEGORIES:' . $escaped,
                        'UID:' . VCard::escape($vcard->getUID())
                    ];
	sort($expected);

	$lines = $this->checkAndRemoveSkeleton($output);

	$this->assertEquals($expected, $lines);
    }

    /**
     * @group default
     * @depends testOutputOneCategory
     * @depends testSetTwoCategories
     * We assume it will output multiple categories one per line
     * rather than separated by commas as also allowed in the spec.
     */
    public function testOutputTwoCategories()
    {
        $builder = VCard::builder('categories');
	$category1 = $builder->setValue('sporting goods')->build();
	$category2 = $builder->setValue('telephone sanitizing')->build();
        
	$vcard = new vCard();
	$vcard->push($category1)->push($category2);

	$output = $vcard->output();
        
	$expected = [
			self::$vcard_empty_fn,
			'CATEGORIES:' . 'sporting goods',
			'CATEGORIES:' . 'telephone sanitizing',
                        'UID:' . VCard::escape($vcard->getUID())
	 ];
	sort($expected);

	$lines = $this->checkAndRemoveSkeleton($output);

	$this->assertEquals($expected, $lines);
    }

    /**
     * @group default
     * @depends testOutputEmptyVCard
     * @depends testSetSingleURL
     * @dataProvider stringEscapeProvider
     */
    public function testOutputOneURL($unescaped, $escaped)
    {
	$vcard = new vCard();
	$vcard->push(VCard::builder('url')->setValue($unescaped)->build());

	$output = $vcard->output();
        
	$expected = [
                        self::$vcard_empty_fn,
                        'URL:' . $escaped,
                        'UID:' . VCard::escape($vcard->getUID())
                    ];
	sort($expected);

	$lines = $this->checkAndRemoveSkeleton($output);

	$this->assertEquals($expected, $lines);
    }


    /**
     * @group default
     * @depends testOutputOneURL
     * @depends testSetSingleURL
     */
    public function testOutputTwoURLs()
    {
        $builder = VCard::builder('url');
	$url1 = $builder->setValue('something')->build();
	$url2 = $builder->setValue('somethingElse')->build();

	$vcard = new vCard();
	$vcard->push($url1)->push($url2);

	$output = $vcard->output();
        
        $expected = [
                        self::$vcard_empty_fn,
			'URL:something',
			'URL:somethingElse',
                        'UID:' . VCard::escape($vcard->getUID())
		];
	sort($expected);

	$lines = $this->checkAndRemoveSkeleton($output);

	$this->assertEquals($expected, $lines, $output);
    }

    /**
     * @group default
     * @depends testOutputEmptyVCard
     * @depends testSetAdr
     * RFC 6350 Sec 6.3.1
     */
    public function testOutputOneAdr()
    {
	$address = [
			'StreetAddress' => '123 Sesame Street',
			'Locality' => 'Hooville',
			'Region' => 'Bear-ever',
			'PostalCode' => '31337',
			'Country' => 'Elbonia'
		    ];

	$vcard = new vCard();
        $vcard->push(VCard::builder('adr')->setValue($address)->build());

	$output = $vcard->output();
        
	$expected = [ self::$vcard_empty_fn,
			"ADR:" . ';;'  // POBox & ExtendedAddress
			. $address['StreetAddress'] . ';'
			. $address['Locality'] . ';'
			. $address['Region'] . ';'
			. $address['PostalCode'] . ';'
			. $address['Country'],
                        'UID:' . VCard::escape($vcard->getUID())
			];
	sort($expected);


	$lines = $this->checkAndRemoveSkeleton($output);

	$this->assertEquals($expected, $lines);
    }
    
    /**
     * @group default
     * @depends testOutputEmptyVCard
     */
    public function testOutputWithOneN()
    {
    	$name = [
    	          'GivenName'       => 'Luna',
    	          'AdditionalNames' => 'Charlotte',
    	          'FamilyName'        => 'Begtrup',
    	          'Prefixes'        => 'Ms.',
    	          'Suffixes'        => 'PhD'
    		];
    	$fn = 'Ms. Luna C. Begtrup, PhD';
        $fnEsc = VCard::escape($fn);
    	    	
    	$vcard = new vCard();
    	$vcard->push(VCard::builder('fn')->setValue($fn)->build())
              ->push(VCard::builder('n')->setValue($name)->build());
    	
    	$output = $vcard->output();

    	$expected = [
    	              'N:' . $name['FamilyName'] . ';' . $name['GivenName']
    	                   . ';' . $name['AdditionalNames'] . ';'
    	                   . $name['Prefixes'] . ';' . $name['Suffixes'],
    	              'FN:' . $fnEsc,
                      'UID:' . VCard::escape($vcard->getUID())
    	            ];

    	sort($expected);
        
    	$lines = $this->checkAndRemoveSkeleton($output);
    	
    	$this->assertEquals($expected, $lines);
    }
    
    /**
     * @group default
     * @depends testOutputEmptyVCard
     */
    public function testOutputRaithSeinar()
    {
    	$inputs = $this->getRaithSeinarInputs();
 	
    	$vcard = $this->getRaithSeinar();
    	
    	$output = $vcard->output();
    	$lines = $this->checkAndRemoveSkeleton($output);

        $expected = [
    	'N:'.$inputs['n_FamilyName'].';'.$inputs['n_GivenName'].';;;',
        'ORG:'.$inputs['org'].';;',
        'TITLE:'.$inputs['title'],
        'FN:'.$inputs['fn'],
        'CATEGORIES:'.$inputs['category1'],
        'CATEGORIES:'.$inputs['category2'],
        'KIND:'.$inputs['kind'],
        'UID:' . VCard::escape($vcard->getUID())
    	];
    	sort($expected);
       	
	$this->assertEquals($expected, $lines);
    }

    /**
     * @group default
     * @depends testOutputEmptyVCard
     */
    public function testOutputDDBinks()
    {
    	$inputs = $this->getDDBinksInputs();
    	
    	$vcard = $this->getDDBinks();
    	
    	$output = $vcard->output();
    	$lines = $this->checkAndRemoveSkeleton($output);

        $expected = [
    	    'N:' . $inputs['n_FamilyName'] . ';' . $inputs['n_GivenName']
    	         . ';' . $inputs['n_AdditionalNames'] . ';;',
            'ORG:'.$inputs['org'].';;',
            'FN:'.$inputs['fn'],
            'KIND:'.$inputs['kind'],
            'UID:' . VCard::escape($vcard->getUID())
    	];
    	sort($expected);
	$this->assertEquals($expected, $lines);
    }
    
    /**
     * @group default
     * @depends testOutputEmptyVCard
     */
    public function testOutputSeinarAPL()
    {
    	$inputs = $this->getSeinarAPLInputs();
    	 
    	$vcard = $this->getSeinarAPL();
    	 
    	$output = $vcard->output();
    	$lines = $this->checkAndRemoveSkeleton($output);

    	$expected = [
    	'ORG:'.$inputs['org_Name'].';'.$inputs['org_Unit1'].';'.$inputs['org_Unit2'],
    	'FN:'.$inputs['fn'],
    	'LOGO:'.addcslashes($inputs['logo'], "\\\n,:;"),
    	'CATEGORIES:'.$inputs['category1'],
    	'CATEGORIES:'.$inputs['category2'],
    	'CATEGORIES:'.$inputs['category3'],
    	'KIND:'.$inputs['kind'],
        'UID:' . VCard::escape($vcard->getUID())
    	];
    	sort($expected);
        
    	$this->assertEquals($expected, $lines);
    }
    
    /**
     * @group default
     * @depends testConstructEmptyVCard
     */
    public function testImportEmptyVCard()
    {
	$input =	self::$vcard_begin . "\r\n"
			. self::$vcard_version . "\r\n"
			. self::$vcard_end . "\r\n";

	$vcard = new vCard(false, $input);
    }
    
    /**
     * @group default
     * @expectedException \DomainException
     */
    public function testImportBadVCardGarbage()
    {
        new vCard(false, 'Garbage');
    }
    
    /**
     * @group default
     * @expectedException \DomainException
     */
    public function testImportBadVCardNoVersion()
    {
        $input =    self::$vcard_begin . "\r\n"
                    . self::$vcard_end . "\r\n";
        new vCard(false, $input);
    }

    /**
     * @group default
     * @expectedException \DomainException
     */
    public function testImportBadVCardNoEnd()
    {
        $input =    self::$vcard_begin . "\r\n"
                    . self::$vcard_version . "\r\n";
        new vCard(false, $input);
    }

    /**
     * @group default
     * @depends testImportEmptyVCard
     * @expectedException \DomainException
     */
    public function testImportVCardEmptyFN()
    {
	$input =	self::$vcard_begin . "\r\n"
			. self::$vcard_version . "\r\n"
			. self::$vcard_empty_fn . "\r\n"
			. self::$vcard_end . "\r\n";

	$vcard = new vCard(false, $input);
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

	$vcard = new vCard(false, $input);
	$this->assertEquals($unescaped, $vcard->fn->getValue());
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
                        . "UID:123\r\n"
			. self::$vcard_end . "\r\n";

	$vcard = new vCard(false, $input);
	$this->assertEquals('123', $vcard->getUID());
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

	$vcard = new vCard(false, $input);
	$this->assertEquals($unescaped, $vcard->fn->getValue());
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

	$vcard = new VCard(false, $input);
	$this->assertEquals($unescaped, $vcard->fn->getValue());
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
                            . $jDoeInputs['adr_StreetAddress']
                            . ';' . $jDoeInputs['adr_Locality']
                            . ';' . $jDoeInputs['adr_Region']
                            . ';' . $jDoeInputs['adr_Postal']
                            . ';' . $jDoeInputs['adr_Country'] . "\r\n"
			. self::$vcard_end . "\r\n";

        $expectedAdr = VCard::builder('adr')
            ->setValue(
                [
                    'StreetAddress'=>$jDoeInputs['adr_StreetAddress'],
                    'Locality'=>$jDoeInputs['adr_Locality'],
                    'Region'=>$jDoeInputs['adr_Region'],
                    'PostalCode'=>$jDoeInputs['adr_Postal'],
                    'Country'=>$jDoeInputs['adr_Country']
                ])
            ->build();

	$vcard = new VCard(false, $input);
	$this->assertNotEmpty($vcard->adr);
        $this->assertCount(1, $vcard->adr);
        $this->assertEquals($expectedAdr, $vcard->adr[0]);
    }
    
    /**
     * @group default
     * @depends testImportVCardFN
     */
    public function testImportVCardAdrWType()
    {
        $jDoeInputs = $this->getJohnDoeInputs();
        
	$input = self::$vcard_begin . "\r\n"
		. self::$vcard_version . "\r\n"
		. 'ADR;TYPE=HOME:;;42 Plantation St.;Baytown;LA;30314;United States of America' . "\r\n"
		. self::$vcard_end . "\r\n";

        $expectedAdr = VCard::builder('adr')
            ->setValue(
                [
                    'StreetAddress'=>$jDoeInputs['adr_StreetAddress'],
                    'Locality'=>$jDoeInputs['adr_Locality'],
                    'Region'=>$jDoeInputs['adr_Region'],
                    'PostalCode'=>$jDoeInputs['adr_Postal'],
                    'Country'=>$jDoeInputs['adr_Country']
                ])
            ->addType(strtolower($jDoeInputs['adr_type']))
            ->build();

	$vcard = new vCard(false, $input);
	$this->assertNotEmpty($vcard->adr);
        $this->assertCount(1, $vcard->adr);
        $this->assertEquals($expectedAdr, $vcard->adr[0]);
    }
    
    /**
     * @group default
     * @group vcard21
     * @depends testImportVCardFN
     */
    public function testImportVCardAdrWBareType21()
    {
        $jDoeInputs = $this->getJohnDoeInputs();
        
	$input = self::$vcard_begin . "\r\n"
		. "VERSION:2.1" . "\r\n"
		. 'ADR;HOME:;;42 Plantation St.;Baytown;LA;30314;United States of America' . "\r\n"
		. self::$vcard_end . "\r\n";

        $expectedAdr = VCard::builder('adr')
            ->setValue(
                [
                    'StreetAddress'=>$jDoeInputs['adr_StreetAddress'],
                    'Locality'=>$jDoeInputs['adr_Locality'],
                    'Region'=>$jDoeInputs['adr_Region'],
                    'PostalCode'=>$jDoeInputs['adr_Postal'],
                    'Country'=>$jDoeInputs['adr_Country']
                ])
            ->addType(strtolower($jDoeInputs['adr_type']))
            ->build();

	$vcard = new vCard(false, $input);
	$this->assertNotEmpty($vcard->adr);
        $this->assertCount(1, $vcard->adr);
        $this->assertEquals($expectedAdr, $vcard->adr[0]);
    }

    /**
     * @group default
     * @depends testImportVCardFN
     * @expectedException \DomainException
     */
    public function testImportVCardAdrWBareType40()
    {
        $jDoeInputs = $this->getJohnDoeInputs();
        
	$input = self::$vcard_begin . "\r\n"
		. self::$vcard_version . "\r\n"
		. 'ADR;HOME:;;42 Plantation St.;Baytown;LA;30314;United States of America' . "\r\n"
		. self::$vcard_end . "\r\n";
	$vcard = new vCard(false, $input);
    }


    /**
     * @group default
     * @depends testImportVCardFN
     */
    public function testImportVCardNoCategories()
    {
	$input =	self::$vcard_begin . "\r\n"
			. self::$vcard_version . "\r\n"
			. self::$vcard_end . "\r\n";

	$vcard = new vCard(false, $input);
	$this->assertEmpty($vcard->categories);
    }

    /**
     * @group default
     * @depends testImportVCardFN
     * @expectedException \DomainException
     */
    public function testImportVCardEmptyCategories()
    {
	$input =	self::$vcard_begin . "\r\n"
			. self::$vcard_version . "\r\n"
			. "CATEGORIES:\r\n"
			. self::$vcard_end . "\r\n";

	$vcard = new vCard(false, $input);
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
			. "CATEGORIES:" . $escaped . "\r\n"
			. self::$vcard_end . "\r\n";

	$vcard = new vCard(false, $input);
        
	$this->assertNotEmpty($vcard->categories);
	$this->assertInternalType("array", $vcard->categories);
	$this->assertCount(1, $vcard->categories);
	$this->assertEquals( $unescaped, $vcard->categories[0]->getValue(),
				print_r($vcard->categories, true) );
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

	$vcard = new vCard(false, $input);
        
        $builder = VCard::builder('categories');
        $catProp1 = $builder->setValue($category1)->build();
        $catProp2 = $builder->setValue($category2)->build();

	$this->assertNotEmpty($vcard->categories);
	$this->assertInternalType("array", $vcard->categories);
	$this->assertCount(2, $vcard->categories,
		print_r($vcard->categories, true) );
	$this->assertEquals([$catProp1, $catProp2], $vcard->categories);
    }

    /**
     * @group default
     * @depends testImportVCardFN
     */
    public function testImportVCardNoURL()
    {
	$input =	self::$vcard_begin . "\r\n"
			. self::$vcard_version . "\r\n"
			. self::$vcard_end . "\r\n";

	$vcard = new vCard(false, $input);
	$this->assertEmpty($vcard->url);
    }

    /**
     * @group default
     * @depends testImportVCardFN
     * @expectedException \DomainException
     */
    public function testImportVCardEmptyURL()
    {
	$input =	self::$vcard_begin . "\r\n"
			. self::$vcard_version . "\r\n"
			. "URL:\r\n"
			. self::$vcard_end . "\r\n";

	$vcard = new vCard(false, $input);
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

	$vcard = new vCard(false, $input);

	$this->assertNotEmpty($vcard->url);
	$this->assertInternalType("array", $vcard->url);
	$this->assertCount(1, $vcard->url);
	$this->assertEquals( $unescaped, $vcard->url[0]->getValue(),
				print_r($vcard->url, true) );
   }

    /**
     * @group default
     * @depends testImportVCardOneURL
     */
    public function testImportVCardOneURLUnescape()
    {
	$url = "http\://somewhere";
	$unescaped = "http://somewhere";

	$input =	self::$vcard_begin . "\r\n"
			. self::$vcard_version . "\r\n"
			. "URL:" . $url . "\r\n"
			. self::$vcard_end . "\r\n";

	$vcard = new vCard(false, $input);

	$this->assertNotEmpty($vcard->url);
	$this->assertInternalType("array", $vcard->url);
	$this->assertCount(1, $vcard->url);
	$this->assertEquals( $unescaped, $vcard->url[0]->getValue(),
				print_r($vcard->url, true) );
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

	$vcard = new vCard(false, $input);
        $builder = VCard::builder('url');
        $urlProp1 = $builder->setValue($url1)->build();
        $urlProp2 = $builder->setValue($url2)->build();

	$this->assertNotEmpty($vcard->url);
	$this->assertInternalType("array", $vcard->url);
	$this->assertCount(2, $vcard->url);
	$this->assertEquals([$urlProp1, $urlProp2], $vcard->url);
   }
   
   /**
    * @group default
    * @depends testImportVCardFN
    * @depends testOutputDDBinks
    */
   public function testImportVCardDDBinks()
   {
       $vcard = $this->getDDBinks();
       $vcard_string = $vcard->output();
       
       $vcard2 = new vCard(null, $vcard_string);
       $this->assertEquals($vcard, $vcard2);
   }
   
   /**
    * @group default
    * @depends testImportVCardFN
    * @depends testOutputRaithSeinar
    */
   public function testImportVCardRaithSeinar()
   {
   	$vcard = $this->getRaithSeinar();
   	$vcard_string = $vcard->output();
   	 
   	$vcard2 = new vCard(null, $vcard_string);
   	unset($vcard2->version);
   	$this->assertEquals($vcard, $vcard2);
   }

   /**
    * @group default
    * @depends testImportVCardFN
    * @depends testOutputSeinarAPL
    */
   public function testImportVCardSeinarAPL()
   {
   	$vcard = $this->getSeinarAPL();
   	$vcard_string = $vcard->output();
   	 
   	$vcard2 = new vCard(null, $vcard_string);
   	unset($vcard2->version);
   	$this->assertEquals($vcard, $vcard2);
   }

   /**
    * @group default
    * @depends testImportVCardDDBinks
    * @depends testOutputDDBinks
    */
   public function testImportVCardDDBinksFromFile()
   {
   	$path = __DIR__ . '/vcards/DDBinks.vcf';
   	$vcard = $this->getDDBinks();
   	 
   	$vcard2 = new vCard($path);
   	unset($vcard2->version);
   	 
   	$this->assertEquals($vcard, $vcard2);
   }   
   
   /**
    * @group default
    * @depends testImportVCardRaithSeinar
    * @depends testOutputRaithSeinar
    */
   public function testImportVCardRaithSeinarFromFile()
   {
   	$path = __DIR__ . '/vcards/RaithSeinar.vcf';
   	$vcard = $this->getRaithSeinar();
   	 
   	$vcard2 = new vCard($path);
   	unset($vcard2->version);
   
   	$this->assertEquals($vcard, $vcard2);
   }
    
   
   /**
    * @group default
    * @depends testImportVCardSeinarAPL
    * @depends testOutputSeinarAPL
    */
   public function testImportVCardSeinarAPLFromFile()
   {
   	$path = __DIR__ . '/vcards/SeinarAPL.vcf';
   	$vcard = $this->getSeinarAPL();
   	 
   	$vcard2 = new vCard($path);
   	unset($vcard2->version);
   	
   	$this->assertEquals($vcard, $vcard2);
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
       $output = vCard::unfold4($folded);
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
       $output = vCard::unfold21($folded);
       $this->assertEquals($unfolded, $output);
   }
}
