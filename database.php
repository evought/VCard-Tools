<?php
/**
 * Database connection parameters and function for VCard testing.
 * @author Eric Vought evought@pobox.com 2014-11-14
 * @license CC-BY
 */

  $vcard_servername = "localhost";
  $vcard_dbname = "VCARD";
  $vcard_username = "vcard-test";
  $vcard_password = "wad%51lumpkin";

  /**
   * Connect to database and return a PDO connection
   */
  function vcard_db_connect()
  {
     global $vcard_servername, $vcard_dbname, $vcard_username, $vcard_password;

     $connection = new PDO("mysql:host=$vcard_servername;dbname=$vcard_dbname", $vcard_username, $vcard_password);

     // set the PDO error mode to exception
     $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

     return $connection;
  }
?>
