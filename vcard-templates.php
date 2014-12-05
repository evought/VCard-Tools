<?php
/**
 * HTML output templates for vcard-tools.php.
 * @author Eric Vought evought@pobox.com 2014-11-16
 * @copyright Eric Vought 2014, Some rights reserved.
 * @license CC-BY 4.0 http://creativecommons.org/licenses/by/4.0/
 */
namespace vCardTools;

/**
 * A template processor for turning vCards into HTML (or potentially,
 * any other output format, but it is well-suited to tree-structured markup).
 * The processor works from a collection of named html fragments, and
 * recursively builds the output string. It is best if you can have each
 * fragment be a complete tag (e.g. div/span) or attribute so that there are
 * no errors with unmatched closing tags.
 * output() starts with the "vcard" entry.
 *
 * Pairs of curly braces "{{" and "}}" surrounding text are substituted with
 * another template from the same table. So "{{content}}" will be
 * substituted by looking up "content" in the same table. If there is no
 * template by that name (or you have a typo), it will be skipped.
 * The text inside the curly braces will be referred to as the "key".
 *
 * By following these substitutions, the html output gets built one template at
 * a time as a tree and can be quite sophisticated.
 *
 * The substitution key can have multiple key parts separated by commas to
 * control how the fragment is substituted. e.g. "{{email_block,?email}}".
 * Extra space around the key parts is ignored, so "{{email_block, ?email}}"
 * will do the same thing.
 *
 * The simplest key part is just a fragment name as with
 * "content" or "email_block", above.
 *
 * If the key part starts with a bang ("!"), the contents of the matching
 * vcard field are substituted for the key (e.g. "{{!email}}" or
 * "{{!n FirstName}}"). If that field contains a space as in {{!n FirstName}},
 * it will pull the named sub-field from a structured vcard element. In this
 * case, it will pull the FirstName from the n element. If the vcard contains
 * multiple fields by that name (more than one TEL, for instance), all of them
 * will be returned separated by spaces. This works *very poorly* with structured
 * elements such as org, adr, and n: use ? and # to control output as described
 * below.
 *
 * If the key part starts with a question mark ("?"), then it will only be
 * substituted if the field named after the ? exists. So,
 * "{{email_span, ?email}}" will substitute the "email_span" if and only if
 * the email field has at least one value. That is, if there is a question
 * mark and the vcard field is not found, then the rest of the key parts
 * are ignored. This can be used to turn off an entire tree of substitutions.
 *
 * If the key part starts with a pound sign ("#"), then the fragment will
 * be substituted once *for each value* of the vcard field named after the #.
 * So, if tell_span is defined as '<span class="tel">{{!tell}}</span>',
 * then '<div class="tel_block">{{tell_span, #tel}}</div>' might produce:
 * '<div class="tel_block"><span class="tel">555-1212</span> <span class="tel">999-4121</span></div' or it
 * might produce just '<div class="tel_block"></div>'. Using a # with a
 * ? somewhere up the tree gives a lot of control over what structural html
 * tags you produce. This default template has examples and the unit tests
 * also demonstrate various combinations.
 *
 * Lastly, you can combine a ! key part and a fragment name. If the field
 * is not there, the named template will be substituted instead.
 * "{{!email, no_email}}" will either substitute the contents of the email
 * vcard field(s) if it is there OR it will look up the no_email fragment and
 * process it instead.
 *
 * Order of subkeys does not matter: "{{my_template, ?email}}" and
 * "{{?email, my_template}}" do the same thing.
 * 
 * Subkeys starting with an underscore *are reserved*, as are subkeys beginning
 * with a percent sign.
 *
 * Do not edit the default template. Create your own array of fragments and
 * initialize a new Template instance.
 * You can then create a template which will output as a table,
 * for instance, instead of divs and spans, or will output just summary
 * information. Build slowly and test a piece at a time.
 * 
 * You may also create a new Template and set its $fallback parameter to
 * look up any undefined fragments in the fallback Template. This means that
 * you may create a new Template, set $fallback to the default template, and
 * only set the specific fragments that you wish to change. When the Template
 * is output, any fragments you set will be used and any you do not define will
 * fall back to the default. You can chain templates via $fallback to any depth.
 * 
 * There is also a mechanism to get and register templates by name or load them
 * from .ini files.
 *
 * !_id and !_rawvcard are magic: they return a urlencoded version of fname
 * (suitable for using in an href for the whole vcard) and a raw text
 * export of the vcard, respectively.
 *
 * _WARNING_ Using multiple similar key parts in the same key has undefined
 * results. In other words, "{{my_template, ?email, ?adr}}" or
 * "{{!email, !role}}" or {{template1, template2}} may do something,
 * may cause an error, or may hatch chickens. It also may do something different
 * in future versions.
 * 
 * @example
 * // use the default template to output $myvcard
 * Template::getDefaultTemplate()->output($myvcard);
 * 
 * @example
 * // create your own simple template and output $myvcard
 * $fragments = [vcard => '{{!fn}}'];
 * $template = new Template($fragments)->output($myvcard);
 * 
 * @example
 * // create your own template with a custom URL, falling back to the default
 * // for everything else and output myvcard
 * $fragments = [fn_href_url => 'http://example.com/view.html?id=447'];
 * $template = new Template($fragments, Template::getDefaultTemplate());
 * $template->output($myvcard);
 */
