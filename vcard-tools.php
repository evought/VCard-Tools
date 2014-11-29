<?php
/**
 * A toolbox for manipulating the vcard and related input data.
 * Uses the vcard.php class and vcard.sql schema. Includes default templates
 * in vcard-templates.php.
 * This tool contains functions for storing vcard class to and reading from
 * the database. There is also a templating engine for creating HTML from
 * a vcard.
 * @author Eric Vought evought@pobox.com 2014-11-16
 * @copyright Eric Vought 2014, Some rights reserved.
 * @license MIT http://opensource.org/licenses/MIT
 */

require_once "vcard.php";
require_once "vcard-templates.php";

class VCardDB
{
// FIXME: Add a method to fetch all contact IDs (without loading the records).
    
    // The product id we will use when creating new vcards
    const VCARD_PRODUCT_ID = '-//VCard Tools//1.0//en';

    /**
     * The PDO connection used for storage and retrieval.
     */
    private $connection;

    /**
     * Retrieve the current PDO connection.
     */
    public function getConnection() {return $this->connection;}

    /**
     * @arg $connection A PDO connection to read from/write to. Not null. Caller
     * retains responsibility for connection, but this class shall ensure that
     * the reference is cleared upon clean-up.
     */
    public function __construct(PDO $connection)
    {
        assert(!empty($connection));
        $this->connection = $connection;
    }

    /**
     * Make sure that $connection is cleared.
     */
    public function __destruct()
    {
        unset($this->connection);
    }

    /**
     * Store the whole vcard to the database, calling sub-functions to store
     * related tables (e.g. address) as necessary.
     * Returns the new contact_id
     * FIXME: None of these routines deal with ENCODING.
     */
    function store(VCard $vcard)
    {
        assert(!empty($this->connection));

        $contact_id = $this->i_storeJustContact($vcard);

        // FIXME: in case of multiple calls, can optimize by reusing prepared SQL.
        foreach ($vcard->org as $org)
        {
	    $this->i_storeOrg($org, $contact_id);
        }

        foreach ($vcard->adr as $adr)
        {
	    $this->i_storeAddress($adr, $contact_id);
        }

        foreach ($vcard->note as $note)
        {
	    $this->i_storeNote($note, $contact_id);
        }

        foreach (["photo", "logo", "sound"] as $data_field)
        {
	    foreach ($vcard->$data_field as $data_item)
    	    {
                $this->i_storeDataProperty($data_field, $data_item, $contact_id);
    	    }
        }

        foreach ($vcard->tel as $tel)
        {
	        $this->i_storeTel($tel, $contact_id);
        }

        foreach ($vcard->email as $item)
        {
	        $this->i_storeEmail($item, $contact_id);
        }

        foreach ($vcard->categories as $item)
        {
	        $this->i_storeCategory($item, $contact_id);
        }

        return $contact_id;
    } // store()


    /**
     * Saves the vcard contact data to the database, returns the id of the
     * new connection record.
     * Stores JUST the info from the CONTACT table itself, no sub-tables.
     */
    private function i_storeJustContact(VCard $vcard)
    {
        assert(!empty($this->connection));

        $vcard->setFNAppropriately();

        $stmt = $this->connection->prepare("INSERT INTO CONTACT (KIND, FN, N_PREFIX, N_GIVEN_NAME, N_ADDIT_NAME, N_FAMILY_NAME, N_SUFFIX, NICKNAME, BDAY, TZ, GEO_LAT, GEO_LONG, ROLE, TITLE, REV, UID, URL) VALUES (:kind, :fn, :n_Prefixes, :n_FirstName, :n_AdditionalNames, :n_LastName, :n_Suffixes, :nickname, :bday, :tz, :geolat, :geolon, :role, :title, :rev, :uid, :url)");

        $stmt->bindValue( ':kind', empty($vcard->kind)
                                ? null : $vcard->kind, PDO::PARAM_STR );
        $stmt->bindValue(':fn', $vcard->fn);

        // NOTE: The VCard spec allows a contact to have multiple names.
        // In practice, no implementations seem to allow this, so we ignore
        // it (for now). That means we have to deal with the oddity that n
        // is actually an array below.
        $n = empty($vcard->n) ? array() : $vcard->n[0];

        foreach([ 'Prefixes', 'FirstName', 'AdditionalNames', 'LastName',
              'Suffixes' ] as $n_key)
        {
            $n_value = empty($n[$n_key]) ? null : $n[$n_key];
            $stmt->bindValue(':n_'.$n_key, $n_value, PDO::PARAM_STR);
        }

        $stmt->bindValue( ':nickname',
        		  empty($vcard->nickname) 
        		          ? null : $vcard->nickname[0],
        		  PDO::PARAM_STR );
        
        if (empty($vcard->bday))
           $stmt->bindValue( ':bday', null, PDO::PARAM_NULL);
        else 
           $stmt->bindValue( ':bday', $vcard->bday);
        
        $stmt->bindValue(':tz', empty($vcard->tz) ? null : $vcard->tz[0]);

        $geo = $vcard->geo;
        if (empty($geo))
        {
            $stmt->bindValue(':geolat', null, PDO::PARAM_NULL);
            $stmt->bindValue(':geolon', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':geolat', $geo[0]["Lattitude"]);
            $stmt->bindValue(':geolon', $geo[0]["Longitude"]);
        }

        $stmt->bindValue( ':role', empty($vcard->role)
                          ? null : $vcard->role[0], PDO::PARAM_STR );
        $stmt->bindValue( ':title', empty($vcard->title)
        		  ? null : $vcard->title[0], PDO::PARAM_STR );

        $stmt->bindValue( ':rev', empty($vcard->rev)
        		  ? null : $vcard->rev, PDO::PARAM_STR );
        $stmt->bindValue( ':uid', empty($vcard->uid)
        		  ? null : $vcard->uid, PDO::PARAM_STR );
        $stmt->bindValue( ':url', empty($vcard->url)
        		  ? null : $vcard->url[0], PDO::PARAM_STR );
    
        $stmt->execute();
        $contact_id = $this->connection->lastInsertId();

        return $contact_id;
    } // i_storeJustContact()

