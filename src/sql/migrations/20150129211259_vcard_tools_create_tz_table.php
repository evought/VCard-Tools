<?php

use Phinx\Migration\AbstractMigration;

class VcardToolsCreateTzTable extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('CONTACT_TZ', ['id'=>'TZ_ID']);
        $table  ->addColumn('UID', 'string', ['limit'=>45])
                ->addColumn('TZ', 'string')
                ->addColumn('MEDIATYPE', 'string', ['limit'=>255, 'null'=>true])
                ->addColumn('VALUETYPE', 'string', ['limit'=>40, 'null'=>true])
                ->addColumn('PREF', 'integer', [ 'limit'=>2,
                                                 'null'=>true,
                                                 'signed'=>false ] )
                ->addForeignKey( 'UID', 'CONTACT', 'UID',
                                [ 'delete'=>'CASCADE',
                                  'update'=>'CASCADE'
                                    ])
                ->create();
        
        $table = $this->table( 'CONTACT_TZ_REL_TYPES',
                               [ 'id'=>false,
                                 'primary_key'=>['TZ_ID', 'TYPE_NAME']
                               ] );
        $table  ->addColumn('TZ_ID', 'integer')
                ->addColumn('TYPE_NAME', 'string', ['limit'=>20])
                ->addForeignKey( 'TZ_ID', 'CONTACT_TZ', 'TZ_ID',
                                  [ 'delete'=>'CASCADE',
                                    'update'=>'CASCADE'
                                      ])
                ->create();
        
        $table = $this->table('CONTACT');
        $table->removeColumn('TZ')->update();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->dropTable('CONTACT_TZ');
        $this->dropTable('CONTACT_TZ_REL_TYPES');
        $table = $this->table('CONTACT');
        $table  ->addColumn('TZ', 'string', ['null'=>true])
                ->update();
    }
}