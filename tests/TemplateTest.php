<?php
/**
 * TemplateTest.php
 * @author Eric Vought <evought@pobox.com>
 * 2014-12-08
 * @copyright Eric Vought 2014, Some rights reserved.
 * @license MIT http://opensource.org/licenses/MIT
 */
namespace EVought\vCardTools;

define('TEST_DIR', __DIR__);

class VCardTemplatesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Sample vcard loaded from disk. Use getter.
     */
    private $seinarAPL = null;
    
    /**
     * Sample vcard loaded from disk. Use getter.
     */
    private $raithSeinar = null;
    
    /**
     * Sample vcard loaded from disk. Use getter.
     */
    private $ddBinks = null;
    
    /**
     * Some cards for testing.
     * @return an individual VCard.
     */
    public function getRaithSeinar()
    {
    	if (null === $this->raithSeinar)
    	{
    	    $path = __DIR__ . '/vcards/RaithSeinar.vcf';
            
            $parser = new VCardParser();
            $vcards = $parser->importFromFile($path);
            
            $this->assertCount(1, $vcards);
	    $this->raithSeinar = $vcards[0];
    	}
	return $this->raithSeinar;
    }
	
    /**
     * Some cards for testing.
     * @return an organization VCard.
     */
    public function getSeinarAPL()
    {
    	if (null === $this->seinarAPL)
    	{
	    $path = __DIR__ . '/vcards/SeinarAPL.vcf';
            
            $parser = new VCardParser();
            $vcards = $parser->importFromFile($path);
            
            $this->assertCount(1, $vcards);
            $this->seinarAPL = $vcards[0];
    	}
	return $this->seinarAPL;
    }
	
    /**
     * Some cards for testing.
     * @return an individual VCard.
     */
    public function getDDBinks()
    {
    	if (null === $this->ddBinks)
    	{
	    $path = __DIR__ . '/vcards/DDBinks.vcf';
            $parser = new VCardParser();
            $vcards = $parser->importFromFile($path);
            
            $this->assertCount(1, $vcards);
            $this->ddBinks = $vcards[0];
    	}
	return $this->ddBinks;
    }
    
    /**
     * @group default
     */
    public function testTemplateInfoFromArrayEmpty()
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
    public function testTemplateInfoFromArray()
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

    /**
     * @group default
     * @depends testTemplateInfoFromArray
     */
    public function testGetDefault()
    {
    	$template = Template::getDefaultTemplate();
    	$this->assertInstanceOf('EVought\vCardTools\Template', $template);
    	    	
    	$this->assertNotNull($template->getFragments());
    	$this->assertNull($template->getFallback());
    	
    	$registeredTemplate = Template::getTemplate('default');
    	$this->assertNotNull($registeredTemplate);
    	$this->assertSame( $template, $registeredTemplate,
                           print_r($registeredTemplate, true) );
    }
    
    /**
     * @group default
     */
    public function testConstructWFragments()
    {
    	$fragments = [];
    	$template = new Template($fragments);
    	
    	$this->assertSame($fragments, $template->getFragments());
    	$this->assertNull($template->getFallback());
    }
    
    /**
     * @group default
     */
    public function testConstructWInfo()
    {	
    	$fragments = [];
    	$template = new Template($fragments, null, new TemplateInfo('George'));

    	$this->assertSame($fragments, $template->getFragments());
    	$this->assertEquals('George', $template->getName());
    	$this->assertNull($template->getFallback());
    	 
    }
    
    /**
     * @group default
     */
    public function testGetTemplateNoneExists()
    {
    	$template = Template::getTemplate('testGetTemplateNoneExists');
    	$this->assertNull($template, print_r($template, true));
    }
    
    /**
     * @group default
     * @depends testConstructWFragments
     */
    public function testRegisterTemplate()
    {
    	$template = new Template([]);
    	Template::registerTemplate('testRegisterTemplate', $template);
    	$this->assertSame( $template,
    			   Template::getTemplate('testRegisterTemplate') );
    }
    
    /**
     * @group default
     * @testConstructWFragments
     */
    public function testFromININoName()
    {
    	$template
    	    = Template::fromINI(__DIR__ . '/templates/testFromININoName.ini');
    	$this->assertNotNull($template);
    	
    	$fragments = $template->getFragments();
    	$this->assertNotNull($fragments);
    	
    	$this->assertEquals(['vcard'=>'content'], $fragments);
    	
    	$this->assertNull($template->getFallback());
    }
    
    /**
     * @group default
     * @depends testFromININoName
     * @depends testTemplateInfoFromArray
     */
    public function testFromINIWithName()
    {
    	$expected = ['vcard' => 'content'];
    	
    	$template
    	    = Template::fromINI(__DIR__ . '/templates/testFromINIWithName.ini');
    	$this->assertNotNull($template);
    	
    	$this->assertEquals('testFromINIWithName', $template->getName());
    	 
    	$fragments = $template->getFragments();
    	$this->assertNotNull($fragments);
    	 
    	$this->assertEquals($expected, $fragments);
    	 
    	$this->assertNull($template->getFallback());
    	
    	$registeredTemplate = Template::getTemplate('testFromINIWithName');
    	$this->assertSame($template, $registeredTemplate);
    }
    
    /**
     * @group default
     * @depends testFromINIWithName
     */
    public function testFromINIWithNameExplicitFallback()
    {
    	$expected = ['vcard' => 'content'];
    	 
    	$template = Template::fromINI( __DIR__ .
    	    '/templates/testFromINIWithNameExplicitFallback.ini',
            Template::getDefaultTemplate() );
    	$this->assertNotNull($template);
    	
    	$this->assertEquals( 'testFromINIWithNameExplicitFallback',
                             $template->getName() );
    
    	$fragments = $template->getFragments();
    	$this->assertNotNull($fragments);
    
    	$this->assertEquals($expected, $fragments);
    
    	$this->assertSame( Template::getDefaultTemplate(),
    			   $template->getFallback() );
    	 
    	$registeredTemplate
    	    = Template::getTemplate('testFromINIWithNameExplicitFallback');
    	$this->assertSame($template, $registeredTemplate);
    }
    
    /**
     * @group default
     * @depends testFromINIWithName
     */
    public function testFromINIWithFallback()
    {
    	// precondition
    	$this->assertNotNull(Template::getTemplate('testFromINIWithName'));
    	
    	$expected = ['vcard' => 'content'];
    
    	$template = Template::fromINI( __DIR__ .
    			'/templates/testFromINIWithFallback.ini' );
    	$this->assertNotNull($template);
    	
    	$this->assertEquals('testFromINIWithFallback', $template->getName());
    
    	$fragments = $template->getFragments();
    	$this->assertNotNull($fragments);
    
    	$this->assertEquals($expected, $fragments);
    
    	$this->assertNotNull($template->getFallback());
    	$this->assertSame( Template::getTemplate('testFromINIWithName'),
    			   $template->getFallback() );
    
    	$registeredTemplate
    	= Template::getTemplate('testFromINIWithFallback');
    	$this->assertSame($template, $registeredTemplate);
    }
    
    /**
     * @group default
     * @testFromINIWithName
     */
    public function testFromINILoadFallback()
    {
    	// precondition
    	$this->assertNull(
    		Template::getTemplate('testFromINILoadFallbackFallback') );
    	 
    	$expected = ['vcard' => 'content'];
    
    	$template = Template::fromINI( __DIR__ .
    			'/templates/testFromINILoadFallback.ini' );
    	$this->assertNotNull($template);
    	
    	$this->assertEquals('testFromINILoadFallback', $template->getName());
    
    	$fragments = $template->getFragments();
    	$this->assertNotNull($fragments);
    
    	$this->assertEquals($expected, $fragments);
    
    	$this->assertNotNull($template->getFallback());
    	$this->assertSame( Template::getTemplate('testFromINILoadFallbackFallback'),
    			$template->getFallback() );
    
    	$registeredTemplate
    	= Template::getTemplate('testFromINILoadFallback');
    	$this->assertSame($template, $registeredTemplate);
    }
    
    /**
     * @group default
     */
    public function testTrivialTemplate()
    {
    	$template = new Template([]);
    	$vcard = new VCard();

    	$output = $template->output($vcard);
    	
    	$this->assertEmpty($output, print_r($output, true));
    }
    
    /**
     * @group default
     * @depends testTrivialTemplate
     */
    public function testLiteralTemplate()
    {
    	$fragments = ['vcard' => 'LiteralOutput'];
    	$template = new Template($fragments);
    	$vcard = new vCard();
    
    	$output = $template->output($vcard);
    	 
    	$this->assertEquals($fragments['vcard'], $output);
    }
    
    /**
     * @group default
     * @depends testLiteralTemplate
     */
    public function testOneRecursion()
    {
    	$fragments = [
    	               'vcard'     => '{{content}}',
    	               'content'   => 'OneRecursion'
                     ];
    	$template = new Template($fragments);
    	$vcard = new vCard();
    
    	$output = $template->output($vcard);
    
    	$this->assertEquals( $fragments['content'], $output);
    }
    
    /**
     * @group default
     * @depends testLiteralTemplate
     */
    public function testOneRecursionNoMatch()
    {
    	$fragments = ['vcard'     => '{{content}}'];
    	$template = new Template($fragments);
    	$vcard = new vCard();
    
    	$output = $template->output($vcard);    
    	$this->assertEmpty($output, print_r($output, true));
    }
    
    /**
     * @group default
     * @depends testOneRecursion
     */
    public function testSeveralLayers()
    {
    	$fragments = [
    	               'vcard'     => '{{layer1}}',
    	               'layer1'    => '{{layer2}}',
    	               'layer2'    => '{{layer3}}',
    	               'layer3'    => 'Layer3Output'
    		     ];
    	$template = new Template($fragments);
    	$vcard = new vCard();
    
    	$output = $template->output($vcard);
    
    	$this->assertEquals($fragments['layer3'], $output);
    }

    /**
     * @group default
     * @depends testOneRecursion
     */
    public function testInsideSubsitution()
    {
    	$fragments = [
    	'vcard'     => 'Content {{content}} here.',
    	'content'   => 'goes'
    			];
    	$template = new Template($fragments);
    	$vcard = new vCard();
    
    	$output = $template->output($vcard);
    
    	$this->assertEquals('Content goes here.', $output);
    }

    /**
     * @group default
     * @depends testOneRecursion
     */
    public function testInsideSubsitutionEmpty()
    {
    	$fragments = ['vcard'     => 'Empty [{{content}}]'];
    	$template = new Template($fragments);
    	$vcard = new vCard();
    
    	$output = $template->output($vcard);
    
    	$this->assertEquals( 'Empty []', $output);
    }
    
    /**
     * @group default
     * @depends testInsideSubsitution
     */
    public function testSubstitutionTree()
    {
    	$fragments = [
    	               'vcard'     => '{{A}}, {{One}}',
    	               'A'         => 'a {{B}} {{C}}',
    	               'B'         => 'b',
    	               'C'         => 'c',
    	               'One'       => '1 {{Two}} {{Three}}',
    	               'Two'       => '2',
    	               'Three'     => '3'
    		     ];
    	$template = new Template($fragments);
    	$vcard = new vCard();
    	
    	$output = $template->output($vcard);
    	
    	$this->assertEquals( 'a b c, 1 2 3', $output);
    }
    
    /**
     * @group default
     * @depends testLiteralTemplate
     */
    public function testFallback()
    {
    	$template_fallback = new Template(["vcard" => "Fallback"]);
    	$template = new Template([], $template_fallback);
    	
    	$vcard = new vCard();
    	
    	$output = $template->output($vcard);
        $this->assertEquals('Fallback', $output);
    } // testFallBack()
    
    /**
     * @group default
     * @depends testLiteralTemplate
     */
    public function testFNLookupEmpty()
    {
    	$template = new Template(['vcard' => '{{!fn}}']);
    	 
    	$vcard = new vCard();
    	 
    	$output = $template->output($vcard);
    	$this->assertEmpty($output);
    }
    
    /**
     * @group default
     * @depends testLiteralTemplate
     */
    public function testFNLookup()
    {
    	$template = new Template(['vcard' => '{{!fn}}']);
    	
    	$vcard = $this->getRaithSeinar();
    	$this->assertNotEmpty($vcard->fn); // precondition
    	
    	$output = $template->output($vcard);
    	$this->assertEquals($vcard->fn[0], $output);
    }
    
    /**
     * @group default
     * @depends testLiteralTemplate
     */
    public function testNFamilyNameLookup()
    {
    	$template = new Template(['vcard' => '{{!n FamilyName}}']);
    	 
    	$vcard = $this->getRaithSeinar();
    	//preconditions
    	$this->assertNotEmpty($vcard->n, print_r($vcard, true));
    	$this->assertNotEmpty($vcard->n[0]->getField('FamilyName'), print_r($vcard, true));
    	 
    	$output = $template->output($vcard);
    	$this->assertEquals($vcard->n[0]->getField('FamilyName'), $output);
    }
    
    /**
     * @group default
     * @depends testFNLookup
     */
    public function testQuestFNNo()
    {
    	$fragments = ['vcard' => '{{output,?url}}', 'output' => 'Output'];
    	$template = new Template($fragments);
    	
    	$vcard = new vCard();
    	$this->assertEmpty($vcard->url); // precondition
    	
    	$output = $template->output($vcard);
    	$this->assertEmpty($output);
    }
    
    /**
     * @group default
     * @depends testFNLookup
     */
    public function testQuestFNYes()
    {
    	$fragments = ['vcard' => '{{output,?fn}}', 'output' => 'Output'];
    	$template = new Template($fragments);
    	 
    	$vcard = $this->getRaithSeinar();
    	$this->assertNotEmpty($vcard->fn); // precondition
    	 
    	$output = $template->output($vcard);
    	$this->assertEquals('Output', $output);
    }
    
    /**
     * @group default
     * @depends testFNLookup
     */
    public function testCategoriesIterEmpty()
    {
    	$template = new Template(['vcard' => '{{#categories}}']);
    	
    	$vcard = new vCard();
    	$this->assertEmpty($vcard->categories); // precondition
    	
    	$output = $template->output($vcard);
    	$this->assertEmpty($output);
    }
    
    /**
     * @group default
     * @depends testFNLookup
     */
    public function testCategoriesIter()
    {
    	$fragments = [
    			'vcard'    => '{{each,#categories}}',
    			'each'     => '{{!categories}}|'
		     ];
    	$template = new Template($fragments);
    	 
    	$vcard = $this->getSeinarApl();
    	$this->assertNotEmpty($vcard->categories); // precondition
    	$expected = $vcard->categories;
    	sort($expected);
    	 
    	$output = $template->output($vcard);
    	$this->assertNotEmpty($output);
    	
    	$output_array = explode('|', $output);
    	
    	$tail = array_pop($output_array); // trailing separator
    	$this->assertEmpty($tail, $output);
    	
    	// values can come back in any order and may have whitespace
    	$output_array = array_map('trim', $output_array);
    	sort($output_array);
    	$this->assertEquals($expected, $output_array);
    }
    
    /**
     * Border case. Using iteration on a single-value property.
     * Principle of least surprise: should substitute once.
     * @group default
     * @depends testCategoriesIter
     */
    public function testFNITer()
    {
	$fragments = [
    			'vcard'    => '{{each,#fn}}',
    			'each'     => '{{!fn}}'
		     ];
    	$template = new Template($fragments);
    	 
    	$vcard = $this->getSeinarApl();
    	$this->assertNotEmpty($vcard->fn); // precondition
    	$expected = (string) $vcard->fn[0];
    	 
    	$output = $template->output($vcard);
    	$this->assertEquals($expected, $output);
    }
    
    /**
     * Border case. Using iteration on a single-value property, not set.
     * Principle of least surprise: should do nothing.
     * @group default
     * @depends testCategoriesIter
     */
    public function testFNITerEmpty()
    {
    	$fragments = [
    	'vcard'    => '{{each,#fn}}',
    	'each'     => '{{!fn}}'
    			];
    	$template = new Template($fragments);
    
    	$vcard = new vCard();
    	$this->assertEmpty($vcard->fn); // precondition
    
    	$output = $template->output($vcard);
    	$this->assertEmpty($output);
    }
    
    /**
     * @group default
     * @depends testOneRecursion
     */
    public function testMagicID()
    {
    	$template = new Template(['vcard' => '{{!_id}}']);
    	
    	$vcard = $this->getRaithSeinar();
    	$this->assertNotEmpty($vcard->fn); // precondition
    	
    	$output = $template->output($vcard);
    	$this->assertEquals(urlencode((string)$vcard->fn[0]), $output);
    }
    
    /**
     * @group default
     * @depends testOneRecursion
     */
    public function testMagicRawVCard()
    {
    	$template = new Template(['vcard' => '{{!_rawvcard}}']);
    	 
    	$vcard = $this->getRaithSeinar();
        $expected = '' . htmlspecialchars($vcard);
        $this->assertNotEmpty($expected);
    	
    	$output = $template->output($vcard);
    	$this->assertEquals($expected, $output);
    }
}