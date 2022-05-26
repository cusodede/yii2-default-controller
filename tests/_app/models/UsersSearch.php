<?php
declare(strict_types = 1);

namespace app\models;

use Throwable;
use yii\data\ActiveDataProvider;

/**
 * Class UsersSearch
 */
class UsersSearch extends Users {

	/**
	 * @inheritdoc
	 */
	public function rules():array {
		return [
			[['id'], 'integer'],
			[['username', 'login', 'password'], 'safe'],
		];
	}

	/**
	 * @param array $params
	 * @return ActiveDataProvider
	 * @throws Throwable
	 */
	public function search(array $params):ActiveDataProvider {
		$query = Users::find()
			->active();

		$dataProvider = new ActiveDataProvider([
			'id' => 'usersDataProvider',
			'query' => $query
		]);

		$dataProvider->setSort([
			'enableMultiSort' => true,//в тестах потребуется сортировка по двум атрибутам для гарантии попадания в проверяемый индекс
			'defaultOrder' => ['id' => SORT_ASC],
			'attributes' => [
				'id' => [
					'asc' => [Users::fieldName('id') => SORT_ASC],
					'desc' => [Users::fieldName('id') => SORT_DESC]
				],
				'username' => [
					'asc' => [Users::fieldName('username') => SORT_ASC],
					'desc' => [Users::fieldName('username') => SORT_DESC]
				],
				'login' => [
					'asc' => [Users::fieldName('login') => SORT_ASC],
					'desc' => [Users::fieldName('login') => SORT_DESC]
				]
			]
		]);

		$this->load($params);
		$query->andFilterWhere([static::fieldName('id') => $this->id]);
		$query->andFilterWhere(['like', static::fieldName('username'), $this->username]);
		$query->andFilterWhere(['like', static::fieldName('login'), $this->login]);


		return $dataProvider;
	}

}