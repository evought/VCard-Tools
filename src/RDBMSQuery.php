<?php
/**
 * RDBMS Query
 * @author Eric Vought evought@pobox.com 2015-01-21
 * @copyright Eric Vought 2014, Some rights reserved.
 * @license MIT http://opensource.org/licenses/MIT
 */

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

namespace EVought\vCardTools;

/**
 * A structure to store a SQL statement and possibly an associated prepared
 * statement. The prepared statement may be weakly held in order to allow
 * external resources to be garbage collected and freed.
 *
 * @author evought
 */
class RDBMSQuery
{
    /**
     * The SQL required for this query.
     * @var string
     */
    private $sqlString;
    
    /**
     * A weak reference to a prepared statement for this query (if it exists).
     * @var \Weakref
     */
    private $statement;
    
    public function __construct($sqlString)
    {
        $this->sqlString = $sqlString;
        // WORKAROUND: PHP Bug #68882 cannot instantiate a null WeakRef and need
        // to indicate that the payload is initially invalid.
        $this->statement = new WeakRefStub(null);
    }
    
    public function getSQL()
    {
        return $this->sqlString;
    }
    
    public function getStatement()
    {
        return $this->statement->get();
    }
    
    public function setStatement(\PDOStatement $statement)
    {
        $this->statement = self::getWeakRef($statement);
    }
    
    static function getWeakRef($ob)
    {
        // WORKAROUND: \WeakRef not in core. If not PECL ectension not
        // available, stub it. Stub always returns null, forcing cache miss;
        // same result as if we did not cache prepared statements which was the
        // original behavior anyway.
        if (class_exists('\WeakRef'))
            return new \WeakRef($ob);
        else
            return new WeakRefStub($ob);
    }
}
