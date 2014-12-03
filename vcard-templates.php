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
 * The processor works from a collection of named html fragments, starts
 * with the entry 'vcard', and recursively builds the output string.
 *  
 * Each entry is itself a template: a named piece of html output. It is best
 * if you can have each template be a complete tag (e.g. div or span) or
 * attribute so that there are no errors with unmatched closing tags.
 * output_vcard() starts with the "vcard" entry.
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
 * The key can have multiple key parts separated by commas to control
 * how the template is substituted. e.g. "{{email_block,?email}}". Extra
 * space around the key parts is ignored, so "{{email_block, ?email}}" will do
 * the same thing.
 *
 * The simplest key part is just a template name as with
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
 * If the key part starts with a pound sign ("#"), then the template will
 * be substituted once *for each value* of the vcard field named after the #.
 * So, if tell_span is defined as '<span class="tel">{{!tell}}</span>',
 * then '<div class="tel_block">{{tell_span, #tel}}</div>' might produce:
 * '<div class="tel_block"><span class="tel">555-1212</span> <span class="tel">999-4121</span></div' or it
 * might produce just '<div class="tel_block"></div>'. Using a # with a
 * ? somewhere up the tree gives a lot of control over what structural html
 * tags you produce. This default template has examples.
 *
 * Lastly, you can combine a ! key part and a template name. If the field
 * is not there, the named template will be substituted instead.
 * "{{!email, no_email}}" will either substitute the contents of the email
 * vcard field(s) if it is there OR it will look up the no_email template and
 * process it instead.
 *
 * Order of subkeys does not matter: "{{my_template, ?email}}" and
 * "{{?email, my_template}}" do the same thing.
 * 
 * Subkeys starting with an underscore *are reserved*, as are subkeys beginning
 * with a percent sign.
 *
 * Do not edit this template here. Create your own similar template,
 * name it something else, and pass it to output_vcard().
 * If you pass your own template, this default template will
 * be ignored.
 * You can then create a template which will output as a table,
 * for instance, instead of divs and spans, or will output just summary
 * information. Build slowly and test a piece at a time.
 *
 * As an aid to making your own templates, _fallback is treated specially.
 * When you set _fallback in your template to be another template array,
 * the template processor will look in _fallback for any definitions it is
 * missing. You can then add only those definitions to your template that you
 * need to change and let output_vcard fallback to these templates for
 * everything else.
 *
 * !_id and !_rawvcard are magic: they return a urlencoded version of fname
 * (suitable for using in an href for the whole vcard) and a raw text
 * export of the vcard, respectively.
 *
 * WARNING: Using multiple similar key parts in the same key has undefined
 * results. In other words, "{{my_template, ?email, ?adr}}" or
 * "{{!email, !role}}" or {{template1, template2}} may do something,
 * may cause an error, or may hatch chickens. It also may do something different
 * in future versions.
 */