class Template
{
    /**
     * The array of named html fragments used to output a vCard.
     * @var Array
     */
    private $fragments;
        
    /**
     * The fallback Template to use for undefined keys.
     * @var Template
     */
    private $fallback;
    
    static private $initialized = false;
    
    static private function i_init()
    {
    	if (self::$initialized === true) return;
    	
    	// FIXME: location awareness
    	self::$defaultTemplate
    	    = self::i_fromINI('templates/defaultTemplate.ini');
    	
    	self::$initialized = true;
    }
    
    /**
     * The default Template instance.
     * @var Template
     */
    static private $defaultTemplate = null;
    
    static private $templateRegistry = [];
    
    /**
     * Return the default Template instance.
     * @return \vCardTools\Template
     */
    static public function getDefaultTemplate()
    {
        if (self::$initialized === false) self::i_init();
    	return self::$defaultTemplate;
    }
    
    /**
     * Add $template to the registry of named Templates for later retrieval.
     * Overwrites any existing value for $name.
     * @param string $name The name to register the Template under. Not null.
     * @param Template $template The Template instance to store.
     */
    static public function registerTemplate($name, Template $template)
    {
    	assert(null !== $name);
    	assert(is_string($name));
    	assert($template != null);
    	
        if (self::$initialized === false) self::i_init();
    	
    	self::$templateRegistry[$name] = $template;
    }
    
    /**
     * Retrieves the named Template from the registry or null if none is found.
     * 'default' should always be defined and return a default HTML template. 
     * @param string $name
     * @return Template|NULL
     */
    static public function getTemplate($name)
    {
    	assert(null !== $name);
    	assert(is_string($name));
    	
        if (self::$initialized === false) self::i_init();
    	
    	if (array_key_exists($name, self::$templateRegistry))
    	{
            assert(is_a(self::$templateRegistry[$name], 'vCardTools\Template'));
            return self::$templateRegistry[$name];
    	} else {
    	    return null;
    	}
    }
    
    /**
     * Creates and returns a Template from an INI file. The INI file should
     * create a conformant array of fragments.
     * If the 'template_name' key is set in the INI file, then the new Template
     * will be registered by that name.
     * If the '_fallback' key is set in the INI file (and it is not provided
     * explicitly), then an attempt is made to look up a registered Template
     * by that name and set it as the fallback for the new template.
     * Lastly, if no registered Template by that name is found, but a key
     * named '_fallback_file' is found in the INI, then an attempt will be
     * made to load THAT INI, and do so recursively if appropriate.
     * @param string $filename Must be a filename for a readable file.
     * @param Template $fallback If not null, will be set as the fallback
     * Template for the newly created instance.
     * @throws \DomainException If the filename is not readable.
     * @throws \RuntimeException If the file cannot be loaded.
     * @return \vCardTools\Template
     */
    static public function fromINI($filename, Template $fallback = null)
    {
    	assert(!empty($filename), '$filename may not be empty');
    	
        if (self::$initialized === false) self::i_init();    	
    	
    	return self::i_fromINI($filename, $fallback);
    }
    
    private static function i_fromINI($filename, Template $fallback = null)
    {
    	assert(!empty($filename), '$filename may not be empty');
    	
    	if (!(is_readable($filename)))
    	    throw new \DomainException(
    	    	'Filename, ' . $filename . 'must exist and be readable' );
    	$fragments = parse_ini_file($filename);
    	if (false === $fragments)
    	{
    	    throw new \RuntimeException('Failed to load INI file '.$filename);
    	}
    	
    	if ( (null === $fallback)
    	     && (array_key_exists('_fallback', $fragments))
             && (array_key_exists( $fragments['_fallback'],
             		           self::$templateRegistry ) ) )
    	{
    	    $fallback = self::$templateRegistry[$fragments['_fallback']];
    	}
    	if ( (null === $fallback)
    	     && (array_key_exists('_fallback_file', $fragments)) )
    	{
    	    $fallback = self::i_fromINI($fragments['_fallback_file']);
    	}
    	
    	$template = new Template($fragments, $fallback);
    	
    	if (array_key_exists('template_name', $fragments))
    	    self::$templateRegistry[$fragments['template_name']] = $template;
    	
    	return $template;    	 
    }
    
