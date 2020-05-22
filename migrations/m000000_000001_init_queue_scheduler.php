<?php

use \yii\db\Schema;
use \yii\db\Migration;

/**
 * @version 20.05
 * @copyright (c) KFOSOFT <kfosoftware@gmail.com>
 */
class m000000_000001_init_queue_scheduler extends Migration
{
    private const TABLE_NAME = 'SchedulerQueue';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute(sprintf('
            CREATE TABLE %s (
                uuid CHAR(36) NOT NULL,
                jobClass VARCHAR(255) NOT NULL,
                jobParams VARCHAR(255) NOT NULL,
                jobTime INT UNSIGNED NOT NULL,
                PRIMARY KEY (uuid)
            );
        ', self::TABLE_NAME));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable(self::TABLE_NAME);
    }
}