class Template
{
  /**
   * Default html output template using divs and spans.
   */
  static public $defaultFragments = [
	"vcard" 
		=>	'<div id="{{!_id}}" class="vcard" {{role_attrib, ?kind}}>{{content}}</div>',
        "role_attrib"
		=>	'role="{{!kind}}"',
	"content"
		=>	'{{prod_id_span}} {{fn_span}} {{graphics_block}} {{n_block,?n}} {{title_block,?title}} {{role_block,?role}} {{orgs_block,?org}} {{note_block,?note}} {{contact_block}} {{category_block,?categories}} {{raw_block}}',
	"prod_id_span"
		=>	'<span class="prodid" hidden>{{!prodid}}</span>',
	"graphics_block"
		=>	'<div class="graphics">{{photo_tag,#photo}} {{logo_tag,#logo}}</div>',
	"photo_tag"
		=>	'<img class="photo" src="{{!photo}}" alt="{{!fn}} photo" />',
	"logo_tag"
		=>	'<img class="logo" src="{{!logo}}" alt="{{!org Name}} logo" />',
        "fn_span"
		=>	'<span class="fn">{{fn_href}}{{!fn}}{{fn_href_trailing}}</span>',
	"fn_href"
		=>	'<a role="vcardurl" href="{{fn_href_url}}"><!-- Must match with closing anchor! -->',
	"fn_href_trailing"
		=>	'</a><!-- Must match with fn_href! -->',
	"fn_href_url"
		=>	'{{!url}}',
	"n_block"
		=>	'<div class="n">{{prefix_span}} {{givenname_span}} {{addit_name_span}} {{familyname_span}} {{suffix_span}}</div>',
	"prefix_span"
		=>      '<span class="prefix">{{!n Prefixes}}</span>',
	"givenname_span"
		=>	'<span class="givenname">{{!n FirstName}}</span>',
	"addit_name_span"
		=>	'<span class="additionalname">{{!n AdditionalNames}}</span>',
	"familyname_span"
		=>	'<span class="familyname">{{!n LastName}}</span>',
	"suffix_span"
		=>	'<span class="suffix">{{!n Suffixes}}</span>',
	"title_block"
		=>	'<div class="title">{{!title}}</div>',
	"role_block"
		=>	'<div class="role">{{role}}</div>',
	"contact_block"
		=>	'<div class="contact">{{email_block,?email}} {{tel_block,?tel}} {{adrs_block,?adr}}</div>',
	"email_block"
		=>	'<div class="emails">{{email_span,#email}}</div>',
	"email_span"
		=>	'<span class="email"><a href="mailto:{{!email}}">{{!email}}</a></span>',
	"adrs_block"
		=>	'<div class="adrs">{{adr_block,#adr}}</div>',
	"adr_block"
		=>	'<div class="adr">{{street_address_span}} {{locality_span}} {{region_span}} {{postal_code_span}} {{country_span}}</div>',

	"street_address_span"
		=>	'<span class="streetaddress">{{!adr StreetAddress}}</span>',
	"locality_span"
		=>	'<span class="locality">{{!adr Locality}}</span>',
	"region_span"
		=>	'<span class="region">{{!adr Region}}</span>',
	"postal_code_span"
		=>	'<span class="postalcode">{{!adr PostalCode}}</span>',
	"country_span"
		=>	'<span class="country">{{!adr Country}}</span>',
	"orgs_block"
		=>	'<div class="orgs">{{org_block,#org}}</div>',
	"org_block"
		=>	'<div class="org">{{org_name_span}} {{org_unit1_span}} {{org_unit2_span}}</div>',
	"org_name_span"
		=>	'<span class="name">{{!org Name}}</span>',
	"org_unit1_span"
		=>	'<span class="unit">{{!org Unit1}}</span>',
	"org_unit2_span"
		=>	'<span class="unit">{{!org Unit2}}</span>',
	"raw_block"
		=>	'<pre class="vcardraw" hidden>{{!_rawvcard}}</pre>',
	"note_block"
		=>	'<div class="notes">{{note_span,#note}}</div>',
	"note_span"
		=>	'<span class="note">{{!note}}</span>',
	"tel_block"
		=>	'<div class="tels">{{tel_span,#tel}}</div>',
	"tel_span"
		=>	'<span class="tel">{{!tel}}</span>',
	"category_block"
		=>	'<div class="categories">{{category_span,#categories}}</div>',
	"category_span"
		=>	'<span class="category">{{!categories}}</span>'
	];

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
    
    /**
     * The default Template instance.
     * @var Template
     */
    static private $defaultTemplate;
    
    /**
     * Return the default Template instance.
     * @return \vCardTools\Template
     */
    static public function getDefaultTemplate()
    {
    	if (null === self::$defaultTemplate)
    		self::$defaultTemplate = new Template(self::$defaultFragments);
    	return self::$defaultTemplate;
    }
    