    /**
     * Create a new template.
     * @param array $fragments A an array of named html fragments use to output
     * a vCard. Not null.
     * @param Template $fallback Another Template instance to fall back to for
     * any keys not found in $fragments. Often, this should be set with
     * getDefaultFragment().
     */
    public function __construct(Array $fragments, Template $fallback = null)
    {
    	assert(null !== $fragments);
    	$this->fragments = $fragments;
    	$this->fallback = $fallback;
    }
    
    /**
     * Returns the array of named html fragments this Template will use to
     * output vCards.
     * @return Array
     */
    public function getFragments() {return $this->fragments;}
    
    /**
     * Return the Template this Template will use to look up undefined keys,
     * or null if none defined.
     * @return \vCardTools\Template
     */
    public function getFallback() {return $this->fallback;}

    /**
     * Produce HTML output from the given vcard by applying named fragments
     * starting from 'vcard'.
     * @arg vCard vcard The vcard to output. Not null.
     * @return string The resulting HTML.
     */
    public function output(vCard $vcard)
    {
        assert(null !== $vcard);
        assert($this->fragments !== null);
	    	
        $vcard->setFNAppropriately();

        return $this->i_processFragment($vcard, 'vcard');
    } //output_vcard()
		
    /**
     * Finds the required template by $key in $fragments and returns it if
     * found.
     * If the current key is not in $fragments, searches in $fallback
     * (potentially recursively).
     * @arg string $key The key of the template to locate. Not null.
     * @return string|null The requested template, if found.
     */
    private function i_findFragment($key)
    {
        assert($key !== null);
	assert(is_string($key));
	assert($this->fragments !== null);
		
	if (array_key_exists($key, $this->fragments))
            return $this->fragments[$key];
	else if (null !== $this->fallback)
	    return $this->fallback->i_findFragment($key);
	else
	    return null;
    } // i_findFragment()
	
    /**
     * Internal helper for producing HTML for vcard from fragments.
     * Recurses from $key, processing substitutions and returning its portion
     * of the HTML tree.
     *
     * @arg vCard $vcard The vcard being written out.
     * @arg Substitution $substitution The current Substitution being processed.
     * Not null.
     * @arg string $iter_over The current vcard field being iterated over,
     * if any.
     * @arg mixed $iter_item The current element of the vcard field being iterated over,
     *   if any.
     * @return string The portion of the HTML tree output.
     */
    private function i_processSubstitution( vCard $vcard,
    		                            Substitution $substitution,
    		                            $iterOver="", $iterItem=null )
    {
	assert(null !== $vcard);
	assert(null !== $this->fragments);
	assert(null !== $substitution);
	    	
	// if we are conditional on a field and it isn't there, bail.
	if ($substitution->hasQuest())
	    if ($this->i_questFails($vcard, $substitution->getQuest()))
	    	return '';
	
        if ($substitution->iterates())
		return $this->i_processIteration($vcard, $substitution);

        $value = '';
	// If the key references a field we need to look up, do it.
	if ($substitution->shouldLookUp())
	    $value = $this->i_processLookUp( $vcard, $substitution,
	    		                     $iterOver, $iterItem );
	
	if (empty($value) && $substitution->hasFragment())
	    $value = $this->i_processFragment( $vcard,
	    		  $substitution->getFragment(), $iterOver, $iterItem );
	return $value;
    } //i_processSubstitution()
    
    /**
     * Return true if the re-Quested property is empty, false otherwise.
     * @param vCard $vcard not null.
     * @param string $questFor The name of the property to check. Not null.
     * @return boolean
     */
    private function i_questFails(vCard $vcard, $questFor)
    {
    	assert(null !== $vcard);
    	assert(null !== $questFor);
    	assert(is_string($questFor));
    	
    	if (empty($vcard->$questFor))
    		return true;
    	else
    		return false;
    }
    
    /**
     * Iterate over a vCard property, substituting the specified fragment
     * for each value of the property.
     * @param vCard $vcard The vcard to find the property in. Not null.
     * @param Substitution $substitution The substitution contains the property
     * to iterate over and the fragment to substitute. Not null. iterOver
     * must be non-null.
     * @return string
     */
    private function i_processIteration( vCard $vcard,
    		                         Substitution $substitution )
    {
    	assert(null !== $vcard);
    	assert(null !== $substitution);
    	
    	$iterOver = $substitution->getIterOver();
    	assert(null !== $iterOver);
    	
    	// if it is there, and is an array (multiple values), we need to
    	// handle them all.
    	if (is_array($vcard->$iterOver))
    	{
    		$iterStrings = array();
    		foreach($vcard->$iterOver as $iterItem)
    			array_push( $iterStrings,
    					$this->i_processFragment( $vcard,
    							$substitution->getFragment(),
    							$iterOver, $iterItem ) );
    		return join(" ", $iterStrings);
    	} else if (($iterItem = $vcard->$iterOver) !== null) {
    		return $this->i_processFragment( $vcard,
    				$substitution->getFragment(),
    				$iterOver, $iterItem );
    	} else {
    		return '';
    	}
    	 
    } //i_processIteration()
    
