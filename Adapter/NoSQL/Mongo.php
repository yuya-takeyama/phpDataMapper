<?php
require_once(dirname(dirname(__FILE__)) . '/Abstract.php');
require_once(dirname(dirname(__FILE__)) . '/Interface.php');
/**
 * MongoDB NoSQL Adapter
 *
 * @package phpDataMapper
 * @link http://phpdatamapper.com
 */
class phpDataMapper_Adapter_NoSQL_Mongo extends phpDataMapper_Adapter_Abstract implements phpDataMapper_Adapter_Interface
{
	// Format for date columns, formatted for PHP's date() function
	protected $format_date = 'Y-m-d';
	protected $format_time = 'H:i:s';
	protected $format_datetime = 'Y-m-d H:i:s';
	
	
	// Connection details
	protected $connection;
	protected $host;
	protected $database;
	protected $username;
	protected $password;
	protected $options;
	
	// Mongo caches
	protected $_mongoCollection;
	protected $_mongoDatabase;
	
    /**
    * @param mixed $host Host string or pre-existing Mongo object
	* @param string $database Optional if $host is Mongo object
    * @param string $username Optional if $host is Mongo object
    * @param string $password Optional if $host is Mongo object
    * @param array $options
    * @return void
    */
    public function __construct($host, $database = null, $username = null, $password = null, array $options = array())
    {
		// Ensure Mongo PHP extension is loaded (required to work)
		if(!extension_loaded('mongo')) {
			throw new phpDataMapper_Exception("MongoDB PHP extension required for this adapter");
		}
		
    	if($host instanceof Mongo) {
    		$this->connection = $host;
    	} else {
			$this->host = $host;
			$this->database = $database;
			$this->username = $username;
			$this->password = $password;
			$this->options = $options;
			
			// Establish connection
			try {
				$this->connection = new Mongo($this->dsn(), $this->options);
			} catch(Exception $e) {
				throw new phpDataMapper_Exception($e->getMessage());
			}
    	}
    }
	
	
	/**
	 * Get database connection
	 * 
	 * @return object Mongo
	 */
	public function connection()
	{
		return $this->connection;
	}
	
	
	/**
	 * Get DSN string to connect with
	 * 
	 * @return string
	 */
	public function dsn()
	{
		$dsn = 'mongodb://';
		if($this->username) {
			$dsn .= ($this->username) ? $this->username : '';
			$dsn .= ($this->password) ? ':' . $this->password : '';
			$dsn .= '@';
		}
		$dsn .= $this->host;
		//$dsn .= ($this->database) ? '/' . $this->database : ''; // Uses 'selectDB' instead (above) so we can attept to create db if it does not exist for migrations
		return $dsn;
	}
	
	
	/**
	 * Get database format
	 *
	 * @return string Date format for PHP's date() function
	 */
	public function dateFormat()
	{
		return $this->format_date;
	}
	
	
	/**
	 * Get database time format
	 *
	 * @return string Time format for PHP's date() function
	 */
	public function timeFormat()
	{
		return $this->format_time;
	}
	
	
	/**
	 * Get database format
	 *
	 * @return string DateTime format for PHP's date() function
	 */
	public function dateTimeFormat()
	{
		return $this->format_datetime;
	}
	
	
	/**
	 * Get date
	 *
	 * @return object MongoDate
	 */
	public function date($format = null)
	{
		// MongoDB only supports timestamps for now, not direct DateTime objects
		$format = parent::date($format)->format('U');
		return new MongoDate($format);
	}
	
	
	/**
	 * Get database time format
	 *
	 * @return object MongoDate 
	 */
	public function time($format = null)
	{
		// MongoDB only supports timestamps for now, not direct DateTime objects
		$format = parent::time($format)->format('U');
		return new MongoDate($format);
	}
	
	
	/**
	 * Get datetime
	 *
	 * @return object MongoDate
	 */
	public function dateTime($format = null)
	{
		// MongoDB only supports timestamps for now, not direct DateTime objects
		$format = parent::dateTime($format)->format('U');
		return new MongoDate($format);
	}
	
	
	/**
	 * Escape/quote direct user input
	 *
	 * @param string $string
	 */
	public function escape($string)
	{
		return $string; // Don't think Mongo needs escaping, it's not SQL, and the JSON encoding takes care of properly escaping values...
	}
	
	
	/**
	 * Migrate structure changes to database
	 * 
	 * @param String $datasource Datasource name
	 * @param Array $fields Fields and their attributes as defined in the mapper
	 */
	public function migrate($datasource, array $fields)
	{
		// @todo Possibly add 'ensureIndex' function calls in case of modified field definitions with added indexes
		return true; // Whoo! NoSQL doesn't have set schema!
	}
	
	
	/**
	 * Create new entity record with set properties
	 */
	public function create($datasource, array $data)
	{
		// @link http://us3.php.net/manual/en/mongocollection.insert.php
		return $this->mongoCollection($datasource)->insert($data);
	}
	
	
	/**
	 * Build a Mongo query
	 *
	 * Finding: @link http://us.php.net/manual/en/mongocollection.find.php
	 * Fields: @link http://us.php.net/manual/en/mongocursor.fields.php
	 * Cursor: @link http://us.php.net/manual/en/class.mongocursor.php
	 * Sorting: @link http://us.php.net/manual/en/mongocursor.sort.php
	 */
	public function read(phpDataMapper_Query $query)
	{
		// Get MongoCursor first - it's required for other options
		$mongoQuery = $this->mongoCollection()
		
		// Organize 'order' options for sorting
		$order = array();
		if($query->order) {
			foreach($query->order as $oField => $oSort) {
				// MongoDB sorting: ASC: 1,  DESC: 2
				$order[$oField] = ($oSort == 'DESC') ? 2 : 1;
			}
		}
		
		// GROUP BY
		if($query->group) {
			throw new phpDataMapper_Exception("Mongo adapter does not current support grouping");
		}
		
		// LIMIT
		if($query->limit) {
			
		}
		/*
			" . ($query->limit ? 'LIMIT ' . $query->limit : '') . " " . ($query->limit && $query->limitOffset ? 'OFFSET ' . $query->limitOffset: '') . "
		*/
		
		return false;
	}
	
