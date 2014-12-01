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
    
}