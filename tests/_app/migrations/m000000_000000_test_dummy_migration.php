<?php
declare(strict_types = 1);

use yii\db\Migration;

/**
 * Class m000000_000000_test_dummy_migration
 */
class m000000_000000_test_dummy_migration extends Migration {

	/**
	 * {@inheritdoc}
	 */
	public function safeUp() {
		$this->createTable('dummy', [
			'id' => $this->primaryKey(),
			'dummy' => $this->string(255)->null()->comment('dummy'),
		]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function safeDown() {
		$this->dropTable('dummy');
	}

}