    /**
     * Saves the vcard org data to the database.
     * @arg $org The VCard org record to write out. Not empty.
     * @arg $contact_id The ID of the contact the org is to be attached to.
     * Numeric.
     * @return The id of the new org record in the database.
     * FIXME: does not handle type property in any way.
     */
    private function i_storeOrg(Array $org, $contact_id)
    {
        assert(!empty($org));
        assert(is_numeric($contact_id));
        assert(!empty($this->connection));

        $stmt = $this->connection->prepare("INSERT INTO CONTACT_ORG (NAME, UNIT1, UNIT2) VALUES (:Name, :Unit1, :Unit2)");

        foreach(['Name', 'Unit1', 'Unit2'] as $key)
        {
            $value = empty($org[$key]) ? null : $org[$key];
            $stmt->bindValue(':'.$key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        $org_id = $this->connection->lastInsertId();

        $stmt = $this->connection->prepare("INSERT INTO CONTACT_REL_ORG (CONTACT_ID, ORG_ID) VALUES (:contact_id, :org_id)");
        $stmt->bindValue(":contact_id", $contact_id);
        $stmt->bindValue(":org_id", $org_id);
        $stmt->execute();

        return $org_id;
    } // i_storeOrg()

    /**
     * Saves the vcard address data to the database, returns the id of the new
     * address record. Takes the vcard adr record and the id of the contact to
     * connect it to.
     * @arg $adr An array with the ADR data from VCard. Not empty.
     * @arg $contact_id The ID of the contact the adr is to be attached to.
     * Numeric.
     * @return The ID of the new ADR database record.
     *
     * FIXME: does not handle type property in any way.
     */
    private function i_storeAddress(Array $adr, $contact_id)
    {
        assert(!empty($adr));
        assert(is_numeric($contact_id));
        assert(!empty($this->connection));

        $stmt = $this->connection->prepare("INSERT INTO CONTACT_MAIL_ADDRESS (STREET, LOCALITY, REGION, POSTAL_CODE, COUNTRY) VALUES (:StreetAddress, :Locality, :Region, :PostalCode, :Country)");

        foreach( [ 'StreetAddress', 'Locality', 'Region',
                   'PostalCode', 'Country' ]
                 as $key )
        {
            $value = empty($adr[$key]) ? null : $adr[$key];
            $stmt->bindValue(':'.$key, $value, PDO::PARAM_STR);
        }

        $stmt->execute();
        $adr_id = $this->connection->lastInsertId();

        $stmt = $this->connection->prepare("INSERT INTO CONTACT_REL_MAIL_ADDRESS (CONTACT_ID, MAIL_ADDRESS_ID) VALUES (:contact_id, :adr_id)");
        $stmt->bindValue(":contact_id", $contact_id);
        $stmt->bindValue(":adr_id", $adr_id);
        $stmt->execute();

        return $adr_id;
    } // i_storeAddress()

    /**
     * Store the data fields (photo, logo, sound)
     * Currently only stores URLs, not blobs
     * @arg $data_field Must be one of: photo, logo, sound.
     * @arg $url The URL to the data to store. Not empty.
     * @arg $contact_id The ID of the contact to attach this to. Numeric.
     * @return The ID of the new Data record in the database.
     */
    private function i_storeDataProperty($data_field, $url, $contact_id)
    {
        assert(in_array($data_field, ['photo', 'logo', 'sound']));
        assert(!empty($url));
        assert(is_numeric($contact_id));
        assert(!empty($this->connection));

        $stmt = $this->connection->prepare("INSERT INTO CONTACT_DATA (DATA_NAME, URL) VALUES (:data_name, :url)");

        $stmt->bindValue(":data_name", $data_field);
        $stmt->bindValue(":url", $url);
        $stmt->execute();
        $data_id = $this->connection->lastInsertId();

        $stmt = $this->connection->prepare("INSERT INTO CONTACT_REL_DATA (CONTACT_ID, CONTACT_DATA_ID) VALUES (:contact_id, :data_id)");
        $stmt->bindValue(":contact_id", $contact_id);
        $stmt->bindValue(":data_id", $data_id);
        $stmt->execute();

        return $data_id;
    } // i_storeDataProperty()

    /**
     * Store the note property.
     * @arg $note The note property to store. Non-empty string.
     * @arg $contact_id The ID of the contact to attach the record to.
     * @return The ID of the new NOTE record.
     */
    private function i_storeNote($note, $contact_id)
    {
        assert(!empty($note));
        assert(is_string($note));
        assert(is_numeric($contact_id));
        assert(!empty($this->connection));

        $stmt = $this->connection->prepare("INSERT INTO CONTACT_NOTE (NOTE) VALUES (:note)");

        $stmt->bindValue(":note", $note);
        $stmt->execute();
        $note_id = $this->connection->lastInsertId();

        $stmt = $this->connection->prepare("INSERT INTO CONTACT_REL_NOTE (CONTACT_ID, NOTE_ID) VALUES (:contact_id, :note_id)");
        $stmt->bindValue(":contact_id", $contact_id);
        $stmt->bindValue(":note_id", $note_id);
        $stmt->execute();

        return $note_id;
    } // i_storeNote()

    /**
     * Store the Tel record.
     * @arg $tel The value to store. Non-empty string.
     * @arg $contact_id The ID of the contact record to associate it with.
     * Numeric.
     * @return The ID of the new TEL record.
     */
    private function i_storeTel($tel, $contact_id)
    {
        assert(!empty($tel));
        assert(is_string($tel));
        assert(is_numeric($contact_id));
        assert(!empty($this->connection));

        $stmt = $this->connection->prepare("INSERT INTO CONTACT_PHONE_NUMBER (LOCAL_NUMBER) VALUES (:number)");

        $stmt->bindValue(":number", $tel);
        $stmt->execute();
        $phone_id = $this->connection->lastInsertId();

        $stmt = $this->connection->prepare("INSERT INTO CONTACT_REL_PHONE_NUMBER (CONTACT_ID, PHONE_NUMBER_ID) VALUES (:contact_id, :phone_id)");
        $stmt->bindValue(":contact_id", $contact_id);
        $stmt->bindValue(":phone_id", $phone_id);
        $stmt->execute();

        return $phone_id;
    } // i_storeTel()

    /**
     * Store the email property.
     * @param unknown $email The email address to store (string, not empty).
     * @param unknown $contact_id The ID of the CONTACT record to associate this with. Numeric.
     * @return The ID of the new EMAIL record.
     * FIXME: does not handle TYPE paramters.
     */
    private function i_storeEmail($email, $contact_id)
    {
        assert(!empty($email));
        assert(is_string($email));
        assert(is_numeric($contact_id));
        assert(!empty($this->connection));
    	
        $stmt = $this->connection->prepare("INSERT INTO CONTACT_EMAIL (EMAIL_ADDRESS) VALUES (:email)");

        $stmt->bindValue(":email", $email);
        $stmt->execute();
        $new_id = $this->connection->lastInsertId();

        $stmt = $this->connection->prepare("INSERT INTO CONTACT_REL_EMAIL (CONTACT_ID, EMAIL_ID) VALUES (:contact_id, :new_id)");
        $stmt->bindValue(":contact_id", $contact_id);
        $stmt->bindValue(":new_id", $new_id);
        $stmt->execute();
        
        return $new_id;
    } // i_storeEmail()

    /**
     * Store a categories property.
     * @param unknown $category The individual category to store. String, not empty.
     * @param unknown $contact_id The ID of the CONTACT record to associate this with.
     * @return The ID of the new CATEGORIES record.
     */
    private function i_storeCategory($category, $contact_id)
    {
        assert(!empty($category));
        assert(is_string($category));
        assert(is_numeric($contact_id));
        assert(!empty($this->connection));
    	
        $stmt = $this->connection->prepare("INSERT INTO CONTACT_CATEGORIES(CATEGORY_NAME) VALUES (:category)");

        $stmt->bindValue(":category", $category);
        $stmt->execute();
        $new_id = $this->connection->lastInsertId();

        $stmt = $this->connection->prepare("INSERT INTO CONTACT_REL_CATEGORIES (CONTACT_ID, CATEGORY_ID) VALUES (:contact_id, :new_id)");
        $stmt->bindValue(":contact_id", $contact_id);
        $stmt->bindValue(":new_id", $new_id);
        $stmt->execute();
        
        return $new_id;
    } // i_storeCategory()

    /**
     * Fetch all vcards from the database.
     * @arg $kind If kind is given, only fetch those of that kind (e.g.
     * organization).
     * @return An array of VCards keyed by contact id.
     */
    public function fetchAll($kind='%')
    {
    	assert(isset($this->connection));
    	
    	$stmt = $this->connection->prepare('SELECT * FROM CONTACT WHERE IFNULL(KIND, \'\') LIKE :kind');
    	$stmt->bindValue(":kind", $kind);

        $stmt->execute();
        $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);

        $vcards = array();

        while ($row = $stmt->fetch())
        {
	    $vcards[$row["CONTACT_ID"]] = $this->i_fetchVCard($row);
        } // while

        $stmt->closeCursor();

        return $vcards;
    } // fetchAll()

    /**
     * Returns all vcards where the fn or categories match the requested search
     * string.
     * @arg $searchString The pattern to search for (SQL matching rules). If
     * omitted, match all cards.
     * @arg $kind If kind is given, return only cards of that kind (e.g.
     * organization).
     */
    public function search($searchString='%', $kind='%')
    {
    	assert(isset($this->connection));
    	
        $stmt = $this->connection->prepare('SELECT CONTACT_ID FROM CONTACT WHERE FN LIKE :searchString AND IFNULL(KIND,\'\') LIKE :kind');
        $stmt->bindValue(":kind", $kind);
        $stmt->bindValue(":searchString", $searchString);
        $stmt->execute();

        $contactIDs = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();
 
        $contactIDs += $this->fetchIDsForCategory( $searchString,
        		$kind="%" );
        if (empty($contactIDs)) return array();

        return $this->fetchByID($contactIDs);
    } // search()

    /**
     * Returns a list of all contact_ids where the org.name parameter matches
     * the query.
     * @arg $organizationName The name of the organization to search for. May
     * be a SQL pattern. String, not empty.
     * @arg $kind If kind is provided, limit results to a specific Kind (e.g.
     * individual.
     * @return The list of contact ids. Actual VCards are not fetched.
     */
    public function fetchIDsForOrganization($organizationName, $kind="%")
    {
    	assert(isset($this->connection));
    	assert(!empty($organizationName));
    	assert(is_string($organizationName));
    	
        $stmt = $this->connection->prepare("SELECT ORG_ID FROM CONTACT_ORG WHERE NAME LIKE :organizationName");
        $stmt->bindValue(":organizationName", $organizationName);
        $stmt->execute();
        $orgIDs = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        if (empty($orgIDs)) return array();

        // HACK: PDO does not support bind an array to an IN parameter, so
        // we just add the list as a string to the query. We aren't re-executing
        // the query and there is no worry of SQL injection here, so it doesn't
        // matter but it's clunky.
        $stmt = $this->connection->prepare("SELECT DISTINCT CONTACT_ID FROM CONTACT_REL_ORG WHERE ORG_ID IN ("
	    . implode(",", $orgIDs) . ")");
        $stmt->execute();
        $contactIDs = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        return $this->filterIDsByKind($contactIDs, $kind);
    } // fetchIDsForOrganization()

    /**
     * Returns only the contact_ids from the input list where kind matches.
     * @arg $contactIDs The list of contact IDs to filter. An array
     * (non-empty) of numerics.
     * @arg $kind The kind of record desired (e.g. individual)
     * @return A new list of any IDs that match.
     */
    public function filterIDsByKind(Array $contactIDs, $kind)
    {
    	assert(isset($this->connection));
    	assert(!empty($contactIDs));
    	assert(is_string($kind));
    	
        $stmt = $this->connection->prepare("SELECT CONTACT_ID FROM CONTACT WHERE CONTACT_ID IN ("
	. implode(",", $contactIDs) . ") AND KIND LIKE :kind");
        $stmt->bindValue(":kind", $kind);
        $stmt->execute();
        $contactIDs = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        return $contactIDs;    
    } // filterIDsByKind()

    /**
     * Returns a list of all contact_ids in a given category.
     * @arg $category The string representing the category to search for.
     * May be a SQL pattern. Not empty.
     * @arg $kind If given, the kind (e.g. individual) to filter by.
     * @return An array of contact IDs. No VCards are fetched.
     */
    public function fetchIDsForCategory($category, $kind="%")
    {
    	assert(isset($this->connection));
    	assert(!empty($category));
    	assert(is_string($category));
    	
        $stmt = $this->connection->prepare("SELECT CATEGORY_ID FROM CONTACT_CATEGORIES WHERE CATEGORY_NAME LIKE :category");
        $stmt->bindValue(":category", $category);
        $stmt->execute();
        $categoryIDs = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        if (empty($categoryIDs)) return array();

        // HACK: PDO does not support bind an array to an IN parameter, so
        // we just add the list as a string to the query. We aren't re-executing
        // the query and there is no worry of SQL injection here, so it doesn't
        // matter but it's clunky.
        $stmt = $this->connection->prepare("SELECT DISTINCT CONTACT_ID FROM CONTACT_REL_CATEGORIES WHERE CATEGORY_ID IN ("
	    . implode(",", $categoryIDs) . ")");
        $stmt->execute();
        $contactIDs = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        return $this->filterIDsByKind($contactIDs, $kind);
    } // fetchIDsForCategory()

    /**
     * Retrieve VCard records for the given Contact IDs.
     * @param unknown $contactIDs
     * @return An array of VCards indexed by contact ID.
     */
    public function fetchByID(Array $contactIDs)
    {
    	assert(isset($this->connection));
    	assert(!empty($contactIDs));
    	
        $vcards = array();
        foreach($contactIDs as $contactID)
        {
	    $vcards[$contactID] = $this->fetchOne($contactID);
        }

        return $vcards;
    } // fetchByID()

    /**
     * Fetch a single vcard given a contact_id.
     * @arg $contactID The ID of the record to fetch. Numeric, not empty.
     * @return The completed vcard or false if none found.
     */
    public function fetchOne($contactID)
    {
    	assert(isset($this->connection));
    	assert(!empty($contactID));
    	assert(is_numeric($contactID));
    	
        $stmt = $this->connection->prepare("SELECT * FROM CONTACT WHERE CONTACT_ID = :contactID");
        $stmt->bindValue(":contactID", $contactID);
        $stmt->execute();
        assert($stmt->rowCount() <= 1);
                
        $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);

        $vcard = false;

        $row = $stmt->fetch();
        if ($row != false) $vcard = $this->i_fetchVCard($row);
        $stmt->closeCursor();

        return $vcard;
    } // fetchOne()