	/**
	 * Update entity
	 */
	public function update($datasource, array $data, array $where = array())
	{
		// @todo Check on the _id field to ensure it is set - Mongo can only 'update' existing records or you get an exception
		
		return false;
	}
	
	
	/**
	 * Delete entities matching given conditions
	 *
	 * @param string $datasource Name of data source
	 * @param array $conditions Array of conditions in column => value pairs
	 */
	public function delete($datasource, array $data)
	{
		return false;
	}
	
	
	/**
	 * Truncate a database table
	 * Should delete all rows and reset serial/auto_increment keys to 0
	 */
	public function truncateDatasource($datasource)
	{
		return false;
	}
	
	
	/**
	 * Drop a database table
	 * Destructive and dangerous - drops entire table and all data
	 */
	public function dropDatasource($datasource)
	{
		return false;
	}
	
	
	/**
	 * Create a database
 	 * Will throw errors if user does not have proper permissions
	 */
	public function createDatabase($database)
	{
		return false;
	}
	
	
	/**
	 * Drop a database table
	 * Destructive and dangerous - drops entire table and all data
	 * Will throw errors if user does not have proper permissions
	 */
	public function dropDatabase($database)
	{
		return false;
	}
	
	
	/**
	 * Return result set for current query
	 */
	public function toCollection(phpDataMapper_Query $query, $stmt)
	{
		return false;
	}
	
	
	/**
	 * Returns current Mongo database to use
	 */
	protected function mongoDatabase()
	{
		if(empty($this->_mongoDatabase)) {
			if(!$this->database) {
				throw new phpDataMapper_Exception("Mongo must have a database to connect to. No database name was specified.");
			}
			$this->connection(); // Just call to ensure we have a db connection established
			$this->_mongoDatabase = $this->connection->selectDB($this->database);
		}
		return $this->_mongoDatabase;
	}
	
	
	/**
	 * Returns current Mongo collection to use
	 */
	protected function mongoCollection($collectionName)
	{
		if(!isset($this->_mongoCollection[$collectionName])) {
			$this->_mongoCollection[$collectionName] = $this->mongoDatabase()->selectCollection($collectionName);
		}
		return $this->_mongoCollection[$collectionName];
	}
}