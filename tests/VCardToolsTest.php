<?php
/**
 * Unit tests for the vcard-tools.php file routines.
 * @author Eric Vought <evought@pobox.com>
 * 2014-11-16
 * @copyright Eric Vought 2014, Some rights reserved.
 * @license MIT http://opensource.org/licenses/MIT
 */
require_once "vcard-tools.php";
require_once "vcard.php";

class VCardToolsTest extends PHPUnit_Extensions_Database_TestCase
{
    // only instantiate pdo once for test clean-up/fixture load
    static private $pdo = null;

    // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once
    private $conn = null;

    /**
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    public function getConnection()
    {
        if ($this->conn === null)
        {
            if (self::$pdo == null)
            {
               self::$pdo = new PDO( $GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], 
                                     $GLOBALS['DB_PASSWD'] );

               self::$pdo->setAttribute( PDO::ATTR_ERRMODE,
                                         PDO::ERRMODE_EXCEPTION );
            }
            $this->conn = $this->createDefaultDBConnection( self::$pdo,
                                                 $GLOBALS['DB_DBNAME'] );
        }

        return $this->conn;
    }

    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet('tests/emptyVCARDDB.xml');
    }

    /**
     * Work around for MySQL problem with truncating tables which are
     * referenced by foreign keys.
     * @see https://github.com/sebastianbergmann/dbunit/issues/37 .
     * @return \PHPUnit_Extensions_Database_Operation
     */
    protected function getSetUpOperation()
    {
        return new \PHPUnit_Extensions_Database_Operation_Composite(array(
            \PHPUnit_Extensions_Database_Operation_Factory::DELETE_ALL(),
            \PHPUnit_Extensions_Database_Operation_Factory::INSERT()
        ));
    }

    /**
     * Ensure that we can instantiate a VCardDB instance.
     */
    public function testCreateVCardDB()
    {
        $vcardDB = new VCardDB(self::$pdo);
        $this->assertNotEmpty($vcardDB);
        $this->assertInstanceOf('VCardDB', $vcardDB);

	$this->assertSame(self::$pdo, $vcardDB->getConnection());

        return $vcardDB;
    }

    /**
     * @depends testCreateVCardDB
     */
    public function testFetchWhenEmpty(VCardDB $vcardDB)
    {
        $this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
                             "Precondition" );
	$vcards = VCardDB::fetch_vcards_from_db(self::$pdo);