    /**
     * Internal helper to fill in details of a vcard.
     * @arg $row The associative array row returned from the db. Not empty.
     * @return The finished vcard.
     */
    protected function i_fetchVCard(Array $row)
    {
    	assert(isset($this->connection));
    	assert(!empty($row));
    	
        $vcard = new vCard();
        $contactID = $row["CONTACT_ID"];

        $simpleCols = [ 'KIND', 'FN', 'NICKNAME', 'BDAY', 'TITLE', 'ROLE',
                        'REV', 'UID', 'URL', 'VERSION' ];
        foreach ($simpleCols as $col)
        {
            if (!empty($row[$col])) $vcard->$col($row[$col]);
        }
                
        if (!empty($row["N_PREFIX"])) $vcard->n($row["N_PREFIX"], "Prefixes");
        if (!empty($row["N_GIVEN_NAME"]))
		$vcard->n($row["N_GIVEN_NAME"], "FirstName");
        if (!empty($row["N_ADDIT_NAME"]))
		$vcard->n($row["N_ADDIT_NAME"], "AdditionalNames");
        if (!empty($row["N_FAMILY_NAME"]))
		$vcard->n($row["N_FAMILY_NAME"], "LastName");
        if (!empty($row["N_SUFFIX"])) $vcard->n($row["N_SUFFIX"], "Suffixes");
        
        if (!empty($row["GEO_LAT"])) $vcard->geo($row["GEO_LAT"], "Lattitude");
        if (!empty($row["GEO_LON"])) $vcard->geo($row["GEO_LON"], "Longitude");
        
	$vcard->prodid(self::VCARD_PRODUCT_ID);

        $this->i_fetchOrgsForVCard($vcard, $contactID);
        $this->i_fetchAdrsForVCard($vcard, $contactID);
        $this->i_fetchNotesForVCard($vcard, $contactID);
        $this->i_fetchDataForVCard($vcard, $contactID);
        $this->i_fetchTelsForVCard($vcard, $contactID);
        $this->i_fetchEmailsForVCard($vcard, $contactID);
        $this->i_fetchCategoriesForVCard($vcard, $contactID);

        return $vcard;
    } // i_fetchVCard()

