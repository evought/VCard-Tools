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
     * Some cards for testing.
     * @return an individual VCard.
     */
    public function getRaithSeinar()
    {
	$path = 'tests/vcards/RaithSeinar.vcf';
	$vcard = new vCard($path);
	unset($vcard->version); // don't want version to cause == to fail.
	return $vcard;
    }
	
    /**
     * Some cards for testing.
     * @return an organization VCard.
     */
    public function getSeinarAPL()
    {
	$path = 'tests/vcards/SeinarAPL.vcf';
	$vcard = new vCard($path); // don't want version to cause == to fail.
	unset($vcard->version);
	return $vcard;
    }
	
    /**
     * Some cards for testing.
     * @return an individual VCard.
     */
    public function getDDBinks()
    {
	$path = 'tests/vcards/DDBinks.vcf';
	$vcard = new vCard($path); // don't want version to cause == to fail.
	unset($vcard->version);
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
}