    /**
     * Create a new template.
     * @param array $fragments A an array of named html fragments use to output
     * a vCard. Not null.
     * @param Template $fallback Another Template instance to fall back to for
     * any keys not found in $fragments. Often, this should be
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

        return $this->i_process($vcard, 'vcard');
    } //output_vcard()
	
    /**
     * Parses a template substitution key in a template.
     * @arg string key The key to tokenize. Not null.
     * @return array An associative array containing "key": the full original key,
     * "quest": the part after any question mark indicating what field the
     * substitution depends on, "lookup": the bang-field indicating the contents of
     * a field to look up and substitute, and "iter_over" contains the part after a
     * hash mark which indicates a field with multiple instances we are to iterate
     * over.
     */
    static private function i_parse_key($key)
    {
	assert($key !== null);
	assert(is_string($key));

	$key_struct = array("key" => $key);
	
	// separate by commas, ignore leading and trailing space
	$key_parts = array_map("trim", explode(",", $key));
	
	foreach ($key_parts as $part)
	{
	    // if we have multiples of the same type, last one clobbers
	
	    // figure out what it is and store it
	    if (substr($part, 0, 1) == "!")
		$key_struct["look_up"] = substr($part, 1);
	    else if (substr($part, 0, 1) == "?")
		$key_struct["quest"] = substr($part, 1);
	    else if (substr($part, 0, 1) == "#")
		$key_struct["iter_over"] = substr($part, 1);
	    else
		$key_struct['fragment'] = $part;
	}
		return $key_struct;
    } // i_parse_key()
	
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
     * @arg string $key The current fragment entry being processed.
     * Not null.
     * @arg string $iter_over The current vcard field being iterated over,
     * if any.
     * @arg mixed $iter_item The current element of the vcard field being iterated over,
     *   if any.
     * @return string The portion of the HTML tree output.
     */
    private function i_process( vCard $vcard, $key, $iter_over="",
    		                $iter_item=null )
    {
	assert(null !== $vcard);
	assert(null !== $this->fragments);
	assert(null !== $key);
	assert(is_string($key));
	    
	$key_struct = self::i_parse_key($key);
	
	// if we are conditional on a field and it isn't there, bail.
	if (!empty($key_struct["quest"]))
	{
	    $quest_for = $key_struct["quest"];
	    $quest_item = $vcard->$quest_for;
	    if (empty($quest_item))
	        return "";
	} // if quest
	
	$value = "";
	
	// If we are supposed to iterate over a field, do it and then bail
	// Actual output will be built in the sub-calls
        if (!empty($key_struct["iter_over"]))
	{
            $iter_over = $key_struct["iter_over"];
	    $iter_items = $vcard->$iter_over;
	
	    // if it is there, and is an array (multiple values), we need to
	    // handle them all.
	    if (is_array($iter_items))
	    {
	        $iter_strings = array();
		foreach($iter_items as $iter_item)
		    array_push( $iter_strings,
			        $this->i_process( $vcard,
			    	                 $key_struct["fragment"],
			    	        	 $iter_over, $iter_item ) );
		return join(" ", $iter_strings);
            }
	} // if iter_over
	
	
	// If the key references a field we need to look up, do it.
	if (!empty($key_struct["look_up"]))
	{
	    $look_up = $key_struct["look_up"];
	
	    // if there is a space in the key, it's a structured element
	    $compound_key = explode(" ", $look_up);
	    if (count($compound_key) == 2)
	    {
	        // if we are already processing a list of #items...
		if ($compound_key[0] == $iter_over)
		{
                    $value = $iter_item[$compound_key[1]];
		} else {
		    // otherwise look it up and *take first one found*
	            // NOTE: vcard->__call() can be fragile.
	            $items = $vcard->$compound_key[0];
	            if (!empty($items))
	            	$value = htmlspecialchars(
			    array_key_exists($compound_key[1], $items[0])
			    ? $items[0][$compound_key[1]] : ""
			);
		}
	    } else if ($iter_over == $look_up) {
		$value = htmlspecialchars($iter_item);
            } else if ($look_up == "_id") {
		$value = urlencode($vcard->fn);
            } else if ($look_up == "_rawvcard") {
	        $value .= $vcard;
	    } else {
	        $items = $vcard->$look_up;
	        if (!empty($items))
	        {
	            if (is_array($items))
                        $value = htmlspecialchars(implode(" ", $items));
		    else
			$value = htmlspecialchars($items);
		}
            }
        } //if look_up
	
	// if we already have a value or we don't have a fragment, bail
	if (!empty($value) || !array_key_exists('fragment', $key_struct))
            return $value;
	
	// Fragment processing
	$fragment = $this->i_findFragment($key_struct['fragment']);
	if (null !== $fragment)
	{
            $low = 0;
	
	    while(($high = strpos($fragment, "{{", $low)) !== false)
	    {
	    	// Strip and output until we hit a template marker
		$value .= substr($fragment, $low, $high - $low);
	
		// strip the front marker
		$low = $high + 2;
		$high = strpos($fragment, "}}", $low);
	
		// Remove and process the new marker
		$new_key = substr($fragment, $low, $high - $low);
		$high += 2;
		$low = $high;
		$value .= self::i_process( $vcard, $new_key, $iter_over,
				$iter_item );
            }
	    $value .= substr($fragment, $low);
	} // if template
	
	return $value;
    } //i_process()
} // Template
?>
