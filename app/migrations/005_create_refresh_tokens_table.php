<?php

class Create_refresh_tokens_table
{
    private $_lava;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->dbforge();
    }

    public function up()
    {
        if (!$this->_lava->dbforge->table_exists('refresh_tokens')) {
            $this->_lava->dbforge
                ->add_field([
                    'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE, 'null' => FALSE],
                    'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'null' => FALSE],
                    'token' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => FALSE],
                    'jti' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => FALSE],
                    'expires_at' => ['type' => 'DATETIME', 'null' => FALSE],
                    'created_at' => ['type' => 'TIMESTAMP', 'null' => FALSE, 'default' => 'CURRENT_TIMESTAMP'],
                ])
                ->add_key('id', primary: TRUE)
                ->add_key('token', unique: TRUE, name: 'refresh_token_unique')
                ->add_key('user_id', name: 'refresh_user_id')
                ->create_table('refresh_tokens');
        }
    }

    public function down()
    {
        $this->_lava->dbforge->drop_table('refresh_tokens');
    }
}