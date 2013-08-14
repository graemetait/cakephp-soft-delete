# CakePHP Soft Delete Plugin

Soft delete for CakePHP 2.x models. Transparently flags records as deleted instead of actually removing them. Will also then hide the deleted records from find operations.

## Installation

If you're using composer then just add the following to your require block.

		"burriko/cake-soft-delete": "2.0.*@dev"

If you're not, then clone/copy the contents of this directory to app/Plugins/CakeSoftDelete.

## Configure

1. Add the following line to your app/Config/bootstrap.php.

		CakePlugin::load('CakeSoftDelete');

2. Change your AppModel to extend SoftDeletableModel. You'll also need to add an appropriate App::uses to tell Cake where to load SoftDeletableModel. Basically Your AppModel class should start something like this.

		<?php

		App::uses('SoftDeletableModel', 'CakeSoftDelete.Model');

		class AppModel extends SoftDeletableModel

3. In the model that should be soft deletable add:

		public $actsAs = array('CakeSoftDelete.SoftDeletable');

4. Your model's database schema will need a field to act as a flag for whether a record has been deleted. By default this field is called 'deleted', but this can be changed in the behavior's settings. The field can be either a boolean or datetime (in which case it will be set to the time the field was deleted).

## Usage

Now when you delete records from this model they should just be soft deleted instead. Soft deleted records will not be present in results from find functions.

To include deleted records in results call the includeDeletedRecords() method on the model. You can then call excludeDeletedRecords() to hide them again.

## Limitations

Deletes will be cascaded to related models as usual, except for HABTM relations. This hasn't been a problem for me but is something to be aware of.