    /**
     * Fetch and attach all org records for a vcard, returning the card.
     * @arg $vcard The card to attach records to. Not null.
     * @arg $contactID The ID of the contact record ORG records are attached
     * to. Numeric, not empty.
     * @return The vcard.
     */ 
    private function i_fetchOrgsForVCard(vCard $vcard, $contactID)
    {
    	assert(isset($this->connection));
    	assert($vcard !== null);
    	assert(!empty($contactID));
    	assert(is_numeric($contactID));
    	
        $col_map = [
                     'NAME' => 'Name',
		     'UNIT1' => 'Unit1',
                     'UNIT2' => 'Unit2'
                   ];

        // Fetch a list of org records associated with the contact
        $stmt = $this->connection->prepare("SELECT ORG_ID FROM CONTACT_REL_ORG WHERE CONTACT_ID=:contactID");
        $stmt->bindValue(":contactID", $contactID);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        // Fetch each org record in turn
        $stmt = $this->connection->prepare("SELECT NAME, UNIT1, UNIT2 FROM CONTACT_ORG WHERE ORG_ID=:orgID");
        foreach ($results as $orgID)
        {
	    $stmt->bindValue(":orgID", $orgID);
	    $stmt->execute();
	    $orgRes = $stmt->fetch(PDO::FETCH_ASSOC);
	    $stmt->closeCursor();

            $org = array();
            foreach ($orgRes as $key => $value)
                if (!empty($value)) $org[$col_map[$key]] = $value;
            $vcard->org($org);
        }

        return $vcard;
    } // i_fetchOrgsForVCard()

