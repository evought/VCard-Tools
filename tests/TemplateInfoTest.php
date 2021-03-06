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
 * Generated by PHPUnit_SkeletonGenerator on 2015-01-03 at 04:38:03.
 */
class TemplateInfoTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var TemplateInfo
     */
    protected $info;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->info = new TemplateInfo('test', 'Test description');
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
     * @covers EVought\vCardTools\TemplateInfo::getName
     */
    public function testGetName()
    {
        $this->assertEquals('test', $this->info->getName());
    }

    /**
     * @group default
     * @covers EVought\vCardTools\TemplateInfo::getDescription
     */
    public function testGetDescription()
    {
        $this->assertEquals('Test description', $this->info->getDescription());
    }

    /**
     * @group default
     * @covers EVought\vCardTools\TemplateInfo::setDescription
     */
    public function testSetDescription()
    {
        $this->info->setDescription('foo');
        $this->assertEquals('foo', $this->info->getDescription());
    }

    /**
     * @group default
     * @covers EVought\vCardTools\TemplateInfo::getUsage
     */
    public function testGetUsage()
    {
        $this->assertEmpty($this->info->getUsage());
    }

    /**
     * @group default
     * @covers EVought\vCardTools\TemplateInfo::setUsage
     */
    public function testSetUsage()
    {
        $this->info->setUsage('Instructions');
        $this->assertEquals('Instructions', $this->info->getUsage());
    }

    /**
     * @group default
     * @covers EVought\vCardTools\TemplateInfo::getSee
     */
    public function testGetSee()
    {
        $this->assertEmpty($this->info->getSee());
    }

    /**
     * @group default
     * @covers EVought\vCardTools\TemplateInfo::setSee
     */
    public function testSetSee()
    {
        $this->info->setSee('http://example.com/documentation/');
        $this->assertEquals( 'http://example.com/documentation/',
                $this->info->getSee() );
    }
    
    /**
     * @group default
     * @covers EVought\vCardTools\TemplateInfo::setSee
     * @expectedException \DomainException
     * @expectedExceptionMessage b@dur1
     */
    public function testSetSeeBadURL()
    {
        $this->info->setSee('b@dur1');
    }
    
    /**
     * @group default
     * @covers EVought\vCardTools\TemplateInfo::getInfo
     */
    public function testGetInfo()
    {
        $this->assertEmpty($this->info->getInfo());
    }

    /**
     * @group default
     * @covers EVought\vCardTools\TemplateInfo::setInfo
     */
    public function testSetInfo()
    {
        $this->info->setInfo(['foo' => 'bar', 'baz' => 'bozo']);
        $this->assertEquals( ['foo' => 'bar', 'baz' => 'bozo'],
                             $this->info->getInfo() );
    }

    /**
     * @group default
     * @covers EVought\vCardTools\TemplateInfo::__set
     */
    public function test__set()
    {
        $this->info->copyright = '@whatever 1992';
        $this->assertEquals('@whatever 1992', $this->info->copyright);
    }

    /**
     * @group default
     * @covers EVought\vCardTools\TemplateInfo::__get
     */
    public function test__get()
    {
        $this->assertEmpty($this->info->copyright);
    }

    /**
     * @group default
     * @covers EVought\vCardTools\TemplateInfo::__unset
     */
    public function test__unset()
    {
        $this->info->copyright = '@whatever 1992';
        unset($this->info->copyright);
        $this->assertEmpty($this->info->copyright);
    }

    /**
     * @group default
     * @covers EVought\vCardTools\TemplateInfo::__isset
     */
    public function test__isset()
    {
        $this->assertFalse(isset($this->info->anchovies));
        $this->info->anchovies = 'no';
        $this->assertTrue(isset($this->info->anchovies));
    }

    /**
     * @group default
     */
    public function testFromArrayEmpty()
    {
    	$info = TemplateInfo::fromArray([]);
    	 
    	$this->assertNull($info->getName());
    	$this->assertNull($info->getDescription());
    	$this->assertNull($info->getUsage());
    	$this->assertNull($info->getSee());
    	$this->assertEmpty($info->getInfo());
    }
    
    /**
     * @group default
     */
    public function testFromArray()
    {
    	$data = [ 'name' => 'George',
    	          'description' => 'My cuddly little TemplateInfo',
    	          'usage' => 'I shall hug him and pet him and squeeze him...',
                  'see' => 'https://www.youtube.com/watch?v=ArNz8U7tgU4',
                  'license' => 'artistic'
                ];
    	$info = TemplateInfo::fromArray($data);
    	
    	$this->assertEquals('George', $info->getName());
    	$this->assertEquals($data['description'], $info->getDescription());
    	$this->assertEquals($data['usage'], $info->getUsage());
    	$this->assertEquals($data['see'], $info->getSee());
    	$this->assertEquals(['license'=>'artistic'], $info->getInfo());
    	$this->assertTrue(isset($info->license));
    	$this->assertEquals('artistic', $info->license);
    }
}
