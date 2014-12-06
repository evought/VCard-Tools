<?php
/**
 * PHPUnit testcase for vcard-templates
 */
use vCardTools\vCard as vCard;
use vCardTools\Template;
use vCardTools\Substitution as Substitution;
use vCardTools\TemplateInfo;
require_once 'vcard.php';
require_once 'vcard-templates.php';

class VCardTemplatesTest extends PHPUnit_Framework_TestCase
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
    	    $path = 'tests/vcards/RaithSeinar.vcf';
	    $this->raithSeinar = new vCard($path);
	    // don't want version to cause == to fail.
	    unset($this->raithSeinar->version); 
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
	    $path = 'tests/vcards/SeinarAPL.vcf';
	    $this->seinarAPL = new vCard($path);
	    // don't want version to cause == to fail.
	    unset($this->seinarAPL->version);
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
	    $path = 'tests/vcards/DDBinks.vcf';
	    $this->ddBinks = new vCard($path);
	    // don't want version to cause == to fail.
	    unset($this->ddBinks->version);
    	}
	return $vcard;
    }
    
    public function testTemplateInfoFromArrayEmpty()
    {
    	$info = TemplateInfo::fromArray([]);
    	 
    	$this->assertNull($info->getName());
    	$this->assertNull($info->getDescription());
    	$this->assertNull($info->getUsage());
    	$this->assertNull($info->getSee());
    	$this->assertEmpty($info->getInfo());
    }
    
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
     * @depends testTemplateInfoFromArray
     */
    public function testGetDefault()
    {
    	$template = Template::getDefaultTemplate();
    	$this->assertInstanceOf('vCardTools\Template', $template);
    	    	
    	$this->assertNotNull($template->getFragments());
    	$this->assertNull($template->getFallback());
    	
    	$registeredTemplate = Template::getTemplate('default');
    	$this->assertNotNull($registeredTemplate);
    	$this->assertSame( $template, $registeredTemplate,
                           print_r($registeredTemplate, true) );
    }
    
    public function testConstructWFragments()
    {
    	$fragments = [];
    	$template = new Template($fragments);
    	$this->assertInstanceOf('vCardTools\Template', $template);
    	
    	$this->assertSame($fragments, $template->getFragments());
    	$this->assertNull($template->getFallback());
    }
    
    public function testConstructWInfo()
    {	
    	$fragments = [];
    	$template = new Template($fragments, null, new TemplateInfo('George'));
    	$this->assertInstanceOf('vCardTools\Template', $template);

    	$this->assertSame($fragments, $template->getFragments());
    	$this->assertEquals('George', $template->getName());
    	$this->assertNull($template->getFallback());
    	 
    }
    
    public function testGetTemplateNoneExists()
    {
    	$template = Template::getTemplate('testGetTemplateNoneExists');
    	$this->assertNull($template, print_r($template, true));
    }
    
    /**
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
     * @testConstructWFragments
     */
    public function testFromININoName()
    {
    	$template
    	    = Template::fromINI('tests/templates/testFromININoName.ini');
    	$this->assertNotNull($template);
    	
    	$fragments = $template->getFragments();
    	$this->assertNotNull($fragments);
    	
    	$this->assertEquals(['vcard'=>'content'], $fragments);
    	
    	$this->assertNull($template->getFallback());
    }
    
    /**
     * @depends testFromININoName
     * @depends testTemplateInfoFromArray
     */
    public function testFromINIWithName()
    {
    	$expected = ['vcard' => 'content'];
    	
    	$template
    	    = Template::fromINI('tests/templates/testFromINIWithName.ini');
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
     * @depends testFromINIWithName
     */
    public function testFromINIWithNameExplicitFallback()
    {
    	$expected = ['vcard' => 'content'];
    	 
    	$template = Template::fromINI(
    	    'tests/templates/testFromINIWithNameExplicitFallback.ini',
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
     * @depends testFromINIWithName
     */
    public function testFromINIWithFallback()
    {
    	// precondition
    	$this->assertNotNull(Template::getTemplate('testFromINIWithName'));
    	
    	$expected = ['vcard' => 'content'];
    
    	$template = Template::fromINI(
    			'tests/templates/testFromINIWithFallback.ini' );
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
     * @testFromINIWithName
     */
    public function testFromINILoadFallback()
    {
    	// precondition
    	$this->assertNull(
    		Template::getTemplate('testFromINILoadFallbackFallback') );
    	 
    	$expected = ['vcard' => 'content'];
    
    	$template = Template::fromINI(
    			'tests/templates/testFromINILoadFallback.ini' );
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
    
    public function testTrivialTemplate()
    {
    	$template = new Template([]);
    	$vcard = new vCard();

    	$output = $template->output($vcard);
    	
    	$this->assertEmpty($output, print_r($output, true));
    }
    
    /**
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
    
    public function testSubstitutionFromTextFragment()
    {
    	$substitution = Substitution::fromText('key');
    	$this->assertInstanceOf('vCardTools\Substitution', $substitution);
    	 
    	$this->assertTrue($substitution->hasFragment());
    	$this->assertEquals('key', $substitution->getFragment());
    	 
    	$this->assertFalse($substitution->hasQuest());
    	$this->assertFalse($substitution->shouldLookUp());
    	$this->assertFalse($substitution->iterates());
    }
    
    public function testSubstitutionFromTextQuest()
    {
    	$substitution = Substitution::fromText('key, ?adr');
    	$this->assertInstanceOf('vCardTools\Substitution', $substitution);
    
    	$this->assertTrue($substitution->hasFragment());
    	$this->assertEquals('key', $substitution->getFragment());
    
    	$this->assertTrue($substitution->hasQuest());
    	$this->assertEquals('adr', $substitution->getQuest());
    	 
    	$this->assertFalse($substitution->shouldLookUp());
    	$this->assertFalse($substitution->iterates());
    }
    
    public function testSubstitutionFromTextLookup()
    {
    	$substitution = Substitution::fromText('!n');
    	$this->assertInstanceOf('vCardTools\Substitution', $substitution);
    
    	$this->assertFalse($substitution->hasFragment());
    
    	$this->assertFalse($substitution->hasQuest());
    
    	$this->assertTrue( $substitution->shouldLookUp(),
                           print_r($substitution->getLookUp(), true) );
    	$this->assertEquals('n', $substitution->getLookUp()['property']);
    	
    	$this->assertFalse($substitution->lookUpIsStructured());
    
    	$this->assertFalse($substitution->iterates());
    }
    
    public function testSubstitutionFromTextLookupStructured()
    {
    	$substitution = Substitution::fromText('!n FirstName');
    	$this->assertInstanceOf('vCardTools\Substitution', $substitution);
    
    	$this->assertFalse($substitution->hasFragment());
    
    	$this->assertFalse($substitution->hasQuest());
    
    	$this->assertTrue( $substitution->shouldLookUp(),
    			print_r($substitution->getLookUp(), true) );
    	$this->assertEquals('n', $substitution->getLookUp()['property']);
    	 
    	$this->assertTrue($substitution->lookUpIsStructured());
    	$this->assertEquals('FirstName', $substitution->getLookUp()['field']);
    
    	$this->assertFalse($substitution->iterates());
    }
    
    public function testSubstitutionFromTextLookupMagic()
    {
    	$substitution = Substitution::fromText('!_id');
    	$this->assertInstanceOf('vCardTools\Substitution', $substitution);
    
    	$this->assertFalse($substitution->hasFragment());
    	$this->assertFalse($substitution->hasQuest());
    
    	$this->assertTrue($substitution->shouldLookUp());
    	$this->assertFalse($substitution->lookUpIsStructured());
    	$this->assertTrue($substitution->isMagic());
    	$this->assertEquals('_id', $substitution->getLookUp()['property']);
    
    	$this->assertFalse($substitution->iterates());
    }

    /**
     * @expectedException \DomainException
     */
    public function testSubstitutionFromTextLookupBadMagic()
    {
    	$substitution = Substitution::fromText('!_abacadabra');
    }
    
    
    public function testSubstitutionFromTextIterates()
    {
    	$substitution = Substitution::fromText('key, #n');
    	$this->assertInstanceOf('vCardTools\Substitution', $substitution);
    
    	$this->assertTrue($substitution->hasFragment());
    	$this->assertEquals('key', $substitution->getFragment());
    
    	$this->assertFalse($substitution->hasQuest());
    
    	$this->assertFalse($substitution->shouldLookUp());
    
    	$this->assertTrue($substitution->iterates());
    	$this->assertEquals('n', $substitution->getIterOver());
    }
    
    /**
     * @depends testLiteralTemplate
     * @depends testSubstitutionFromTextFragment
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
     * @depends testLiteralTemplate
     * @depends testSubstitutionFromTextFragment
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
     * @depends testLiteralTemplate
     * @depends testSubstitutionFromTextLookup
     */
    public function testFNLookupEmpty()
    {
    	$template = new Template(['vcard' => '{{!fn}}']);
    	 
    	$vcard = new vCard();
    	 
    	$output = $template->output($vcard);
    	$this->assertEmpty($output);
    }
    
    /**
     * @depends testLiteralTemplate
     * @depends testSubstitutionFromTextLookup
     */
    public function testFNLookup()
    {
    	$template = new Template(['vcard' => '{{!fn}}']);
    	
    	$vcard = $this->getRaithSeinar();
    	$this->assertNotEmpty($vcard->fn); // precondition
    	
    	$output = $template->output($vcard);
    	$this->assertEquals($vcard->fn, $output);
    }
    
    /**
     * @depends testLiteralTemplate
     * @depends testSubstitutionFromTextLookup
     */
    public function testNLastNameLookup()
    {
    	$template = new Template(['vcard' => '{{!n LastName}}']);
    	 
    	$vcard = $this->getRaithSeinar();
    	//preconditions
    	$this->assertNotEmpty($vcard->n, print_r($vcard, true));
    	$this->assertNotEmpty($vcard->n[0]['LastName'], print_r($vcard, true));
    	 
    	$output = $template->output($vcard);
    	$this->assertEquals($vcard->n[0]['LastName'], $output);
    }
    
    /**
     * @depends testFNLookup
     * @depends testSubstitutionFromTextQuest
     */
    public function testQuestFNNo()
    {
    	$fragments = ['vcard' => '{{output,?fn}}', 'output' => 'Output'];
    	$template = new Template($fragments);
    	
    	$vcard = new vCard();
    	$this->assertEmpty($vcard->fn); // precondition
    	
    	$output = $template->output($vcard);
    	$this->assertEmpty($output, $output);
    }
    
    /**
     * @depends testFNLookup
     * @depends testSubstitutionFromTextQuest
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
     * @depends testFNLookup
     * @depends testSubstitutionFromTextIterates
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
     * @depends testFNLookup
     * @depends testSubstitutionFromTextIterates
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
     * @depends testCategoriesIter
     * @depends testSubstitutionFromTextIterates
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
    	$expected = $vcard->fn;
    	 
    	$output = $template->output($vcard);
    	$this->assertEquals($expected, $output);
    }
    
    /**
     * Border case. Using iteration on a single-value property, not set.
     * Principle of least surprise: should do nothing.
     * @depends testCategoriesIter
     * @depends testSubstitutionFromTextIterates
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
     * @depends testOneRecursion
     * @depends testSubstitutionFromTextFragment
     */
    public function testMagicID()
    {
    	$template = new Template(['vcard' => '{{!_id}}']);
    	
    	$vcard = $this->getRaithSeinar();
    	$this->assertNotEmpty($vcard->fn); // precondition
    	
    	$output = $template->output($vcard);
    	$this->assertEquals(urlencode($vcard->fn), $output);
    }
    
    /**
     * @depends testOneRecursion
     * @depends testSubstitutionFromTextFragment
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