    /**
     * Fetch and attach all ADR records for a vcard, returning the card.
     * @arg $vcard The card to attach the records to. Not null.
     * @arg $contactID The contact ID the ADR records are associated with.
     * Numeric. Not null.
     * @return The vcard the records have been attached to.
     */
    private function i_fetchAdrsForVCard(vCard $vcard, $contactID)
    {
    	assert(isset($this->connection));
    	assert($vcard !== null);
    	assert($contactID !== null);
    	assert(is_numeric($contactID));
    	
        $col_map = [
                     'STREET' => 'StreetAddress',
		     'LOCALITY' => 'Locality',
                     'REGION' => 'Region',
                     'POSTAL_CODE' => 'PostalCode',
                     'COUNTRY' => 'Country'
                   ];

        // Fetch a list of adr records associated with the contact
        $stmt = $this->connection->prepare("SELECT MAIL_ADDRESS_ID FROM CONTACT_REL_MAIL_ADDRESS WHERE CONTACT_ID=:contactID");
        $stmt->bindValue(":contactID", $contactID);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        // Fetch each adr record in turn
        $stmt = $this->connection->prepare("SELECT STREET, LOCALITY, REGION, POSTAL_CODE, COUNTRY FROM CONTACT_MAIL_ADDRESS WHERE MAIL_ADDRESS_ID=:adrID");
        foreach ($results as $adrID)
        {
	    $stmt->bindValue(":adrID", $adrID);
	    $stmt->execute();
	    $adrRes = $stmt->fetch(PDO::FETCH_ASSOC);
	    $stmt->closeCursor();

            $adr = array();
            foreach ($adrRes as $key => $value)
                if (!empty($value)) $adr[$col_map[$key]] = $value;

	    $vcard->adr($adr);
        }
        return $vcard;
    } // fetchAdrsForVCard()

