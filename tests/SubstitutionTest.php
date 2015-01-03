<?php
/**
 * SubstitutionTest.php
 * @author Eric Vought <evought@pobox.com>
 * 2015-01-02
 * @copyright Eric Vought 2015, Some rights reserved.
 * @license MIT http://opensource.org/licenses/MIT
 */
namespace EVought\vCardTools;

/**
 * Tests for Substitution
 */
class SubstitutionTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        
    }

    /**
     * @group default
     */
    public function testFromFragment()
    {
        $substitution = Substitution::fromFragment('key');
    	$this->assertInstanceOf('EVought\vCardTools\Substitution', $substitution);
    	 
    	$this->assertTrue($substitution->hasFragment());
    	$this->assertEquals('key', $substitution->getFragment());
    	 
    	$this->assertFalse($substitution->hasQuest());
    	$this->assertFalse($substitution->shouldLookUp());
    	$this->assertFalse($substitution->iterates());
    }

    /**
     * @group default
     */
    public function testFromTextFragment()
    {
        $substitution = Substitution::fromText('key');
    	$this->assertInstanceOf('EVought\vCardTools\Substitution', $substitution);
    	 
    	$this->assertTrue($substitution->hasFragment());
    	$this->assertEquals('key', $substitution->getFragment());
    	 
    	$this->assertFalse($substitution->hasQuest());
    	$this->assertFalse($substitution->shouldLookUp());
    	$this->assertFalse($substitution->iterates());
    }

    /**
     * @group default
     */
    public function testFromTextQuest()
    {
    	$substitution = Substitution::fromText('key, ?adr');
    	$this->assertInstanceOf('EVought\vCardTools\Substitution', $substitution);
    
    	$this->assertTrue($substitution->hasFragment());
    	$this->assertEquals('key', $substitution->getFragment());
    
    	$this->assertTrue($substitution->hasQuest());
    	$this->assertEquals('adr', $substitution->getQuest());
    	 
    	$this->assertFalse($substitution->shouldLookUp());
    	$this->assertFalse($substitution->iterates());
    }
    
    /**
     * @group default
     */
    public function testFromTextLookup()
    {
    	$substitution = Substitution::fromText('!n');
    	$this->assertInstanceOf('EVought\vCardTools\Substitution', $substitution);
    
    	$this->assertFalse($substitution->hasFragment());
    
    	$this->assertFalse($substitution->hasQuest());
    
    	$this->assertTrue( $substitution->shouldLookUp(),
                           print_r($substitution->getLookUp(), true) );
    	$this->assertEquals('n', $substitution->getLookUp()['property']);
    	
    	$this->assertFalse($substitution->lookUpIsStructured());
    
    	$this->assertFalse($substitution->iterates());
    }
    
    /**
     * @group default
     */
    public function testFromTextLookupStructured()
    {
    	$substitution = Substitution::fromText('!n GivenName');
    	$this->assertInstanceOf('EVought\vCardTools\Substitution', $substitution);
    
    	$this->assertFalse($substitution->hasFragment());
    
    	$this->assertFalse($substitution->hasQuest());
    
    	$this->assertTrue( $substitution->shouldLookUp(),
    			print_r($substitution->getLookUp(), true) );
    	$this->assertEquals('n', $substitution->getLookUp()['property']);
    	 
    	$this->assertTrue($substitution->lookUpIsStructured());
    	$this->assertEquals('GivenName', $substitution->getLookUp()['field']);
    
    	$this->assertFalse($substitution->iterates());
    }
    
    /**
     * @group default
     */
    public function testFromTextLookupMagic()
    {
    	$substitution = Substitution::fromText('!_id');
    	$this->assertInstanceOf('EVought\vCardTools\Substitution', $substitution);
    
    	$this->assertFalse($substitution->hasFragment());
    	$this->assertFalse($substitution->hasQuest());
    
    	$this->assertTrue($substitution->shouldLookUp());
    	$this->assertFalse($substitution->lookUpIsStructured());
    	$this->assertTrue($substitution->isMagic());
    	$this->assertEquals('_id', $substitution->getLookUp()['property']);
    
    	$this->assertFalse($substitution->iterates());
    }
    
    /**
     * @group default
     * @expectedException \DomainException
     * @expectedExceptionMessage _abacadabra
     */
    public function testFromTextLookupBadMagic()
    {
    	$substitution = Substitution::fromText('!_abacadabra');
    }
    
    /**
     * @group default
     */
    public function testFromTextIterates()
    {
    	$substitution = Substitution::fromText('key, #n');
    	$this->assertInstanceOf('EVought\vCardTools\Substitution', $substitution);
    
    	$this->assertTrue($substitution->hasFragment());
    	$this->assertEquals('key', $substitution->getFragment());
    
    	$this->assertFalse($substitution->hasQuest());
    
    	$this->assertFalse($substitution->shouldLookUp());
    
    	$this->assertTrue($substitution->iterates());
    	$this->assertEquals('n', $substitution->getIterOver());
    }

}
