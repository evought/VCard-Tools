<?php
/**
 * PHPUnit testcase for vcard-templates
 */
use vCardTools\vCard as vCard;
use vCardTools\Template;
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
	
    public function testTrivialTemplate()
    {
    	$templates = [];
    	$vcard = new VCard();

    	$output = Template::output_vcard($vcard, $templates);
    	
    	$this->assertEmpty($output, print_r($output, true));
    }
    
    /**
     * @depends testTrivialTemplate
     */
    public function testLiteralTemplate()
    {
    	$templates = ['vcard' => 'LiteralOutput'];
    	$vcard = new VCard();
    
    	$output = Template::output_vcard($vcard, $templates);
    	 
    	$this->assertEquals( $templates['vcard'], $output,
    			     print_r($output, true) );
    }
    
    /**
     * @depends testLiteralTemplate
     */
    public function testOneRecursion()
    {
    	$templates = [
    	               'vcard'     => '{{content}}',
    	               'content'   => 'OneRecursion'
                     ];
    	$vcard = new VCard();
    
    	$output = Template::output_vcard($vcard, $templates);
    
    	$this->assertEquals( $templates['content'], $output,
    			print_r($output, true) );
    }
    
    /**
     * @depends testLiteralTemplate
     */
    public function testOneRecursionNoMatch()
    {
    	$templates = [
    	'vcard'     => '{{content}}'
    			];
    	$vcard = new VCard();
    
    	$output = Template::output_vcard($vcard, $templates);
    
    	$this->assertEmpty($output, print_r($output, true));
    }
    
    /**
     * @depends testOneRecursion
     */
    public function testSeveralLayers()
    {
    	$templates = [
    	'vcard'     => '{{layer1}}',
    	'layer1'    => '{{layer2}}',
    	'layer2'    => '{{layer3}}',
    	'layer3'    => 'Layer3Output'
    			];
    	$vcard = new VCard();
    
    	$output = Template::output_vcard($vcard, $templates);
    
    	$this->assertEquals( $templates['layer3'], $output,
    			print_r($output, true) );
    }

    /**
     * @depends testOneRecursion
     */
    public function testInsideSubsitution()
    {
    	$templates = [
    	'vcard'     => 'Content {{content}} here.',
    	'content'   => 'goes'
    			];
    	$vcard = new VCard();
    
    	$output = Template::output_vcard($vcard, $templates);
    
    	$this->assertEquals( 'Content goes here.', $output,
    			print_r($output, true) );
    }

    /**
     * @depends testOneRecursion
     */
    public function testInsideSubsitutionEmpty()
    {
    	$templates = [
    	'vcard'     => 'Empty [{{content}}]'
    			];
    	$vcard = new VCard();
    
    	$output = Template::output_vcard($vcard, $templates);
    
    	$this->assertEquals( 'Empty []', $output,
    			print_r($output, true) );
    }
    
    /**
     * @depends testInsideSubsitution
     */
    public function testSubstitutionTree()
    {
    	$templates = [
    	               'vcard'     => '{{A}}, {{One}}',
    	               'A'         => 'a {{B}} {{C}}',
    	               'B'         => 'b',
    	               'C'         => 'c',
    	               'One'       => '1 {{Two}} {{Three}}',
    	               'Two'       => '2',
    	               'Three'     => '3'
    		     ];
    	$vcard = new vCard();
    	
    	$output = Template::output_vcard($vcard, $templates);
    	
    	$this->assertEquals( 'a b c, 1 2 3', $output,
    			print_r($output, true) );
    }
    
    /**
     * @depends testLiteralTemplate
     */
    public function testFallback()
    {
    	$templates_fallback = ["vcard" => "Fallback"];
    	$templates = ['_fallback' => $templates_fallback];
    	
    	$vcard = new vCard();
    	
    	$output = Template::output_vcard($vcard, $templates);
        $this->assertEquals('Fallback', $output);
    } // testFallBack()
    
    /**
     * @depends testLiteralTemplate
     */
    public function testFNLookupEmpty()
    {
    	$templates = ['vcard' => '{{!fn}}'];
    	 
    	$vcard = new vCard();
    	 
    	$output = Template::output_vcard($vcard, $templates);
    	$this->assertEmpty($output);
    }
    
    /**
     * @depends testLiteralTemplate
     */
    public function testFNLookup()
    {
    	$templates = ['vcard' => '{{!fn}}'];
    	
    	$vcard = $this->getRaithSeinar();
    	$this->assertNotEmpty($vcard->fn); // precondition
    	
    	$output = Template::output_vcard($vcard, $templates);
    	$this->assertEquals($vcard->fn, $output);
    }
    
    /**
     * @depends testLiteralTemplate
     */
    public function testNLastNameLookup()
    {
    	$templates = ['vcard' => '{{!n LastName}}'];
    	 
    	$vcard = $this->getRaithSeinar();
    	$this->assertNotEmpty($vcard->n, print_r($vcard, true));
    	$this->assertNotEmpty($vcard->n[0]['LastName'], print_r($vcard, true)); // precondition
    	 
    	$output = Template::output_vcard($vcard, $templates);
    	$this->assertEquals($vcard->n[0]['LastName'], $output);
    }
    
    /**
     * @depends testFNLookup
     */
    public function testQuestFNNo()
    {
    	$templates = ['vcard' => '{{output,?fn}}', 'output' => 'Output'];
    	
    	$vcard = new vCard();
    	$this->assertEmpty($vcard->fn); // precondition
    	
    	$output = Template::output_vcard($vcard, $templates);
    	$this->assertEmpty($output, $output);
    }
    
    /**
     * @depends testFNLookup
     */
    public function testQuestFNYes()
    {
    	$templates = ['vcard' => '{{output,?fn}}', 'output' => 'Output'];
    	 
    	$vcard = $this->getRaithSeinar();
    	$this->assertNotEmpty($vcard->fn); // precondition
    	 
    	$output = Template::output_vcard($vcard, $templates);
    	$this->assertEquals('Output', $output);
    }
    
    /**
     * @depends testFNLookup
     */
    public function testCategoriesIterEmpty()
    {
    	$templates = ['vcard' => '{{#categories}}'];
    	
    	$vcard = new vCard();
    	$this->assertEmpty($vcard->categories); // precondition
    	
    	$output = Template::output_vcard($vcard, $templates);
    	$this->assertEmpty($output);
    }
    
    /**
     * @depends testFNLookup
     */
    public function testCategoriesIter()
    {
    	$templates = [
    			'vcard'    => '{{each,#categories}}',
    			'each'     => '{{!categories}}|'
		     ];
    	 
    	$vcard = $this->getSeinarApl();
    	$this->assertNotEmpty($vcard->categories); // precondition
    	$expected = $vcard->categories;
    	sort($expected);
    	 
    	$output = Template::output_vcard($vcard, $templates);
    	$this->assertNotEmpty($output);
    	
    	$output_array = explode('|', $output);
    	
    	$tail = array_pop($output_array); // trailing separator
    	$this->assertEmpty($tail, $output);
    	
    	// values can come back in any order and may have whitespace
    	$output_array = array_map('trim', $output_array);
    	sort($output_array);
    	$this->assertEquals($expected, $output_array);
    }
}