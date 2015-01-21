<?php
/**
 * VCardRepository.php
 * @author Eric Vought evought@pobox.com 2015-01-21
 * @copyright Eric Vought 2014, Some rights reserved.
 * @license MIT http://opensource.org/licenses/MIT
 */
namespace EVought\vCardTools;

/*
 * The MIT License
 *
 * Copyright 2015 evought.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * A generic interface for storing and retrieving VCards from some backing
 * store.
 * @author evought
 */
interface VCardRepository
{
    /**
     * Store the whole vcard to the database.
     * @param VCard $vcard The record to store.
     * @return integer The new contact id.
     */
    public function store(VCard $vcard);
    
    /**
     * Fetch all vcards from the backing store.
     * @param string $kind If kind is given, only fetch those of that kind (e.g.
     * 'organization').
     * @return array An array of vCards keyed by uid.
     */
    public function fetchAll($kind='%');
    
     /**
     * Returns all vcards where the fn or categories match the requested search
     * string.
     * @param string $searchString The pattern to search for (SQL matching rules). If
     * omitted, match all cards.
     * @param string $kind If kind is given, return only cards of that kind (e.g.
     * organization).
     * @return array of vCards indexed by uid.
     */
    public function search($searchString='%', $kind='%');
    
    /**
     * Returns a list of all contact_ids where the org.name parameter matches
     * the query.
     * @param string $organizationName The name of the organization to search for. May
     * be a SQL pattern. String, not empty.
     * @param string $kind If kind is provided, limit results to a specific Kind (e.g.
     * individual.
     * @return array The list of contact uids. Actual vCards are not fetched.
     */
    public function fetchIDsForOrganization($organizationName, $kind="%");
    
    /**
     * Returns a list of all contact uids in a given category.
     * @param string $category The string representing the category to search for.
     * May be a SQL pattern. Not empty.
     * @param string $kind If given, the kind (e.g. individual) to filter by.
     * @return array An array of contact uids. No vCards are fetched.
     */
    public function fetchIDsForCategory($category, $kind="%");
    
    /**
     * Retrieve vCard records for the given Contact IDs.
     * @param array $uids A list of contact uids to fetch.
     * @return array An array of vCards indexed by uid.
     */
    public function fetchByID(Array $uids);
    
    /**
     * Fetch a single vcard given a contact uid.
     * @param string $uid The ID of the record to fetch. String, not empty.
     * @return VCard|null The completed vcard or false if none found.
     */
    public function fetchOne($uid);
    
    /**
     * Deletes a CONTACT from the database by uid. Should delete all dependent
     * records (e.g. properties) for that CONTACT as well.
     * @param integer $uid The uid of the record to delete. Numeric,
     * not null.
     * @return bool If a record was deleted, false otherwise.
     * @throws \PDOException On database failure.
     */
    public function deleteContact($uid);
}