    /**
     * Fetch and attach all note records for a vcard, returning the card.
     * @param vCard $vcard The card to attach the fetched records to. Not null.
     * @param unknown $contact_id The Contact ID the records will be found
     * under. Numeric, not null.
     * @return The VCard being assembled.
     */
    private function i_fetchNotesForVCard(vCard $vcard, $contactID)
    {
    	assert(isset($this->connection));
    	assert($vcard !== null);
    	assert($contactID !== null);
    	assert(is_numeric($contactID));
    	
        // Fetch a list of note records associated with the contact
        $stmt = $this->connection->prepare("SELECT NOTE_ID FROM CONTACT_REL_NOTE WHERE CONTACT_ID=:contactID");
        $stmt->bindValue(":contactID", $contactID);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        // Fetch each note record in turn
        $stmt = $this->connection->prepare("SELECT NOTE FROM CONTACT_NOTE WHERE NOTE_ID=:noteID");
        foreach ($results as $noteID)
        {
	    $stmt->bindValue(":noteID", $noteID);
	    $stmt->execute();
	    $note = $stmt->fetch(PDO::FETCH_NUM, 0);
	    $stmt->closeCursor();

	    $vcard->note($note[0]);
        }

        return $vcard;
    } // i_fetchNotesForVCard()

    /**
     * Fetch and attach all TEL records for the given contact ID.
     * @param vCard $vcard The vCard to attach fetched records to. Not null.
     * @param unknown $contact_id The ID of the contact the TEL records are
     * associated with. Numeric, not null.
     * @return The vCard being assembled.
     */
    private function i_fetchTelsForVCard(vCard $vcard, $contactID)
    {
    	assert($this->connection !== null);
    	assert($vcard !== null);
    	assert($contactID !== null);
    	assert(is_numeric($contactID));
    	
        // Fetch a list of tel records associated with the contact
        $stmt = $this->connection->prepare("SELECT PHONE_NUMBER_ID FROM CONTACT_REL_PHONE_NUMBER WHERE CONTACT_ID=:contactID");
        $stmt->bindValue(":contactID", $contactID);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        // Fetch each record in turn
        $stmt = $this->connection->prepare("SELECT LOCAL_NUMBER FROM CONTACT_PHONE_NUMBER WHERE PHONE_NUMBER_ID=:phoneID");
        foreach ($results as $phoneID)
        {
	    $stmt->bindValue(":phoneID", $phoneID);
	    $stmt->execute();
	    $phone = $stmt->fetch(PDO::FETCH_NUM, 0);
	    $stmt->closeCursor();

	    $vcard->tel($phone[0]);
        }

        return $vcard;
    } // i_fetchTelsForVCard()

