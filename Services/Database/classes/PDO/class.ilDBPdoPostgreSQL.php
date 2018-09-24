<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once('./Services/Database/classes/PDO/Manager/class.ilDBPdoManagerPostgres.php');
require_once('class.ilDBPdo.php');
require_once('./Services/Database/classes/PDO/FieldDefinition/class.ilDBPdoPostgresFieldDefinition.php');
require_once('./Services/Database/classes/PDO/Reverse/class.ilDBPdoReversePostgres.php');

/**
 * Class ilDBPdoPostgreSQL
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ilDBPdoPostgreSQL extends ilDBPdo implements ilDBInterface {

	const POSTGRE_STD_PORT = 5432;
	/**
	 * @var int
	 */
	protected $port = self::POSTGRE_STD_PORT;
	/**
	 * @var string
	 */
	protected $storage_engine = null;
	/**
	 * @var ilDBPdoManagerPostgres
	 */
	protected $manager;


	public function generateDSN() {
		if (!$this->getPort()) {
			$this->setPort(self::POSTGRE_STD_PORT);
		}
		$this->dsn = 'pgsql:host=' . $this->getHost() . ';port=' . $this->getPort() . ';dbname=' . $this->getDbname() . ';user='
		             . $this->getUsername() . ';password=' . $this->getPassword() . '';
	}


	/**
	 * @param bool $return_false_for_error
	 * @return bool
	 * @throws \Exception
	 */
	public function connect($return_false_for_error = false) {
		$this->generateDSN();
		try {
			$this->pdo = new PDO($this->getDSN(), $this->getUsername(), $this->getPassword(), $this->getAttributes());
			$this->initHelpers();
		} catch (Exception $e) {
			$this->error_code = $e->getCode();
			if ($return_false_for_error) {
				return false;
			}
			throw $e;
		}

		return ($this->pdo->errorCode() == PDO::ERR_NONE);
	}


	protected function getAdditionalAttributes() {
		return array(
			PDO::ATTR_EMULATE_PREPARES => true,
		);
	}


	public function initHelpers() {
		$this->manager = new ilDBPdoManagerPostgres($this->pdo, $this);
		$this->reverse = new ilDBPdoReversePostgres($this->pdo, $this);
		$this->field_definition = new ilDBPdoPostgresFieldDefinition($this);
	}


	/**
	 * Primary key identifier
	 */
	function getPrimaryKeyIdentifier() {
		return "pk";
	}


	/**
	 * @return bool
	 */
	public function supportsFulltext() {
		return false;
	}


	/**
	 * @return bool
	 */
	public function supportsTransactions() {
		return true;
	}


	/**
	 * @param $a_table
	 * @param $a_constraint
	 * @return string
	 */
	public function constraintName($a_table, $a_constraint) {
		$a_constraint = str_replace($a_table . '_', '', $a_constraint);

		return $a_table . '_' . $a_constraint;
	}


	/**
	 * @param $index_name_base
	 * @return string
	 */
	public function getIndexName($index_name_base) {
		return parent::getIndexName($index_name_base); // TODO: Change the autogenerated stub
	}


	/**
	 * @param $a_table
	 * @param $a_pk_columns
	 * @param $a_other_columns
	 * @return bool
	 * @throws \ilDatabaseException
	 */
	public function replace($a_table, $a_pk_columns, $a_other_columns) {
		$a_columns = array_merge($a_pk_columns, $a_other_columns);
		$fields = array();
		$field_values = array();
		$placeholders = array();
		$types = array();
		$values = array();
		$lobs = false;
		$lob = array();
		$val_field = array();
		$a = array();
		$b = array();
		foreach ($a_columns as $k => $col) {
			if ($col[0] == 'clob' or $col[0] == 'blob') {
				$val_field[] = $this->quote($col[1], 'text') . " " . $k;
			} else {
				$val_field[] = $this->quote($col[1], $col[0]) . " " . $k;
			}
			$fields[] = $k;
			$placeholders[] = "%s";
			$placeholders2[] = ":$k";
			$types[] = $col[0];
			$values[] = $col[1];
			$field_values[$k] = $col[1];
			if ($col[0] == "blob" || $col[0] == "clob") {
				$lobs = true;
				$lob[$k] = $k;
			}
			$a[] = "a." . $k;
			$b[] = "b." . $k;
		}
		$abpk = array();
		$aboc = array();
		$delwhere = array();
		foreach ($a_pk_columns as $k => $col) {
			$abpk[] = "a." . $k . " = b." . $k;
			$delwhere[] = $k . " = " . $this->quote($col[1], $col[0]);
		}
		foreach ($a_other_columns as $k => $col) {
			$aboc[] = "a." . $k . " = b." . $k;
		}
		//		if ($lobs)	// lobs -> use prepare execute (autoexecute broken in PEAR 2.4.1)
		//		{
		$this->manipulate("DELETE FROM " . $a_table . " WHERE " . implode($delwhere, " AND "));
		$this->insert($a_table, $a_columns);

		return true;
	}


	/**
	 * @param array $a_tables
	 * @deprecated Use ilAtomQuery instead
	 * @return bool
	 */
	public function lockTables($a_tables) {

		$locks = array();

		$counter = 0;
		foreach ($a_tables as $table) {
			if (!isset($table['sequence']) && $table['sequence']) {
				$lock = 'LOCK TABLE ' . $table['name'];

				switch ($table['type']) {
					case ilDBConstants::LOCK_READ:
						$lock .= ' IN SHARE MODE ';
						break;

					case ilDBConstants::LOCK_WRITE:
						$lock .= ' IN EXCLUSIVE MODE ';
						break;
				}

				$locks[] = $lock;
			}
		}

		// @TODO use and store a unique identifier to allow nested lock/unlocks
		$this->beginTransaction();
		foreach ($locks as $lock) {
			$this->query($lock);
		}

		return true;
	}


	/**
	 * @throws \ilDatabaseException
	 * @deprecated Use ilAtomQuery instead
	 */
	public function unlockTables() {
		$this->commit();
	}


	public function getStorageEngine() {
		return null;
	}


	public function setStorageEngine($storage_engine) {
		return false;
	}

	//
	//
	//

	/**
	 * @param string $table_name
	 * @return mixed
	 * @throws \ilDatabaseException
	 */
	public function nextId($table_name) {
		$sequence_name = $table_name . '_seq';
		$query = "SELECT NEXTVAL('$sequence_name')";
		$result = $this->query($query, 'integer');
		$data = $result->fetchObject();

		return $data->nextval;
	}


	/**
	 * @param $table_name
	 * @param bool $error_if_not_existing
	 * @return int
	 */
	public function dropTable($table_name, $error_if_not_existing = false) {
		try {
			$this->pdo->exec("DROP TABLE $table_name");
		} catch (PDOException $PDOException) {
			if ($error_if_not_existing) {
				throw $PDOException;
			}

			return false;
		}

		return true;
	}


	/**
	 * @param $identifier
	 * @param bool $check_option
	 * @return mixed
	 */
	public function quoteIdentifier($identifier, $check_option = false) {
		return '"'.$identifier.'"';
	}


	/**
	 * @param string $table_name
	 * @return bool
	 */
	public function tableExists($table_name) {
		$tables = $this->listTables();

		if (is_array($tables)) {
			if (in_array($table_name, $tables)) {
				return true;
			}
		}

		return false;
	}


	/**
	 * @param $query
	 * @return string
	 */
	protected function appendLimit($query) {
		if ($this->limit !== null && $this->offset !== null) {
			$query .= ' LIMIT ' . (int)$this->limit . ' OFFSET ' . (int)$this->offset;
			$this->limit = null;
			$this->offset = null;

			return $query;
		}

		return $query;
	}


	/**
	 * @param $table_name  string
	 * @param $column_name string
	 *
	 * @return bool
	 */
	public function tableColumnExists($table_name, $column_name) {
		return in_array($column_name, $this->manager->listTableFields($table_name));
	}


	/**
	 * @param $a_name
	 * @param $a_new_name
	 * @return bool
	 * @throws \ilDatabaseException
	 */
	public function renameTable($a_name, $a_new_name) {
		// check table name
		try {
			$this->checkTableName($a_new_name);
		} catch (ilDatabaseException $e) {
			return true;
		}

		if ($this->tableExists($a_new_name)) {
			return true;
		}
		try {
			$this->manager->alterTable($a_name, array( "name" => $a_new_name ), false);
		} catch (Exception $e) {
			return true;
		}

		return true;
	}


	/**
	 * @param $table_name
	 * @param int $start
	 * @return bool
	 */
	public function createSequence($table_name, $start = 1) {
		if (in_array($table_name, $this->manager->listSequences())) {
			return true;
		}
		try {
			parent::createSequence($table_name, $start); // TODO: Change the autogenerated stub
		} catch (Exception $e) {
			return true;
		}
	}


	/**
	 * @param $table_name
	 * @param $fields
	 * @param bool $drop_table
	 * @param bool $ignore_erros
	 * @return bool|mixed
	 * @throws \ilDatabaseException
	 */
	public function createTable($table_name, $fields, $drop_table = false, $ignore_erros = false) {
		if ($this->tableExists($table_name)) {
			return true;
		}
		try {
			return parent::createTable($table_name, $fields, $drop_table, $ignore_erros); // TODO: Change the autogenerated stub
		} catch (Exception $e) {
			return true;
		}
	}


	/**
	 * @param string $table_name
	 * @param array $primary_keys
	 * @return bool
	 */
	public function addPrimaryKey($table_name, $primary_keys) {
		require_once('./Services/Database/classes/class.ilDBAnalyzer.php');
		$ilDBAnalyzer = new ilDBAnalyzer($this);
		if ($ilDBAnalyzer->getPrimaryKeyInformation($table_name)) {
			return true;
		}
		try {
			return parent::addPrimaryKey($table_name, $primary_keys); // TODO: Change the autogenerated stub
		} catch (Exception $e) {
			return true;
		}
	}


	public function addIndex($table_name, $fields, $index_name = '', $fulltext = false) {
		$indices = $this->manager->listTableIndexes($table_name);
		if (in_array($this->constraintName($table_name, $index_name), $indices)) {
			return true;
		}
		try {
			return parent::addIndex($table_name, $fields, $index_name, $fulltext); // TODO: Change the autogenerated stub
		} catch (Exception $e) {
			return true;
		}
	}


	public function addUniqueConstraint($table, $fields, $name = "con") {
		try {
			return parent::addUniqueConstraint($table, $fields, $name); // TODO: Change the autogenerated stub
		} catch (Exception $e) {
			return true;
		}
	}

	/**
	 * @param $table_name
	 * @return bool
	 */
	public function dropPrimaryKey($table_name) {
		return $this->manager->dropConstraint($table_name, "pk", true);
	}

}