    /**
     * Look-up and return the requested property value or magic value.
     * @param vCard $vcard The vcard to find the property in.
     * @param string $lookUp The name of the property or magic value. Not null.
     * @param string $iterOver The name of a property being iterated over, or
     * null.
     * @param unknown $iterItem The current value of the property being
     * iterated over, or null.
     * @return string
     */
    private function i_processLookUp( vCard $vcard, Substitution $substitution,
    		                      $iterOver, $iterItem )
    {
	assert(null !== $vcard);
	assert(null !== $substitution);
	assert($substitution->shouldLookUp());
	
	$lookUpProperty = $substitution->getLookUp()['property'];
	assert(null !== $lookUpProperty);
	
	$value = '';
	
	if ($substitution->isMagic())
	{
	    if ($lookUpProperty == "_id")
		$value = urlencode($vcard->fn);
	    else if ($lookUpProperty == "_rawvcard")
		$value .= $vcard;
	    else
	    	assert(false, 'bad magic:' . $lookUpProperty);
	} else if ($substitution->lookUpIsStructured()) {
    	    $lookUpField = $substitution->getLookUp()['field'];
    	    
    	    // if we are already processing a list of #items...
    	    if ($lookUpProperty == $iterOver)
    	    {
    		$value = $iterItem[$lookUpField];
    	    } else {
    		// otherwise look it up and *take first one found*
    		// NOTE: vcard->__get can be fragile.
    		$items = $vcard->$lookUpProperty;
    		if (!empty($items))
    		    $value = htmlspecialchars(
    		        array_key_exists($lookUpField, $items[0])
    			? $items[0][$lookUpField] : ''
    			);
    	    }
    	} else if ($iterOver == $lookUpProperty) {
    	    $value = htmlspecialchars($iterItem);
    	} else {
    	    $items = $vcard->$lookUpProperty;
    	    if (!empty($items))
    	    {
                if (is_array($items))
    		    $value = htmlspecialchars(implode(" ", $items));
    		else
    		    $value = htmlspecialchars($items);
    	    }
    	}
    	return $value;
    } // i_processLookup()
    
    /**
     * Process and return the requested fragment, making further substitutions
     * as necessary.
     * @param vCard $vcard The card to look up values in. Not null.
     * @param string $fragmentKey The key to the fragment to output, not null.
     * @param string $iterOver The name of any property being iterated over,
     * or null.
     * @param string $iterItem The current value of any property being iterated
     * over, or null.
     * @return string
     */
    private function i_processFragment( vCard $vcard, $fragmentKey, $iterOver=null,
    		                        $iterItem=null)
    {
    	assert(null !== $vcard);
    	assert(null !== $fragmentKey);
    	assert(is_string($fragmentKey));
    	
    	$fragment = $this->i_findFragment($fragmentKey);
    	$value = '';
    	
	if (null !== $fragment)
	{
            $low = 0;
	
	    while(($high = strpos($fragment, '{{', $low)) !== false)
	    {
	    	// Strip and output until we hit a template marker
		$value .= substr($fragment, $low, $high - $low);
	
		// strip the front marker
		$low = $high + 2;
		$high = strpos($fragment, '}}', $low);
	
		// Remove and process the new marker
		$newSubstitution = Substitution::fromText(substr($fragment, $low, $high - $low));
		$high += 2;
		$low = $high;
		$value .= self::i_processSubstitution( $vcard, $newSubstitution,
				$iterOver, $iterItem );
            }
	    $value .= substr($fragment, $low);
	} // if fragment
	return $value;
    } //i_processFragment()
} // Template

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
    public function getFragment() {return $this->fragment;}
    public function hasFragment() {return $this->fragment !== null;}
	    
    /**
     * The name of the vCard Property this substitution is contingent on,
     * or null;
     * @var string
     */
    private $quest = null;
    public function getQuest() {return $this->quest;}
    public function hasQuest() {return $this->quest !== null;}
    
    /**
     * @see getLookUp()
     * @var array;
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
     * @param unknown $fragment
     * @return \vCardTools\Substitution
     */
    public static function fromFragment($fragment)
    {
    	$substitution = new Substitution();
    	$substitution->fragment = $fragment;
    	return $substitution;
    }
}
?>