    /**
     * Fetch all EMAIL records for a give contact ID and attach them.
     * @param vCard $vcard The card to attach the records to. Not null.
     * @param unknown $contactID The contact ID the EMAIL records are
     * associated with. Numeric, not null.
     * @return The vcard being assembled.
     */
    private function i_fetchEmailsForVCard(vCard $vcard, $contactID)
    {
    	assert($this->connection !== null);
    	assert($vcard !== null);
    	assert($contactID !== null);
    	assert(is_numeric($contactID));
    	
        // Fetch a list of records associated with the contact
        $stmt = $this->connection->prepare("SELECT EMAIL_ID FROM CONTACT_REL_EMAIL WHERE CONTACT_ID=:contactID");
        $stmt->bindValue(":contactID", $contactID);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        // Fetch each record in turn
        $stmt = $this->connection->prepare("SELECT EMAIL_ADDRESS FROM CONTACT_EMAIL WHERE EMAIL_ID=:id");
        foreach ($results as $id)
        {
	    $stmt->bindValue(":id", $id);
	    $stmt->execute();
	    $item = $stmt->fetch(PDO::FETCH_NUM, 0);
	    $stmt->closeCursor();

	    $vcard->email($item[0]);
        }

        return $vcard;
    } // i_fetchEmailsForVCard()

    /**
     * Fetch and attach all data records (photo, logo, sound) for a vcard,
     * returning the card.
     * @param vCard $vcard The vCard the records will be attached to. Not null.
     * @param unknown $contactID The ID of the contact data records are
     * associated with. Numeric, not null.
     * @return The vcard being assembled.
     */
    private function i_fetchDataForVCard(vCard $vcard, $contactID)
    {
    	assert($this->connection !== null);
    	assert($vcard !== null);
    	assert($contactID !== null);
    	assert(is_numeric($contactID));
    	
        // Fetch a list of data records associated with the contact
        $stmt = $this->connection->prepare('SELECT CONTACT_DATA_ID FROM CONTACT_REL_DATA WHERE CONTACT_ID=:contactID');
        $stmt->bindValue(':contactID', $contactID);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        if (empty($results)) return $vcard;

        // Fetch each data record in turn
        $stmt = $this->connection->prepare('SELECT DATA_NAME, URL FROM CONTACT_DATA WHERE CONTACT_DATA_ID=:dataID');
        foreach ($results as $dataID)
        {
	    $stmt->bindValue(':dataID', $dataID);
	    $stmt->execute();
	    $data = $stmt->fetch();
	    $stmt->closeCursor();

	    $field = $data["DATA_NAME"];
	    $vcard->$field($data["URL"]);
        }

        return $vcard;
    } // i_fetchDataForVCard()

    /**
     * Fetch all CATEGORIES records for the given contact ID and attach them.
     * @param vCard $vcard The vCard to attach records to. Not null.
     * @param unknown $contactID The contact ID the records are associated
     * with. Numeric, not null.
     * @return vCard The vCard being assembled.
     */
    private function i_fetchCategoriesForVCard(vCard $vcard, $contactID)
    {
    	assert($this->connection !== null);
    	assert($vcard !== null);
    	assert($contactID !== null);
    	assert(is_numeric($contactID));
    	
        // Fetch a list of records associated with the contact
        $stmt = $this->connection->prepare('SELECT CATEGORY_ID FROM CONTACT_REL_CATEGORIES WHERE CONTACT_ID=:contactID');
        $stmt->bindValue(':contactID', $contactID);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        // Fetch each record in turn
        $stmt = $this->connection->prepare('SELECT CATEGORY_NAME FROM CONTACT_CATEGORIES WHERE CATEGORY_ID=:id');
        foreach ($results as $id)
        {
	    $stmt->bindValue(':id', $id);
	    $stmt->execute();
	    $item = $stmt->fetch(PDO::FETCH_NUM, 0);
	    $stmt->closeCursor();

	    $vcard->categories($item[0]);
        }

        return $vcard;
    } // i_fetchCategoriesForVCard()
} // VCardDB

