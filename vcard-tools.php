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
	    self::store_address_from_vcard($this->connection, $adr, $contact_id);
        }

        foreach ($vcard->note as $note)
        {
	    self::store_note_from_vcard($this->connection, $note, $contact_id);
        }

        foreach (["photo", "logo", "sound"] as $data_field)
        {
	    foreach ($vcard->$data_field as $data_item)
    	    {
                self::store_data_from_vcard( $this->connection, $data_field,
                                             $data_item, $contact_id );
    	    }
        }

        foreach ($vcard->tel as $tel)
        {
	    self::store_tel_from_vcard($this->connection, $tel, $contact_id);
        }

        foreach ($vcard->email as $item)
        {
	    self::store_email_from_vcard($this->connection, $item, $contact_id);
        }

        foreach ($vcard->categories as $item)
        {
	    self::store_category_from_vcard( $this->connection, $item, 
                                             $contact_id );
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
                                ? PDO::PARAM_NULL : $vcard->kind );
        $stmt->bindValue(':fn', $vcard->fn);

        // NOTE: The VCard spec allows a contact to have multiple names.
        // In practice, no implementations seem to allow this, so we ignore
        // it (for now). That means we have to deal with the oddity that n
        // is actually an array below.
        $n = empty($vcard->n) ? array() : $vcard->n[0];

        foreach([ 'Prefixes', 'FirstName', 'AdditionalNames', 'LastName',
              'Suffixes' ] as $n_key)
        {
            $n_value = empty($n[$n_key]) ? PDO::PARAM_NULL : $n[$n_key];
            $stmt->bindValue(':n_'.$n_key, $n_value);
        }

        $stmt->bindValue(':nickname', $vcard->nickname ? $vcard->nickname[0] : PDO::PARAM_NULL);
        $stmt->bindValue(':bday', $vcard->bday ? $vcard->bday : PDO::PARAM_NULL);
        $stmt->bindValue(':tz', $vcard->tz ? $vcard->tz : PDO::PARAM_NULL);

        $geo = $vcard->geo;
        if (empty($geo))
        {
            $stmt->bindValue(':geolat', PDO::PARAM_NULL);
            $stmt->bindValue(':geolon', PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':geolat', $geo[0]["Lattitude"]);
            $stmt->bindValue(':geolon', $geo[0]["Longitude"]);
        }

        $stmt->bindValue( ':role', $vcard->role
                          ? $vcard->role[0] : PDO::PARAM_NULL );
        $stmt->bindValue(':title',
	    $vcard->title ? $vcard->title[0]: PDO::PARAM_NULL );

        $stmt->bindValue(':rev', $vcard->rev ? $vcard->rev : PDO::PARAM_NULL);
        $stmt->bindValue(':uid', $vcard->uid ? $vcard->uid : PDO::PARAM_NULL);
        $stmt->bindValue(':url', $vcard->url ? $vcard->url[0] : PDO::PARAM_NULL);
    
        $stmt->execute();
        $contact_id = $this->connection->lastInsertId();

        return $contact_id;
    } // i_storeJustContact()

    /**
     * Saves the vcard org data to the database.
     * @arg $org The VCard org record to write out.
     * @arg $contact_id The ID of the contact the org is to be attached to.
     * @return The id of the new org record in the database.
     * FIXME: does not handle type property in any way.
     * FIXME: does not handle org subunits.
     */
    private function i_storeOrg(Array $org, $contact_id)
    {
        $stmt = $this->connection->prepare("INSERT INTO CONTACT_ORG (NAME, UNIT1, UNIT2) VALUES (:Name, :Unit1, :Unit2)");

        foreach(['Name', 'Unit1', 'Unit2'] as $key)
        {
            $value = empty($org[$key]) ? PDO::PARAM_NULL : $org[$key];
            $stmt->bindValue(':'.$key, $value);
        }
        $stmt->execute();
        $org_id = $this->connection->lastInsertId();

        $stmt = $this->connection->prepare("INSERT INTO CONTACT_REL_ORG (CONTACT_ID, ORG_ID) VALUES (:contact_id, :org_id)");
        $stmt->bindValue(":contact_id", $contact_id);
        $stmt->bindValue(":org_id", $org_id);
        $stmt->execute();

        return $org_id;
    } // i_storeOrg()

    // Saves the vcard address data to the database, returns the id of the new
    // address record. Takes the vcard adr record and the id of the contact to
    // connect it to.
    // FIXME: does not handle type property in any way.
    function store_address_from_vcard($connection, $adr, $contact_id)
    {
        $stmt = $connection->prepare("INSERT INTO CONTACT_MAIL_ADDRESS (STREET, LOCALITY, REGION, POSTAL_CODE, COUNTRY) VALUES (:StreetAddress, :Locality, :Region, :PostalCode, :Country)");

        foreach( [ 'StreetAddress', 'Locality', 'Region',
                   'PostalCode', 'Country' ]
                 as $key )
        {
            $value = empty($adr[$key]) ? PDO::PARAM_NULL : $adr[$key];
            $stmt->bindValue(':'.$key, $value);
        }

        $stmt->execute();
        $adr_id = $connection->lastInsertId();

        $stmt = $connection->prepare("INSERT INTO CONTACT_REL_MAIL_ADDRESS (CONTACT_ID, MAIL_ADDRESS_ID) VALUES (:contact_id, :adr_id)");
        $stmt->bindValue(":contact_id", $contact_id);
        $stmt->bindValue(":adr_id", $adr_id);
        $stmt->execute();

        return $adr_id;
    } // store_address_from_vcard()

    // Store the data fields (photo, logo, sound)
    // Currently only stores URLs, not blobs
    // $data_field must be one of: photo, logo, sound
    static function store_data_from_vcard( $connection, $data_field, $url, 
                                           $contact_id )
    {
        $stmt = $connection->prepare("INSERT INTO CONTACT_DATA (DATA_NAME, URL) VALUES (:data_name, :url)");

        $stmt->bindValue(":data_name", $data_field);
        $stmt->bindValue(":url", $url);
        $stmt->execute();
        $data_id = $connection->lastInsertId();

        $stmt = $connection->prepare("INSERT INTO CONTACT_REL_DATA (CONTACT_ID, CONTACT_DATA_ID) VALUES (:contact_id, :data_id)");
        $stmt->bindValue(":contact_id", $contact_id);
        $stmt->bindValue(":data_id", $data_id);
        $stmt->execute();
    } // store_data_from_vcard

    static function store_note_from_vcard($connection, $note, $contact_id)
    {
        $stmt = $connection->prepare("INSERT INTO CONTACT_NOTE (NOTE) VALUES (:note)");

        $stmt->bindValue(":note", $note);
        $stmt->execute();
        $note_id = $connection->lastInsertId();

        $stmt = $connection->prepare("INSERT INTO CONTACT_REL_NOTE (CONTACT_ID, NOTE_ID) VALUES (:contact_id, :note_id)");
        $stmt->bindValue(":contact_id", $contact_id);
        $stmt->bindValue(":note_id", $note_id);
        $stmt->execute();

    } // store_note_from_vcard()

    static function store_tel_from_vcard($connection, $tel, $contact_id)
    {
        $stmt = $connection->prepare("INSERT INTO CONTACT_PHONE_NUMBER (LOCAL_NUMBER) VALUES (:number)");

        $stmt->bindValue(":number", $tel);
        $stmt->execute();
        $phone_id = $connection->lastInsertId();

        $stmt = $connection->prepare("INSERT INTO CONTACT_REL_PHONE_NUMBER (CONTACT_ID, PHONE_NUMBER_ID) VALUES (:contact_id, :phone_id)");
        $stmt->bindValue(":contact_id", $contact_id);
        $stmt->bindValue(":phone_id", $phone_id);
        $stmt->execute();
    } // store_tel_from_vcard()

    static function store_email_from_vcard($connection, $email, $contact_id)
    {
        $stmt = $connection->prepare("INSERT INTO CONTACT_EMAIL (EMAIL_ADDRESS) VALUES (:email)");

        $stmt->bindValue(":email", $email);
        $stmt->execute();
        $new_id = $connection->lastInsertId();

        $stmt = $connection->prepare("INSERT INTO CONTACT_REL_EMAIL (CONTACT_ID, EMAIL_ID) VALUES (:contact_id, :new_id)");
        $stmt->bindValue(":contact_id", $contact_id);
        $stmt->bindValue(":new_id", $new_id);
        $stmt->execute();
    } // store_email_from_vcard()

    static function store_category_from_vcard($connection, $category, $contact_id)
    {
        $stmt = $connection->prepare("INSERT INTO CONTACT_CATEGORIES(CATEGORY_NAME) VALUES (:category)");

        $stmt->bindValue(":category", $category);
        $stmt->execute();
        $new_id = $connection->lastInsertId();

        $stmt = $connection->prepare("INSERT INTO CONTACT_REL_CATEGORIES (CONTACT_ID, CATEGORY_ID) VALUES (:contact_id, :new_id)");
        $stmt->bindValue(":contact_id", $contact_id);
        $stmt->bindValue(":new_id", $new_id);
        $stmt->execute();
    } // store_category_from_vcard()

    /**
     * Fetch all vcards from the database.
     * If kind is given, only fetch those of that kind (e.g. organization).
     */
    static function fetch_vcards_from_db($connection, $kind="%")
    {
        $stmt = $connection->prepare("SELECT * FROM CONTACT WHERE KIND LIKE :kind");
        $stmt->bindValue(":kind", $kind);
        $stmt->execute();
        $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);

        $vcards = array();

        while ($row = $stmt->fetch())
        {
	    $vcards[$row["CONTACT_ID"]]
		= i_fetch_vcard_from_database($connection, $row);
        } // while

        $stmt->closeCursor();

        return $vcards;
    } // fetch_vcards_from_db()

    /**
     * Returns all vcards where the fn or categories match the requested search
     * string.
     */
    static function search_vcards($connection, $search_string, $kind="%")
    {
        $stmt = $connection->prepare("SELECT CONTACT_ID FROM CONTACT WHERE FN LIKE :search_string AND KIND LIKE :kind");
        $stmt->bindValue(":kind", $kind);
        $stmt->bindValue(":search_string", $search_string);
        $stmt->execute();

        $contact_ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();
 
        $contact_ids += self::fetch_contact_ids_for_category($connection, $search_string, $kind="%");

        return self::fetch_vcards_by_id($connection, $contact_ids);
    } // search_vcards()

    /**
     * Returns a list of all contact_ids where the org.name parameter matches
     * the query. May be a SQL pattern.
     */
    static function fetch_contact_ids_for_organization( $connection, 
        $organization_name, $kind="%" )
    {
        if (empty($organization_name)) return array();

        $stmt = $connection->prepare("SELECT ORG_ID FROM CONTACT_ORG WHERE NAME LIKE :organization_name");
        $stmt->bindValue(":organization_name", $organization_name);
        $stmt->execute();
        $org_ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        if (empty($org_ids)) return array();

        // HACK: PDO does not support bind an array to an IN parameter, so
        // we just add the list as a string to the query. We aren't re-executing
        // the query and there is no worry of SQL injection here, so it doesn't
        // matter but it's clunky.
        $stmt = $connection->prepare("SELECT DISTINCT CONTACT_ID FROM CONTACT_REL_ORG WHERE ORG_ID IN ("
	    . implode(",", $org_ids) . ")");
        $stmt->execute();
        $contact_ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        return self::filter_contact_ids_by_kind($connection, $contact_ids, $kind);
    } // fetch_contact_ids_for_organization()

    /**
     * Returns only the contact_ids from the input list where kind matches.
     */
    static function filter_contact_ids_by_kind( $connection, $contact_ids, 
          $kind="%" )
    {
        if (empty($contact_ids) || $kind == "%") return $contact_ids;

        $stmt = $connection->prepare("SELECT CONTACT_ID FROM CONTACT WHERE CONTACT_ID IN ("
	. implode(",", $contact_ids) . ") AND KIND LIKE :kind");
        $stmt->bindValue(":kind", $kind);
        $stmt->execute();
        $contact_ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        return $contact_ids;    
    } // filter_contact_ids_by_kind()

    /**
     * Returns a list of all contact_ids in a given category. Category may be
     * a SQL pattern.
     */
    static function fetch_contact_ids_for_category( $connection, $category, 
           $kind="%" )
    {
        $stmt = $connection->prepare("SELECT CATEGORY_ID FROM CONTACT_CATEGORIES WHERE CATEGORY_NAME LIKE :category");
        $stmt->bindValue(":category", $category);
        $stmt->execute();
        $category_ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        if (empty($category_ids)) return array();

        // HACK: PDO does not support bind an array to an IN parameter, so
        // we just add the list as a string to the query. We aren't re-executing
        // the query and there is no worry of SQL injection here, so it doesn't
        // matter but it's clunky.
        $stmt = $connection->prepare("SELECT DISTINCT CONTACT_ID FROM CONTACT_REL_CATEGORIES WHERE CATEGORY_ID IN ("
	    . implode(",", $category_ids) . ")");
        $stmt->execute();
        $contact_ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        return self::filter_contact_ids_by_kind($connection, $contact_ids, $kind);
    } // fetch_contact_ids_for_category()

    static function fetch_vcards_by_id($connection, $contact_ids)
    {
        $vcards = array();
        foreach($contact_ids as $contact_id)
        {
	    $vcards[$contact_id]
		   = self::fetch_vcard_from_db($connection, $contact_id);
        }

        return $vcards;
    } // fetch_vcards_by_id()

    /**
     * Fetch a single vcard given a contact_id.
     * @return The completed vcard or false if none found.
     */
    static function fetch_vcard_from_db($connection, $contact_id)
    {
        $stmt = $connection->prepare("SELECT * FROM CONTACT WHERE CONTACT_ID = :contact_id");
        $stmt->bindValue(":contact_id", $contact_id);
        $stmt->execute();
        $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);

        $vcard = false;

        $row = $stmt->fetch();
        if ($row != false)
	   $vcard = self::i_fetch_vcard_from_database($connection, $row);
        $stmt->closeCursor();

        return $vcard;
} // fetch_vcard_from_db()

    /**
     * Internal helper to fill in details of a vcard.
     * @arg $row The associative array row returned from the db.
     * @arg $connection DB connection to fetch additional info.
     * @return The finished vcard.
     */
    static function i_fetch_vcard_from_database($connection, $row)
    {
        $vcard = new vcard();
        $contact_id = $row["CONTACT_ID"];

        if (!empty($row["KIND"])) $vcard->kind($row["KIND"]);
        if (!empty($row["FN"])) $vcard->fn($row["FN"]);

        if (!empty($row["N_PREFIX"])) $vcard->n($row["N_PREFIX"], "Prefixes");
        if (!empty($row["N_GIVEN_NAME"]))
		$vcard->n($row["N_GIVEN_NAME"], "FirstName");
        if (!empty($row["N_ADDIT_NAME"]))
		$vcard->n($row["N_ADDIT_NAME"], "AdditionalNames");
        if (!empty($row["N_FAMILY_NAME"]))
		$vcard->n($row["N_FAMILY_NAME"], "LastName");
        if (!empty($row["N_SUFFIX"])) $vcard->n($row["N_SUFFIX"], "Suffixes");
        if (!empty($row["NICKNAME"])) $vcard->nickname($row["N_NICKNAME"]);
        if (!empty($row["BDAY"]) && ($row["BDAY"] != PDO::PARAM_NULL))
            $vcard->bday($row["BDAY"]);
        if (!empty($row["GEO_LAT"])) $vcard->geo($row["GEO_LAT"], "Lattitude");
        if (!empty($row["GEO_LON"])) $vcard->geo($row["GEO_LON"], "Longitude");
        if (!empty($row["TITLE"])) $vcard->title($row["TITLE"]);
        if (!empty($row["ROLE"])) $vcard->role($row["ROLE"]);
        if (!empty($row["REV"])) $vcard->rev($row["REV"]);
        if (!empty($row["UID"])) $vcard->uid($row["UID"]);
        if (!empty($row["URL"])) $vcard->url($row["URL"]);
        if (!empty($row["VERSION"])) $vcard->version($row["VERSION"]);
	$vcard->prodid(self::VCARD_PRODUCT_ID);

        self::fetch_org_for_vcard_from_db($connection, $vcard, $contact_id);
        self::fetch_adr_for_vcard_from_db($connection, $vcard, $contact_id);
        self::fetch_note_for_vcard_from_db($connection, $vcard, $contact_id);
        self::fetch_data_for_vcard_from_db($connection, $vcard, $contact_id);
        self::fetch_tel_for_vcard_from_db($connection, $vcard, $contact_id);
        self::fetch_email_for_vcard_from_db($connection, $vcard, $contact_id);
        self::fetch_category_for_vcard_from_db($connection, $vcard, $contact_id);

        return $vcard;
    } // i_fetch_vcard_from_database()

    // Fetch and attach all org records for a vcard, returning the card.
    static function fetch_org_for_vcard_from_db($connection, $vcard, $contact_id)
    {
        $col_map = [
                     'NAME' => 'Name',
		     'UNIT1' => 'Unit1',
                     'UNIT2' => 'Unit2'
                   ];

        // Fetch a list of org records associated with the contact
        $stmt = $connection->prepare("SELECT ORG_ID FROM CONTACT_REL_ORG WHERE CONTACT_ID=:contact_id");
        $stmt->bindValue(":contact_id", $contact_id);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        // Fetch each org record in turn
        $stmt = $connection->prepare("SELECT NAME, UNIT1, UNIT2 FROM CONTACT_ORG WHERE ORG_ID=:org_id");
        foreach ($results as $org_id)
        {
	    $stmt->bindValue(":org_id", $org_id);
	    $stmt->execute();
	    $org_res = $stmt->fetch(PDO::FETCH_ASSOC);
	    $stmt->closeCursor();

            $org = array();
            foreach ($org_res as $key => $value)
                if (!empty($value)) $org[$col_map[$key]] = $value;
            $vcard->org($org);
        }

        return $vcard;
    } // fetch_org_for_vcard_from_db()

    // Fetch and attach all adr records for a vcard, returning the card.
    static function fetch_adr_for_vcard_from_db($connection, $vcard, $contact_id)
    {
        $col_map = [
                     'STREET' => 'StreetAddress',
		     'LOCALITY' => 'Locality',
                     'REGION' => 'Region',
                     'POSTAL_CODE' => 'PostalCode',
                     'COUNTRY' => 'Country'
                   ];

        // Fetch a list of adr records associated with the contact
        $stmt = $connection->prepare("SELECT MAIL_ADDRESS_ID FROM CONTACT_REL_MAIL_ADDRESS WHERE CONTACT_ID=:contact_id");
        $stmt->bindValue(":contact_id", $contact_id);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        // Fetch each adr record in turn
        $stmt = $connection->prepare("SELECT STREET, LOCALITY, REGION, POSTAL_CODE, COUNTRY FROM CONTACT_MAIL_ADDRESS WHERE MAIL_ADDRESS_ID=:adr_id");
        foreach ($results as $adr_id)
        {
	    $stmt->bindValue(":adr_id", $adr_id);
	    $stmt->execute();
	    $adr_res = $stmt->fetch(PDO::FETCH_ASSOC);
	    $stmt->closeCursor();

            $adr = array();
            foreach ($adr_res as $key => $value)
                if (!empty($value)) $adr[$col_map[$key]] = $value;

	    $vcard->adr($adr);
        }
        return $vcard;
    } // fetch_adr_for_vcard_from_db()

    // Fetch and attach all note records for a vcard, returning the card.
    static function fetch_note_for_vcard_from_db($connection, $vcard, $contact_id)
    {
        // Fetch a list of note records associated with the contact
        $stmt = $connection->prepare("SELECT NOTE_ID FROM CONTACT_REL_NOTE WHERE CONTACT_ID=:contact_id");
        $stmt->bindValue(":contact_id", $contact_id);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        // Fetch each note record in turn
        $stmt = $connection->prepare("SELECT NOTE FROM CONTACT_NOTE WHERE NOTE_ID=:note_id");
        foreach ($results as $note_id)
        {
	    $stmt->bindValue(":note_id", $note_id);
	    $stmt->execute();
	    $note = $stmt->fetch(PDO::FETCH_NUM, 0);
	    $stmt->closeCursor();

	    $vcard->note($note[0]);
        }

        return $vcard;
    } // fetch_note_for_vcard_from_db()

    static function fetch_tel_for_vcard_from_db($connection, $vcard, $contact_id)
    {
        // Fetch a list of tel records associated with the contact
        $stmt = $connection->prepare("SELECT PHONE_NUMBER_ID FROM CONTACT_REL_PHONE_NUMBER WHERE CONTACT_ID=:contact_id");
        $stmt->bindValue(":contact_id", $contact_id);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        // Fetch each record in turn
        $stmt = $connection->prepare("SELECT LOCAL_NUMBER FROM CONTACT_PHONE_NUMBER WHERE PHONE_NUMBER_ID=:phone_id");
        foreach ($results as $phone_id)
        {
	    $stmt->bindValue(":phone_id", $phone_id);
	    $stmt->execute();
	    $phone = $stmt->fetch(PDO::FETCH_NUM, 0);
	    $stmt->closeCursor();

	    $vcard->tel($phone[0]);
        }

        return $vcard;
    } // fetch_tel_for_vcard_from_db()

    static function fetch_email_for_vcard_from_db($connection, $vcard, 
              $contact_id )
    {
        // Fetch a list of records associated with the contact
        $stmt = $connection->prepare("SELECT EMAIL_ID FROM CONTACT_REL_EMAIL WHERE CONTACT_ID=:contact_id");
        $stmt->bindValue(":contact_id", $contact_id);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        // Fetch each record in turn
        $stmt = $connection->prepare("SELECT EMAIL_ADDRESS FROM CONTACT_EMAIL WHERE EMAIL_ID=:id");
        foreach ($results as $id)
        {
	    $stmt->bindValue(":id", $id);
	    $stmt->execute();
	    $item = $stmt->fetch(PDO::FETCH_NUM, 0);
	    $stmt->closeCursor();

	    $vcard->email($item[0]);
        }

        return $vcard;
    } // fetch_email_for_vcard_from_db()

    // Fetch and attach all data records for a vcard, returning the card.
    // photo, logo, sound
    static function fetch_data_for_vcard_from_db($connection, $vcard, $contact_id)
    {
        // Fetch a list of data records associated with the contact
        $stmt = $connection->prepare("SELECT CONTACT_DATA_ID FROM CONTACT_REL_DATA WHERE CONTACT_ID=:contact_id");
        $stmt->bindValue(":contact_id", $contact_id);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        if (empty($results)) $vcard;

        // Fetch each data record in turn
        $stmt = $connection->prepare("SELECT DATA_NAME, URL FROM CONTACT_DATA WHERE CONTACT_DATA_ID=:data_id");
        foreach ($results as $data_id)
        {
	    $stmt->bindValue(":data_id", $data_id);
	    $stmt->execute();
	    $data = $stmt->fetch();
	    $stmt->closeCursor();

	    $field = $data["DATA_NAME"];
	    $vcard->$field($data["URL"]);
        }

        return $vcard;
    } // fetch_adr_for_vcard_from_db()

    static function fetch_category_for_vcard_from_db( $connection, $vcard, 
           $contact_id )
    {
        // Fetch a list of records associated with the contact
        $stmt = $connection->prepare("SELECT CATEGORY_ID FROM CONTACT_REL_CATEGORIES WHERE CONTACT_ID=:contact_id");
        $stmt->bindValue(":contact_id", $contact_id);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        // Fetch each record in turn
        $stmt = $connection->prepare("SELECT CATEGORY_NAME FROM CONTACT_CATEGORIES WHERE CATEGORY_ID=:id");
        foreach ($results as $id)
        {
	    $stmt->bindValue(":id", $id);
	    $stmt->execute();
	    $item = $stmt->fetch(PDO::FETCH_NUM, 0);
	    $stmt->closeCursor();

	    $vcard->categories($item[0]);
        }

        return $vcard;
    } // fetch_category_for_vcard_from_db()
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