	$this->assertEmpty($vcards);
        $this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
                             "Postcondition" );
    }

    /**
     * @depends testCreateVCardDB
     */
    public function testSearchWhenEmpty(VCardDB $vcardDB)
    {
        $this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
                             "Precondition" );
	$vcards = VCardDB::search_vcards(self::$pdo, "bloomers");

	$this->assertEmpty($vcards);
        $this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
                             "Postcondition" );
    }

    /**
     * @depends testCreateVCardDB
     */
    public function testStoreAndRetrieveTrivialVCard(VCardDB $vcardDB)
    {
        $this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
                             "Precondition" );

        $expected = 'foo';
        $vcard = new VCard();
	$vcard->fn = $expected;

        $contact_id = VCardDB::store_whole_contact_from_vcard(self::$pdo, $vcard);
        $this->assertEquals( 1, $this->getConnection()->getRowCount('CONTACT'),
                             "After storing " . $contact_id );

        $result_vcards = VCardDB::fetch_vcards_by_id(self::$pdo, array($contact_id));

	$this->assertCount(1, $result_vcards);
        $result_vcard = array_pop($result_vcards);
	$this->assertEquals($expected, $result_vcard->fn);
        $this->assertEquals(VCardDB::VCARD_PRODUCT_ID, $result_vcard->prodid);
        unset($result_vcard->prodid);

        return $vcardDB;
    }


    /**
     * @depends testStoreAndRetrieveTrivialVCard
     */
    public function testStoreAndRetrieveVCard(VCardDB $vcardDB)
    {
        $this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
                             "Precondition" );

        $expected = [
                        'n_FirstName' => 'Fred',
                        'n_LastName' => 'Jones',
                        'url' => 'http://golf.com',
                        'fn' => 'Fred Jones'
                     ];
        $vcard = new VCard();
	$vcard->fn = $expected['fn'];
        $vcard->n($expected['n_FirstName'], 'FirstName');
        $vcard->n($expected['n_LastName'], 'LastName');
	$vcard->url($expected['url']);
	$vcard->fn = $expected['fn'];

        $contact_id = VCardDB::store_whole_contact_from_vcard(self::$pdo, $vcard);
        $this->assertEquals( 1, $this->getConnection()->getRowCount('CONTACT'),
                             "After storing " . $contact_id );
        $this->assertEquals( 0,
            $this->getConnection()->getRowCount('CONTACT_MAIL_ADDRESS'),
            "After storing " . $contact_id );
        $this->assertEquals( 0,
            $this->getConnection()->getRowCount('CONTACT_REL_MAIL_ADDRESS'),
            "After storing " . $contact_id );
        $result_vcards = VCardDB::fetch_vcards_by_id(self::$pdo, array($contact_id));

	$this->assertCount(1, $result_vcards);
        $result_vcard = array_pop($result_vcards);

        // check and remove prodid so it won't fail comparison
        $this->assertEquals(VCardDB::VCARD_PRODUCT_ID, $result_vcard->prodid);
        unset($result_vcard->prodid);

	$this->assertEquals($vcard, $result_vcard);

        return $vcardDB;
    } //testStoreAndRetrieveVCard()

    /**
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWAddress(VCardDB $vcardDB)
    {
        $this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
                             "Precondition" );

        $expected = [
                        'n_FirstName' => 'Fred',
                        'n_LastName' => 'Jones',
                        'adr_StreetAddress' => '47 Some Street',
			'adr_Locality' => 'Birmingham',
                        'adr_Region' => 'AL',
                        'fn' => 'Fred Jones'
                     ];
        $vcard = new VCard();
	$vcard->fn = $expected['fn'];
        $vcard->n($expected['n_FirstName'], 'FirstName');
        $vcard->n($expected['n_LastName'], 'LastName');
        $vcard->adr($expected['adr_StreetAddress'], 'StreetAddress');
        $vcard->adr($expected['adr_Locality'], 'Locality');
        $vcard->adr($expected['adr_Region'], 'Region');
	$vcard->fn = $expected['fn'];

        $contact_id = VCardDB::store_whole_contact_from_vcard(self::$pdo, $vcard);
        $this->assertEquals( 1, $this->getConnection()->getRowCount('CONTACT'),
                             "After storing " . $contact_id );
        $this->assertEquals( 1,
            $this->getConnection()->getRowCount('CONTACT_MAIL_ADDRESS'),
            "After storing " . $contact_id );
        $this->assertEquals( 1,
            $this->getConnection()->getRowCount('CONTACT_REL_MAIL_ADDRESS'),
            "After storing " . $contact_id );
        $result_vcards = VCardDB::fetch_vcards_by_id(self::$pdo, array($contact_id));

	$this->assertCount(1, $result_vcards);
        $result_vcard = array_pop($result_vcards);

        // check and remove prodid so it won't fail comparison
        $this->assertEquals(VCardDB::VCARD_PRODUCT_ID, $result_vcard->prodid);
        unset($result_vcard->prodid);

	$this->assertEquals($vcard, $result_vcard);
    } //testStoreAndRetrieveWAddress()

        /**
     * @depends testStoreAndRetrieveVCard
     */
    public function testStoreAndRetrieveWEmail()
    {
        $this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
                             "Precondition" );

        $expected = [
                        'n_FirstName' => 'Fred',
                        'n_LastName' => 'Jones',
                        'email' => 'noone@nowhere.org',
                        'fn' => 'Fred Jones'
                     ];
        $vcard = new VCard();
	$vcard->fn = $expected['fn'];
        $vcard->n($expected['n_FirstName'], 'FirstName');
        $vcard->n($expected['n_LastName'], 'LastName');
        $vcard->email($expected['email']);
	$vcard->fn = $expected['fn'];

        $contact_id = VCardDB::store_whole_contact_from_vcard(self::$pdo, $vcard);
        $this->assertEquals( 1, $this->getConnection()->getRowCount('CONTACT'),
                             "After storing " . $contact_id );
        $this->assertEquals( 1,
            $this->getConnection()->getRowCount('CONTACT_EMAIL'),
            "After storing " . $contact_id );
        $this->assertEquals( 1,
            $this->getConnection()->getRowCount('CONTACT_REL_EMAIL'),
            "After storing " . $contact_id );
        $result_vcards = VCardDB::fetch_vcards_by_id(self::$pdo, array($contact_id));

	$this->assertCount(1, $result_vcards);
        $result_vcard = array_pop($result_vcards);

        // check and remove prodid so it won't fail comparison
        $this->assertEquals(VCardDB::VCARD_PRODUCT_ID, $result_vcard->prodid);
        unset($result_vcard->prodid);

	$this->assertEquals($vcard, $result_vcard);
    } //testStoreAndRetrieveWAddress()
} // VCardToolsTest
?>