<?php

use Phinx\Migration\AbstractMigration;

class VcardToolsAddGroupColumn extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     *
     * Uncomment this method if you would like to use it.
     *
    public function change()
    {
    }
    */
    
    /**
     * Migrate Up.
     */
    public function up()
    {
        foreach ( [ 'CONTACT_ADR', 'CONTACT_CATEGORIES', 'CONTACT_DATA',
                    'CONTACT_EMAIL', 'CONTACT_GEO', 'CONTACT_NICKNAME',
                    'CONTACT_NOTE', 'CONTACT_ORG', 'CONTACT_RELATED',
                    'CONTACT_ROLE', 'CONTACT_TEL', 'CONTACT_TITLE',
                    'CONTACT_TZ', 'CONTACT_URL', 'CONTACT_XTENDED']
                as $tableName )
        {
            $table = $this->table($tableName);
            $table  ->addColumn('PROP_GROUP', 'string', ['null'=>true])
                    ->update();
        }
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        foreach ( [ 'CONTACT_ADR', 'CONTACT_CATEGORIES', 'CONTACT_DATA',
                    'CONTACT_EMAIL', 'CONTACT_GEO', 'CONTACT_NICKNAME',
                    'CONTACT_NOTE', 'CONTACT_ORG', 'CONTACT_RELATED',
                    'CONTACT_ROLE', 'CONTACT_TEL', 'CONTACT_TITLE',
                    'CONTACT_TZ', 'CONTACT_URL', 'CONTACT_XTENDED']
                as $tableName )
        {
            $table = $this->table($tableName);
            $table  ->removeColumn('PROP_GROUP')
                    ->update();
        }
    }
}