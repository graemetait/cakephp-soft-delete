<?php

App::uses('Model', 'Model');

class SoftDeletableModel extends Model
{
	/**
	 * Override delete() to use the softDelete() method from behavior
	 * Much better results than using beforeDelete() as you can return true
	 * and use the protected methods for deleting related records
	 * Bring on traits!
	 * @param  int     $id      Record ID to be deleted
	 * @param  boolean $cascade Whether to delete related records
	 * @return boolean          Whether the delete was successful
	 */
	public function delete($id = null, $cascade = true)
	{
		if ($this->Behaviors->hasMethod('softDelete')) {
			if ($cascade) {
				// Can't call these from behavior as they're protected

				// Delete related models marked as dependent
				$this->_deleteDependent($id, $cascade);

				// Delete HABTM links - Bad idea?
				// $this->_deleteLinks($id);
			}
			return $this->softDelete($id, $cascade);
		} else {
			return parent::delete($id, $cascade);
		}
	}

	/**
	 * Override deleteAll() to use softDeleteAll() from behavior
	 * Necessary as can't be done using callbacks
	 * @param  array   $conditions Search conditions to find records to delete
	 * @param  boolean $cascade    Whether to delete related records
	 * @param  boolean $callbacks  Whether to call callbacks
	 * @return boolean             Whether the deletes were successful
	 */
	public function deleteAll($conditions, $cascade = true, $callbacks = false)
	{
		if (empty($conditions))
			return false;

		if ($this->Behaviors->hasMethod('softDeleteAll')) {
			return $this->softDeleteAll($conditions, $cascade, $callbacks);
		} else {
			return parent::deleteAll($conditions, $cascade, $callbacks);
		}
	}
}