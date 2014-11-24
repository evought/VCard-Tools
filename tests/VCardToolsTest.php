<?php
/**
 * Unit tests for the vcard-tools.php file routines.
 * @author Eric Vought <evought@pobox.com>
 * 2014-11-16
 * @copyright Eric Vought 2014, Some rights reserved.
 * @license http://creativecommons.org/licenses/by/4.0/ CC-BY 4.0 
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

    public function testFetchWhenEmpty()
    {
        $this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
                             "Precondition" );
	$vcards = fetch_vcards_from_db(self::$pdo);

	$this->assertEmpty($vcards);
        $this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
                             "Postcondition" );
    }

    public function testSearchWhenEmpty()
    {
        $this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
                             "Precondition" );
	$vcards = search_vcards(self::$pdo, "bloomers");

	$this->assertEmpty($vcards);
        $this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
                             "Postcondition" );
    }

    public function testStoreAndRetrieveTrivialVCard()
    {
        $this->assertEquals( 0, $this->getConnection()->getRowCount('CONTACT'),
                             "Precondition" );

        $expected = 'foo';
        $vcard = new VCard();
	$vcard->fn = $expected;

        $contact_id = store_whole_contact_from_vcard(self::$pdo, $vcard);
        $this->assertEquals( 1, $this->getConnection()->getRowCount('CONTACT'),
                             "After storing " . $contact_id );

        $result_vcards = fetch_vcards_by_id(self::$pdo, array($contact_id));

	$this->assertCount(1, $result_vcards);
        $result_vcard = array_pop($result_vcards);
	$this->assertEquals($expected, $result_vcard->fn);
    }


} // VCardToolsTest
?>
