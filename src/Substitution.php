<?php
/** 
 * Template utility for parsing/interrogating fragment substitution.
 * @author Eric Vought evought@pobox.com 2014-12-08
 * @copyright Eric Vought 2014, Some rights reserved.
 * @license MIT http://opensource.org/licenses/MIT
 */

namespace EVought\vCardTools;

/**
 * A structure for storing information about a substitution in a Template
 * fragment.
 * A Substitution is marked by the content of pairs of curly braces in
 * fragment text. The components of a substition allow conditional inclusion,
 * property lookup, iteration, and fragment replacement.
 * This class parses the text marker and provides easy lookup of the components
 * in Template processing.
 */
class Substitution
{
    /**
     * The list of allowed magic identifiers in a bang-field (lookup request).
     * @var array
     */
    public static $allowedMagic = ['_id', '_rawvcard'];
    
    /**
     * The name of the fragment to output, or null.
     * @var string
     */
    private $fragment = null;
    
    /**
     * Get the name of a fragment which should be replaced in this
     * substitution or null if no fragment was named.
     * @return string
     */
    public function getFragment() {return $this->fragment;}
    
    /**
     * Returns true if-and-only-if a fragment was named by this substitution.
     * @return bool
     */
    public function hasFragment() {return $this->fragment !== null;}
	    
    /**
     * The name of the vCard Property this substitution is contingent on,
     * or null;
     * @var string
     */
    private $quest = null;
    
    /**
     * The name of the vCard Property this substitution is contingent on,
     * or null; 
     * @return string
     */
    public function getQuest() {return $this->quest;}
    
    /**
     * Return true if-and-only-if this substitution is conditional on a
     * property.
     * @see getQuest() If true, will return the name of the property the
     * substitution is conditional on.
     * @return bool
     */
    public function hasQuest() {return $this->quest !== null;}
    
    /**
     * The name of a Property this substitution is to look up.
     * @see getLookUp()
     * @var string[];
     */
    private $lookUpProperty = [];

    /**
     * If shouldLookup() returns true, returns an array of 1 or 2 members.
     * The first, 'property' will be the name of the vCard property itself,
     * and, if set, 'field' will be the field within a structured property.
     * e.g. 'property' => 'adr' and 'field' => 'StreetAddress'. Several
     * predicates return information about this field. 
     * @return array
     * @see shouldLookUp()
     * @see lookUpIsStructured()
     * @see isMagic()
     */
    public function getLookUp(){return $this->lookUpProperty;}
    
    /**
     * Returns true if there is a property to look up, false otherwise.
     * @return boolean
     */
    public function shouldLookUp() {return !(empty($this->lookUpProperty));}
        
    /**
     * Returns true if $lookUpProperty contains both the name of a property and
     * of a field within that property.
     * @return boolean
     */
    public function lookUpIsStructured()
    {
    	return ( !empty($this->lookUpProperty)
    			&& array_key_exists('field', $this->lookUpProperty) );
    }
    
    /**
     * Returns true if shouldLookUp() is true and the 'property' element
     * contains a magic value (with leading underscore intact).
     * @see shouldLookUp()
     * @see getLookUp()
     * @return boolean
     */
    public function isMagic()
    {
    	if ( $this->shouldLookUp()
    	     && ('_' === substr($this->lookUpProperty['property'], 0, 1)) )
    	    return true;
    	else
    	    return false;
    }
    
    /**
     * The name of a vCard Property to iterate over or null.
     * @var string
     */
    private $iterOver = null;

    /**
     * Returns name of a vCard Property to iterate over if iterates() returns
     * true.
     * @return string
     * @see iterates()
     */
    public function getIterOver() {return $this->iterOver;}
    
    /**
     * Returns true if this substitution requests iteration over a property
     * value and getIterOver() will return the name of that property, false
     * otherwise.
     * @see getIterOver()
     * @return boolean
     */
    public function iterates() {return null !== $this->iterOver;}
    
    /**
     * Construct a new, empty Substitution.
     * @see fromText() Construct and return from substituion text.
     * @see fromFragment() Construct and return from a named fragment.
     */
    private function __construct(){}
    
    /**
     * If there was a bang (!) in the substitution string requesting the
     * look up of a value, this function is called to parse it (the leading
     * bang should have already been stripped. The lookUp value may have an
     * embedded space indicating that it refers to a field within a
     * structured property. Sets lookUpProperty appropriately.
     * @see lookUpProperty
     * @param string $lookUp The bang-field from the substitution text, minus
     * the initial bang.
     * @throws \DomainException if a magic identifier is used which is not
     * allowed.
     * @see $allowedMagic
     */
    private function parseBangProperty($lookUp)
    {
    	assert(null !== $lookUp);
    	assert(is_string($lookUp));
    	
    	if ( ('_' === substr($lookUp, 0, 1)) &&
    	     !(in_array($lookUp, self::$allowedMagic)) )
    	{
    	    throw new \DomainException($lookUp . ' is not allowed magic.'); 	
    	}
    	
    	$compoundKey = explode(' ', $lookUp, 2);
    	$this->lookUpProperty = ['property' => $compoundKey[0]];
    	if (count($compoundKey) == 2)
    	    $this->lookUpProperty['field'] = $compoundKey[1];
    }
    
    /**
     * Parse the given text to produce and return a Substitution.
     * @param string $text
     * @return \vCardTools\Substitution
     */
    public static function fromText($text)
    {
    	assert($text !== null);
    	assert(is_string($text));
    	
    	$substitution = new Substitution();
    	// separate by commas, ignore leading and trailing space
    	$text_parts = array_map("trim", explode(",", $text));
    	 
    	foreach ($text_parts as $part)
    	{
    		// If we have multiples of the same type, last one clobbers
    		// Figure out what it is and store it
    		if (substr($part, 0, 1) == "!")
    			$substitution->parseBangProperty(substr($part, 1));
    		else if (substr($part, 0, 1) == "?")
    			$substitution->quest = substr($part, 1);
    		else if (substr($part, 0, 1) == "#")
    			$substitution->iterOver = substr($part, 1);
    		else
    			$substitution->fragment = $part;
    	}
    	
    	return $substitution;
    }
    
    /**
     * Build and return a Substitution from the key of a fragment.
     * @param string $fragment
     * @return Substitution
     */
    public static function fromFragment($fragment)
    {
    	$substitution = new Substitution();
    	$substitution->fragment = $fragment;
    	return $substitution;
    }
}