/**
 * Produce HTML output from the given vcard via the assoc array
 * of HTML templates.
 * @$arg vcard The vcard to output.
 * @$templates An assoc array of named HTML templates. If not provided,
 * the global $vcard_templates will be used. See documentation in
 * vcard-templates.php.
 * @return the resulting HTML.
 */
function output_vcard(vCard $vcard, $templates = null)
{
    global $vcard_templates;

    $vcard->setFNAppropriately();

    return i_process( $vcard,
		      $templates ? $templates : $vcard_templates,
		      "vcard"
		     );
} //output_vcard

/**
 * Parses a template substitution key in a template.
 * @arg key The key to tokenize.
 * @return an associative array containing "key": the full original key,
 * "quest": the part after any question mark indicating what field the
 * substitution depends on, "lookup": the bang-field indicating the contents of
 * a field to look up and substitute, and "iter_over" contains the part after a
 * hash mark which indicates a field with multiple instances we are to iterate 
 * over.
 */
function i_parse_key($key)
{
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
	    $key_struct["template"] = $part;
    }
    return $key_struct;
} // i_parse_key()

/**
 * Finds the required template by $key in $templates and returns it if found.
 * Looks for the magic _fallback key in templates, and, if there, expects it
 * to be another associate array. If the current key is not in $templates,
 * searches in _fallback (potentially recursively).
 * @arg $key The key of the template to locate.
 * @arg $templates An associative array of named HTML templates.
 * @return The requested template, if found.
 */
function i_find_template($key, $templates)
{
    if (array_key_exists($key, $templates))
    {
	return $templates[$key];
    } else if ( array_key_exists("_fallback", $templates) ) {
	$fallback = $templates["_fallback"];
	if (!is_array($fallback))
	{
	    error_log('$templates["_fallback"] is NOT an array.');
	    return false;
	}
	return i_find_template($key, $fallback);
    } else {
	return false;
    }
} // i_find_template()

/**
 * Internal helper for producing HTML for vcard from templates.
 * Recurses from $key, processing substitutions and returning its portion
 * of the HTML tree.
 *
 * @arg $vcard The vcard being written out.
 * @arg $templates An assoc array of named html templates like the global
 *   $vcard_templates.
 * @arg $key The current template entry being processed.
 * @arg $iter_over The current vcard field being iterated over, if any.
 * @arg $iter_item The current element of the vcard field being iterated over,
 *   if any.
 * @return The portion of the HTML tree output.
 */
function i_process( $vcard,
	$templates, $key, $iter_over="", $iter_item=null )
{
  $key_struct = i_parse_key($key);

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
	    {
//DEBUG:
//		error_log("DEBUG: key: ".$key." iter_over: ".$iter_over." fn: ".$vcard->fn);
		array_push($iter_strings, i_process($vcard, $templates, $key_struct["template"], $iter_over, $iter_item));
	    }
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
		// otherwise we look it up and *take the first one found*
                // NOTE: vcard->__call() VERY fragile.
		$items = $vcard->$compound_key[0];
	    	if (!empty($items))
			$value = htmlspecialchars(
				array_key_exists($compound_key[1], $items[0]) 
				? $items[0][$compound_key[1]]
				: ""
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
  
  // if we already have a value or we don't have a template, bail
  if (!empty($value) || !array_key_exists("template", $key_struct))
    return $value;

  // Template processing
  $template = i_find_template($key_struct["template"], $templates);
  if (!empty($template))
  {
      $low = 0;

      // FIXME: ugly loop
      $high = strpos($template, "{{", $low);
      while ($high !== false)
      {
	// Strip and output until we hit a template marker
	$value .= substr($template, $low, $high - $low);

	// strip the front marker
        $low = $high + 2;
	$high = strpos($template, "}}", $low);

	// Remove and process the new marker
	$new_key = substr($template, $low, $high - $low);
	$high += 2;
	$low = $high;
	$value .= i_process($vcard, $templates, $new_key, $iter_over, $iter_item);
	$high = strpos($template, "{{", $low);
      }
      $value .= substr($template, $low);
   } // if template

   return $value;
} //i_process()

?>
