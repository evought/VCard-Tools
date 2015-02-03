<?php

use Phinx\Migration\AbstractMigration;

class VcardToolsCreateTitleTable extends AbstractMigration
{
     /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('CONTACT_TITLE', ['id'=>'TITLE_ID']);
        $table  ->addColumn('UID', 'string', ['limit'=>45])
                ->addColumn('TITLE', 'string', ['limit'=>50])
                ->addColumn('VALUETYPE', 'string', ['limit'=>40, 'null'=>true])
                ->addColumn('PREF', 'integer', [ 'limit'=>2,
                                                 'null'=>true,
                                                 'signed'=>false ] )
                ->addForeignKey( 'UID', 'CONTACT', 'UID',
                                [ 'delete'=>'CASCADE',
                                  'update'=>'CASCADE'
                                    ])
                ->create();
        
        $table = $this->table( 'CONTACT_TITLE_REL_TYPES',
                               [ 'id'=>false,
                                 'primary_key'=>['TITLE_ID', 'TYPE_NAME']
                               ] );
        $table  ->addColumn('TITLE_ID', 'integer')
                ->addColumn('TYPE_NAME', 'string', ['limit'=>20])
                ->addForeignKey( 'TITLE_ID', 'CONTACT_TITLE', 'TITLE_ID',
                                  [ 'delete'=>'CASCADE',
                                    'update'=>'CASCADE'
                                      ])
                ->create();
        
        $table = $this->table('CONTACT');
        $table->removeColumn('TITLE')->update();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->dropTable('CONTACT_ROLE');
        $this->dropTable('CONTACT_ROLE_REL_TYPES');
        $table = $this->table('CONTACT');
        $table  ->addColumn('ROLE', 'string', ['null'=>true])
                ->update();
    }
}