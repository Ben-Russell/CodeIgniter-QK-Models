CodeIgniter QK Models
========

Extends CI_Model to automatically provide CRUD support for models.


## Installation

Put MY_Model.php in your *application/core/* directory.

## Using QK Models

### Creating a Model

To use a QK Model, first define a model with properties matching their column names. Then simply add in the `_table` and `_key` meta properties.

	class User extends MY_Model
	{
		public UserID;
		public UserName;
		public Name;
		public Email;
		public Age;
		public IsAdmin;


		protected static $_table = "AppDB.Users";
		protected static $_key = "UserID"
	}

You model now already has CRUD support!

### Using the Model

Now that you have your model let's use it:

#### Select Models

Select Models from the database by using the `GetItemByFilter` method.

	// Get a single model instance
	$user = User::GetItemByFilter(array("UserName" => "MyUserName17"));

	// Get a list of model instances
	$users = User::GetItemsByFilter(array("Email" => "MyEmail@email.com", "Name" => "MyName"));



	// Call any CI Active Record helper method to query a model
	$leetadmins = User::GetItemsByFilter( array("IsAdmin" => true, "Age >" => 1),
		array(
			"like" => array("Email" => "%@admins.com")
			"order_by" => array("Name" => "desc")			
		) );

#### Update Models

For updating models make sure that the _key meta property is set with the table's unique primary key.

These methods will update a model's record in the database.

	// Update a model
	$user->Update();

	// Update a model using static method
	User::UpdateItem($user);

	// Batch Update multiple models from list
	User::UpdateItems($users);

#### Delete Models

For deleting models make sure that the _key meta property is set with the table's unique primary key.

These methods will delete a model's record in the database. (Careful as this will immediately delete records from the database)

	// Delete a model
	$user->Delete();

	// Delete a model using static method
	User::DeleteItem($user);

	// Batch Delete multiple models from list
	User::DeleteItems($users);

#### Inserting Models

These methods will insert a new model record in the database. After an Insert is complete, the property referenced by the meta property _key will be updated with the newly inserted id (id returned from db->insert_id()).


	// Create a new instance of a model
	$newuser = new User();

	$newuser.UserName = "MyUserName18";
	$newuser.Name = "MyName";
	$newuser.Email = "MyEmail@email.com";
	$newuser.Age = 1;
	$newuser.IsAdmin = false;


	// Insert the new instance into the database
	$newuser->Insert();

	// Insert the new instance using static method
	User::InsertItem($newuser);

	// Insert a list of new model instances
	User::InsertItems($listofnewusers);

#### Relationships

A third meta property `_fkey` can be used to define foreign key relationships to the model.
When a model is selected, it will automatically select/bind any related objects.

	class Supplier extends MY_Model
	{
		public SupplierID;
		public Name;

		protected static $_table = "AppDB.Suppliers";
		protected static $_key = "SupplierID"
	}

	class Product extends MY_Model
	{
		public ProductID;
		public Name;
		public SupplierID;

		public Supplier;

		protected static $_table = "AppDB.Products";
		protected static $_key = "ProductID"
		protected static $_fkey = array(
			"SupplierID" => "Supplier"
		);
	}

	// Select a product
	$product = Product::GetItemByFilter(array("Name" => "Super Gizmo"));

	// Supplier can be accessed
	$supplier = Product.Supplier;