<?php

class SoftDeletableBehavior extends ModelBehavior
{
	/**
	 * Default setting values
	 * @var array
	 */
	protected $defaults = array(
		'field_name' => 'deleted'
	);

	protected $include_deleted_records = false;

	/**
	 * Load any settings, or use defaults
	 *
	 * @param object $model    Model using the behavior
	 * @param array  $settings Settings to override for model.
	 * @return void
	 */
	public function setup(Model $model, $settings = null)
	{
		if (is_array($settings)) {
			$this->settings[$model->alias] = array_merge($this->defaults, $settings);
		} else {
			$this->settings[$model->alias] = $this->defaults;
		}
	}

	/**
	 * Automatically exclude deleted records from find unless told otherwise.
	 * @param  Model  $model
	 * @param  array  $find  Conditions for the find
	 * @return array         Conditions for the find with delete excluded
	 */
	public function beforeFind(Model $model, $query)
	{
		if ($this->include_deleted_records)
			return $query;

		if (!$this->checkForDeletedField($model, $query['conditions'])) {
			$find_condition = $this->findConditionToExcludeDeleted($model);
			if (is_array($query['conditions'])) {
				$query['conditions'] += $find_condition;
			} else {
				$query['conditions'] = $find_condition;
			}
		}

		return $query;
	}

	protected function findConditionToExcludeDeleted(Model $model)
	{
		switch ($this->getDeletedFieldColumnType($model)) {
			case 'boolean':
				return array($this->getModelFieldName($model) => false);
			case 'datetime':
				return array($this->getModelFieldName($model) => null);
		}
	}

	public function softDelete(Model $model, $id = null)
	{
		// Remember current id
		$original_id = $model->id;

		if ($id)
			$model->id = $id;

		$result = $this->markRecordAsDeleted($model);

		// Restore original id
		$model->id = $original_id;

		return $result;
	}

	protected function markRecordAsDeleted(Model $model)
	{
		switch ($this->getDeletedFieldColumnType($model)) {
			case 'boolean':
				return $model->saveField($this->getFieldName($model), true);
			case 'datetime':
				return $model->saveField($this->getFieldName($model), date('Y-m-d H:i:s'));
		}
	}

	// $callbacks is ignored atm, and they are always called
	public function softDeleteAll(Model $model, $conditions, $cascade, $callbacks)
	{
		$ids = $model->find('all', array_merge(array(
			'fields' => "{$model->alias}.{$model->primaryKey}",
			'recursive' => 0), compact('conditions'))
		);
		if ($ids === null) {
			return false;
		}

		$ids = Hash::extract($ids, "{n}.{$model->alias}.{$model->primaryKey}");
		if (empty($ids)) {
			return true;
		}

		$result = true;
		foreach ($ids as $id) {
			$result = ($result && $model->delete($id, $cascade));
		}
		return $result;
	}

	/**
	 * Intercept calls to delete and soft delete instead
	 * @param  Model   $model
	 * @param  boolean $cascade Cascade delete to associated models
	 * @return boolean          Always false so record isn't properly deleted
	 */
	public function beforeDelete(Model $model, $cascade = true)
	{
	}

	/**
	 * Don't automatically exclude deleted records from results
	 * @param  Model  $model
	 * @return void
	 */
	public function includeDeletedRecords(Model $model)
	{
		$this->include_deleted_records = true;
	}

	/**
	 * Go back to automatically excluding deleted records from results
	 * @param  Model  $model
	 * @return void
	 */
	public function excludeDeletedRecords(Model $model)
	{
		$this->include_deleted_records = false;
	}

	/**
	 * Attempt to see if deleted field is used in find
	 * Checks for simple usage but will likely miss many edge cases
	 * @param  array $conditions Find conditions
	 * @return boolean           Whether deleted field is present
	 */
	protected function checkForDeletedField(Model $model, $conditions)
	{
		$field_name = $this->getFieldName($model);
		$model_field_name = $this->getModelFieldName($model);

		return (   isset($conditions[$field_name])
		        or isset($conditions[$model_field_name])
		        or isset($conditions['OR'][$field_name])
		        or isset($conditions['OR'][$model_field_name])
		        or isset($conditions['NOT'][$field_name])
		        or isset($conditions['NOT'][$model_field_name]));
	}

	protected function getFieldName(Model $model)
	{
		return $this->settings[$model->alias]['field_name'];
	}

	protected function getModelFieldName(Model $model)
	{
		$field_name = $this->getFieldName($model);
		return "{$model->alias}.$field_name";
	}

	protected function getDeletedFieldColumnType(Model $model)
	{
		if (empty($this->settings[$model->alias]['column_type']))
			$this->settings[$model->alias]['column_type'] = $model->getColumnType(
				$this->getFieldName($model)
			);

		return $this->settings[$model->alias]['column_type'];
	}
}