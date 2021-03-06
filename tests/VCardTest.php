<?php
/**
 * VCardTest.php
 * @author Eric Vought <evought@pobox.com>
 * 2014-11-16
 * @copyright Eric Vought 2014, Some rights reserved.
 * @license MIT http://opensource.org/licenses/MIT
 */

namespace EVought\vCardTools;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

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
	$lines = explode("\n", VCardParser::unfold4($vcard_string));
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
    public function testGetSpecificationNotStrict()
    {
        $specification = VCard::getSpecification('undefined', false);
        $this->assertNotEmpty($specification);
        $this->assertEquals('undefined', $specification->getName());
    }
    
    /**
     * @depends testGetSpecifications
     * @group default
     */
    public function testGetSpecificationStrictUndefined()
    {
        $specification = VCard::getSpecification('undefined', true);
        $this->assertNull($specification);
    }
    /**
     * @depends testGetSpecification
     * @group default
     */
    public function testBuilder()
    {
        $builder = VCard::builder('adr');
        $this->assertNotEmpty($builder);
        $this->assertInstanceOf('EVought\vCardTools\PropertyBuilder', $builder);
        $this->assertEquals('adr', $builder->getName());
    }
    
    /**
     * @depends testGetSpecification
     * @group default
     * @expectedException \DomainException
     * @expectedExceptionMessage undefined
     */
    public function testBuilderStrictUndefined()
    {
        $builder = VCard::builder('undefined');
    }
    
    /**
     * @depends testGetSpecification
     * @group default
     */
    public function testBuilderNotStrict()
    {
        $builder = VCard::builder('undefined', false);
        $this->assertNotEmpty($builder);
        $this->assertInstanceOf('EVought\vCardTools\PropertyBuilder', $builder);
        $this->assertEquals('undefined', $builder->getName());
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
        // FIXME: #75 handle gender's enumeration
        $property = VCard::builder('gender')->setField('Sex','U')->build();
        $vcard->push($property);
        $this->assertCount(1, $vcard);
	$this->assertNotEmpty($vcard->gender);
        $this->assertCount(1, $vcard);
	$this->assertEquals($property, $vcard->gender);

        $vcard->clear();
	$this->assertCount(0, $vcard);
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
     * @depends testConstructEmptyVCard
     * @expectedException UnexpectedValueException
     */
    public function testPushBadProperty(VCard $vcard)
    {
        $vcard->push('foo');
        return $vcard->clear();
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
    	$properties = [ 'kind'=>'foo', 'prodid'=>'bar',
                        'gender'=>['Sex'=>'M'], 'rev'=>'4'];
        
        foreach ($properties as $property=>$value)
        {
            $this->assertEmpty($vcard->$property);
            $vcard->$property
                    = VCard::builder($property)->setValue($value)->build();
            $this->assertCount(1, $vcard, print_r($vcard, true));
	    $this->assertNotEmpty($vcard->$property);
	    $this->assertEquals($value, $vcard->$property->getValue());
	
	    $vcard->clear();
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
        $vcard->fn = [VCard::builder('fn')->setValue($expected)->build()];
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
     * @expectedException UnexpectedValueException
     */
    public function testAssignBadSingleValue(vCard $vcard)
    {
        $vcard->fn = ["foo"];
	return $vcard;
    }

    /**
     * @group default
     * @depends testNoFN
     * @expectedException UnexpectedValueException
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
     */
    public function testAssignSingleToMultiple(vCard $vcard)
    {
        $adr = VCard::builder('adr')
                ->setValue(['Locality'=>'Cheesequake', 'Region'=>'NJ'])
                ->build();
        $vcard->adr = $adr;
        
        $this->assertNotEmpty($vcard->adr);
        $this->assertCount(1, $vcard->adr);
        $this->assertEquals($adr, $vcard->adr[0]);
	return $vcard;
    }

    /**
     * @group default
     * @depends testPushSpeccedSingle
     * Because FN is a single value element, setting twice should
     * overwrite the first value rather than adding a new value.
     */
    public function testResetRev(vCard $vcard)
    {
	$rev1 = VCard::builder('rev')->setValue('First Rev')->build();
	$rev2 = VCard::builder('rev')->setValue('New Rev')->build();
        $vcard->push($rev1, $rev2);
	$this->assertNotEmpty($vcard->rev);
	$this->assertNotInternalType("array", $vcard->rev);
	$this->assertSame($rev2, $vcard->rev);

	return $vcard->clear();
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
    public function testSetTwoCategories(VCard $vcard)
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

	return $vcard->clear();
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
    public function testPushMultipleAdr(VCard $vcard)
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
     * @depends testPushMultipleAdr
     */
    public function testPushMultipleAdrContainer(VCard $vcard)
    {
        $builder = VCard::builder('adr');
        $adr1 = $builder->setValue(['StreetAddress' => 'Some Street'])->build();
        $adr2 = $builder->build();
	$vcard->push([$adr1, $adr2]);
        
	$this->assertNotEmpty($vcard->adr);
	$this->assertCount(2, $vcard->adr);

	$this->assertContains($adr1, $vcard->adr, \print_r($vcard->adr, true));
        $this->assertContains($adr2, $vcard->adr, \print_r($vcard->adr, true));

	return $vcard->clear();
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
        
        return $vcard->clear();
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
        
        return $vcard->clear();
    }

    /**
     * @depends testConstructEmptyVCard
     * @group default
     */
    public function testGetUndefinedProperties(VCard $vcard)
    {
        VCard::builder('tel')->setValue('555-1212')->pushTo($vcard);
        VCard::builder('undefined', false)->setValue('foo')->pushTo($vcard);
        $iterator = $vcard->getUndefinedProperties();
        $iterator->rewind();
        $this->assertTrue($iterator->valid());
        $prop = $iterator->current();
        $this->assertEquals('undefined', $prop->getName());
        return $vcard->clear();
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
        
        return $vcard->clear();
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
        
        return $vcard->clear();
    }

    /**
     * When neither N nor ORG are set, can't come up with a useful value.
     * @depends testConstructEmptyVCard
     * @group default
     */
    public function testSetFNAppropriatelyNoHint(VCard $vcard)
    {
        $vcard->setFNAppropriately();
        $this->assertEmpty($vcard->fn);
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
        $vcard->push($n, $kind);

        $vcard->setFNAppropriately();
        $this->assertNotEmpty($vcard->fn);
        $this->assertEquals((string) $n, $vcard->fn[0]->getValue());

        return $vcard->clear();
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
        $vcard->push($org, $kind);
        
        $vcard->setFNAppropriately();
        $this->assertNotEmpty($vcard->fn);
        $this->assertEquals((string) $org, $vcard->fn[0]->getValue());
        
        return $vcard->clear();
    }
    
    public function foldProvider()
    {
        $string10 = '0123456789';
        $string20 = $string10 . $string10;
        $string70 = $string20 . $string20 . $string20 . $string10;
        $string80 = $string70 . $string10;
        $string150 = $string80 . $string70;
        $string160 = $string150 . $string10;

        return [
            'string20' =>  [$string20,  $string20],
            'string70' =>  [$string70,  $string70],
            'string80' =>  [$string80,  $string70 . '01234' . "\n" . ' 56789'],
            'string150' => [$string150, $string70 . '01234' . "\n"
                                        . ' 56789' . $string70 ],
            'string160' => [$string160, $string70 . '01234' . "\n"
                                        . ' 56789' . $string70 . "\n"
                                        . ' ' . $string10 ]
        ];
    }
    
    /**
     * @group default
     * @dataProvider foldProvider
     */
    public function testFoldLine($unfolded, $folded)
    {
        $this->assertEquals($folded, VCard::foldLine($unfolded));
    }
    
    /**
     * @group default
     * @depends testFoldLine
     */
    public function testFoldOutput()
    {
        $inputStr = '';
        $foldedStr = '';
        
        foreach ($this->foldProvider() as $input)
        {
            $inputStr .= $input[0] . "\n";
            $foldedStr .= $input[1] . "\n";
        }
        
        $this->assertEquals($foldedStr, VCard::foldOutput($inputStr));
    }

    /**
     * @depends testConstructEmptyVCard
     * @group default
     * FN appears because RFC6350 may not be omitted (and is not
     * supposed to be empty).
     */
    public function testOutputEmptyVCard(VCard $vcard)
    {
	$output = $vcard->output();
	$this->assertNotEmpty($output);

	$lines = $this->checkAndRemoveSkeleton($output);
        
        $expected = 	[
                            'UID:' . VCard::escape($vcard->getUID())
                        ];
        
	$this->assertEquals($expected, $lines, $output);
        return $vcard->clear();
    }

    /**
     * @group default
     * @depends testOutputEmptyVCard
     * @depends testPushSpeccedSingle
     * @dataProvider stringEscapeProvider
     */
    public function testOutputFN($unescaped, $escaped, VCard $vcard)
    {
	$vcard->push(VCard::builder('fn')->setValue($unescaped)->build());
        
	$output = $vcard->output();
	$this->assertNotEmpty($output);

        $expected = 	[
                            'FN:' . $escaped,
                            'UID:' . VCard::escape($vcard->getUID())
                        ];
	$lines = $this->checkAndRemoveSkeleton($output);

	$this->assertEquals($expected, $lines);
        return $vcard->clear();
    }

    /**
     * @group default
     * @depends testOutputEmptyVCard
     * @depends testSetSingleCategory
     * @dataProvider stringEscapeProvider
     */
    public function testOutputOneCategory($unescaped, $escaped, VCard $vcard)
    {
        VCard::builder('fn')->setValue('foo')->pushTo($vcard);
	VCard::builder('categories')->setValue($unescaped)->pushTo($vcard);

	$output = $vcard->output();

        $expected = [
                        'FN:foo',
                        'CATEGORIES:' . $escaped,
                        'UID:' . VCard::escape($vcard->getUID())
                    ];
	sort($expected);

	$lines = $this->checkAndRemoveSkeleton($output);

	$this->assertEquals($expected, $lines);
        return $vcard->clear();
    }

    /**
     * We assume it will output multiple categories one per line
     * rather than separated by commas as also allowed in the spec.
     * @group default
     * @depends testOutputOneCategory
     * @depends testSetTwoCategories
     */
    public function testOutputTwoCategories()
    {
        $vcard = new VCard();
        VCard::builder('categories')
                ->setValue('sporting goods')->pushTo($vcard)
                ->setValue('telephone sanitizing')->pushTo($vcard);
        VCard::builder('fn')->setValue('foo')->pushTo($vcard);

	$output = $vcard->output();
        
	$expected = [
			'FN:foo',
			'CATEGORIES:' . 'sporting goods',
			'CATEGORIES:' . 'telephone sanitizing',
                        'UID:' . VCard::escape($vcard->getUID())
	 ];
	sort($expected);

	$lines = $this->checkAndRemoveSkeleton($output);

	$this->assertEquals($expected, $lines);
        return $vcard->clear();
    }

    /**
     * @group default
     * @depends testOutputEmptyVCard
     * @depends testSetSingleURL
     * @dataProvider stringEscapeProvider
     */
    public function testOutputOneURL($unescaped, $escaped, $vcard)
    {
	VCard::builder('url')->setValue($unescaped)->pushTo($vcard);
        VCard::builder('fn')->setValue('foo')->pushTo($vcard);

	$output = $vcard->output();
        
	$expected = [
                        'FN:foo',
                        'URL'
            . ':' . $escaped,
                        'UID:' . VCard::escape($vcard->getUID())
                    ];
	sort($expected);

	$lines = $this->checkAndRemoveSkeleton($output);

	$this->assertEquals($expected, $lines);
        
        return $vcard->clear();
    }


    /**
     * @group default
     * @depends testOutputOneURL
     * @depends testSetSingleURL
     */
    public function testOutputTwoURLs()
    {
        $vcard = new VCard();
        VCard::builder('url')
                ->setValue('something')->pushTo($vcard)
                ->setValue('somethingElse')->pushTo($vcard);
        VCard::builder('fn')->setValue('foo')->pushTo($vcard);
        
	$output = $vcard->output();
        
        $expected = [
                        'FN:foo',
			'URL:something',
			'URL:somethingElse',
                        'UID:' . VCard::escape($vcard->getUID())
		];
	sort($expected);

	$lines = $this->checkAndRemoveSkeleton($output);
	$this->assertEquals($expected, $lines, $output);
        
        return $vcard->clear();
    }

    /**
     * @group default
     * @depends testOutputEmptyVCard
     * @depends testSetAdr
     * RFC 6350 Sec 6.3.1
     */
    public function testOutputOneAdr(VCard $vcard)
    {
	$address = [
			'StreetAddress' => '123 Sesame Street',
			'Locality' => 'Hooville',
			'Region' => 'Bear-ever',
			'PostalCode' => '31337',
			'Country' => 'Elbonia'
		    ];

        VCard::builder('adr')->setValue($address)->pushTo($vcard);
        VCard::builder('fn')->setValue('foo')->pushTo($vcard);

	$output = $vcard->output();
        
	$expected = [ 'FN:foo',
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
        
        return $vcard->clear();
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
    	'N:'.$inputs['n']['FamilyName'].';'.$inputs['n']['GivenName'].';;;',
        'ORG:'.$inputs['org']['Name'].';;',
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
    	    'N:' . $inputs['n']['FamilyName'] . ';' . $inputs['n']['GivenName']
    	         . ';' . $inputs['n']['AdditionalNames'] . ';;',
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
    	'ORG:'.$inputs['org']['Name'].';'.$inputs['org']['Unit1'].';'.$inputs['org']['Unit2'],
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
    public function testListGroupsEmpty(VCard $vcard)
    {
        $this->assertEmpty($vcard->listGroups());
    }
    
    /**
     * @group default
     * @depends testConstructEmptyVCard
     */
    public function testListGroups(VCard $vcard)
    {
        $vcard->builder('fn')->setValue('foo')->pushTo($vcard);
        
        $vcard->builder('tel')->setValue('555-1212')->setGroup('a')
                              ->pushTo($vcard);
        $vcard->builder('tel')->setValue('555-1213')
                              ->pushTo($vcard);
        $vcard->builder('note')->setValue('A note.')->setGroup('b')
                              ->pushTo($vcard);
        $vcard->builder('note')->setValue('Another note.')->setGroup('a')
                              ->pushTo($vcard);
        $this->assertEquals(['a', 'b'], $vcard->listGroups());
        
        return $vcard->clear();
    }
    
    /**
     * @group default
     * @depends testConstructEmptyVCard
     */
    public function testgetGroupMembersEmpty(VCard $vcard)
    {
        $iter = $vcard->getGroupMembers("properties-anonymous");
        $this->assertFalse($iter->valid());
    }
    
    /**
     * @group default
     * @depends testConstructEmptyVCard
     */
    public function testGetGroupMembers(VCard $vcard)
    {
        $vcard->builder('fn')->setValue('foo')->pushTo($vcard);
        
        $vcard->builder('tel')->setValue('555-1212')->setGroup('a')
                              ->pushTo($vcard);
        $vcard->builder('tel')->setValue('555-1213')
                              ->pushTo($vcard);
        $vcard->builder('note')->setValue('A note.')->setGroup('b')
                              ->pushTo($vcard);
        $vcard->builder('note')->setValue('Another note.')->setGroup('a')
                              ->pushTo($vcard);
        $properties = [];
        foreach ($vcard->getGroupMembers('a') as $property)
        {
            $properties[] = $property;
        }
        $this->assertCount(2, $properties, print_r($properties, true));
    }
}
