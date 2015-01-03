<?php

/* 
 * Utility for Template metadata.
 * @author Eric Vought evought@pobox.com 2014-12-08
 * @copyright Eric Vought 2014, Some rights reserved.
 * @license MIT http://opensource.org/licenses/MIT
 */

namespace EVought\vCardTools;

/**
 * A container for Template metadata.
 * @author evought
 *
 */
class TemplateInfo
{
    private $name = null;
    private $description = null;
    private $usage = null;
    private $see = null;
    
    private $info = [];

    /**
     * The name of the template. This should be the same name it is registered
     * under.
     */
    public function getName(){return $this->name;}
    
    /**
     * A short (e.g. one line) description of the template and its purpose.
     * @return string|null
     */
    public function getDescription(){return $this->description;}
    
    /**
     * Set the short description.
     * @param string|null $description.
     * @return \vCardTools\TemplateInfo
     */
    public function setDescription($description)
    {
    	$this->description = $description;
    	return $this;
    }
    
    /**
     * Longer (multi-line) usage information for the template.
     * @return string|null
     */
    public function getUsage(){return $this->usage;}
    
    /**
     * Set the usage text.
     * @param string|null $usage
     * @return \vCardTools\TemplateInfo
     */
    public function setUsage($usage)
    {
    	$this->usage = $usage;
    	return $this;
    }
    
    /**
     * A URL to more information about the template.
     * @return string|null Should be a URL.
     */
    public function getSee(){return $this->see;}
    
    /**
     * Provide a URL cross-reference for more information.
     * @param string|null $see
     * @return \vCardTools\TemplateInfo
     */
    public function setSee($see)
    {
        \assert(null !== $see);
        $url = \filter_var($see, \FILTER_VALIDATE_URL);
        if (false === $url)
            throw new \DomainException($see . ' is not a valid url.');
        else
            $this->see = $see;
        return $this;
    }
    
    /**
     * Additional descriptive fields as keys and values.
     * @return array
     */
    public function getInfo(){return $this->info;}
    
    /**
     * Provide an array of additional information fields (should be string
     * or string-convertible values). Clears any existing array.
     * @param unknown $info
     * @return \vCardTools\TemplateInfo
     */
    public function setInfo($info)
    {
    	assert(is_array($info));
    	$this->info = $info;
    	return $this;
    }
    
    /**
     * Magic method. Set additional informational keys and values.
     * @param unknown $name
     * @param unknown $value
     */
    public function __set($name, $value)
    {
    	assert(null !== $name);
    	assert((null === $value) || is_string($value));
    	
    	$this->info[$name] = $value;
    }
    
    /**
     * Magic method. Get additional informational keys and values.
     * @param unknown $name
     * @return NULL|multitype:
     */
    public function __get($name)
    {
    	assert(null !== $name);
    	
    	if (!array_key_exists($name, $this->info)) return null;
    	return $this->info[$name];
    }
    
    /**
     * Magic method. Unset an additional informational keys.
     * @param unknown $name
     * @return NULL|multitype:
     */
    public function __unset($name)
    {
    	assert(null !== $name);
    	assert(is_string($name));
    	
    	if (array_key_exists($name, $this->info))
	    unset($this->info[$name]);
    }

    /**
     * Magic method. Test an additional informational key.
     * @param unknown $name
     * @return NULL|multitype:
     */
    public function __isset($name)
    {
    	assert(null !== $name);
    	assert(is_string($name));
    	
    	return isset($this->info[$name]);
    }
    
    /**
     * Construct a new TemplateInfo, setting $name and $description if desired.
     * Name cannot be changed by Public caller after construction.
     * @param string|null $name
     * @param string|null $description
     */
    public function __construct($name = null, $description = null)
    {
    	assert((null === $name) || is_string($name));
    	assert((null === $description) || is_string($description));
    	
    	$this->name = $name;
    	$this->description = $description;
    }
    
    /**
     * Create a new TemplateInfo from an array of keys and values. Intended
     * as a convenience method when loading Templates from .ini files.
     * Looks for defined fields first and strips them, then stores the rest as
     * additional informational fields.
     * @param Array $data Not null.
     * @return \vCardTools\TemplateInfo
     * @see getInfo()
     */
    public static function fromArray(Array $data)
    {
    	assert(null !== $data);
    	$templateInfo = new TemplateInfo();
    	
    	foreach (['name', 'description', 'usage', 'see'] as $field)
    	{
    	    if (!empty($data[$field]))
    	    {
    	        if (!is_string($data[$field]))
    	            throw new \DomainException($field . ' must be a string.');
    	        $templateInfo->$field = $data[$field];
    	        unset($data[$field]);
    	    }
    	}
    	$templateInfo->info = $data;
    	return $templateInfo;
    }
}
