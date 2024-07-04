<?php

namespace Drupal\sqlsrv\Driver\Database\sqlsrv;

use Drupal\Core\Database\Connection as DatabaseConnection;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseAccessDeniedException;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\DatabaseNotFoundException;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Database\StatementWrapper;
use Drupal\Core\Database\TransactionNoActiveException;
use Drupal\Core\Database\TransactionOutOfOrderException;
use Drupal\Core\Database\TransactionNameNonUniqueException;

/**
 * Sqlsvr implementation of \Drupal\Core\Database\Connection.
 */
class Connection extends DatabaseConnection implements SupportsTemporaryTablesInterface {

  /**
   * {@inheritdoc}
   */
  protected $statementClass = NULL;

  /**
   * {@inheritdoc}
   */
  protected $statementWrapperClass = StatementWrapper::class;

  /**
   * The identifier quote characters for the database type.
   *
   * An array containing the start and end identifier quote characters for the
   * database type. The ANSI SQL standard identifier quote character is a double
   * quotation mark.
   *
   * @var string[]
   */
  protected $identifierQuotes = ['[', ']'];

  /**
   * Error code for "Unknown database" error.
   */
  const DATABASE_NOT_FOUND = 42000;

  /**
   * Error code for "Access denied" error.
   */
  const ACCESS_DENIED = 28000;

  /**
   * This is the original replacement regexp from Microsoft.
   *
   * We could probably simplify it a lot because queries only contain
   * placeholders when we modify them.
   *
   * NOTE: removed 'escape' from the list, because it explodes
   * with LIKE xxx ESCAPE yyy syntax.
   */
  const RESERVED_REGEXP = '/\G
    # Everything that follows a boundary that is not : or _.
    \b(?<![:\[_])(?:
      # Any reserved words, followed by a boundary that is not an opening parenthesis.
      (action|admin|alias|any|are|array|at|begin|boolean|class|commit|contains|current|
      data|date|day|depth|domain|external|file|full|function|get|go|host|input|language|
      last|less|local|map|min|module|new|no|object|old|open|operation|parameter|parameters|
      path|plan|prefix|proc|public|ref|result|returns|role|row|rule|save|search|second|
      section|session|size|state|statistics|temporary|than|time|timestamp|tran|translate|
      translation|trim|user|value|variable|view|without)
      (?!\()
      |
      # Or a normal word.
      ([a-z]+)
    )\b
    |
    \b(
      [^a-z\'"\\\\]+
    )\b
    |
    (?=[\'"])
    (
      "  [^\\\\"] * (?: \\\\. [^\\\\"] *) * "
      |
      \' [^\\\\\']* (?: \\\\. [^\\\\\']*) * \'
    )
  /Six';

  /**
   * The schema object for this connection.
   *
   * Set to NULL when the schema is destroyed.
   *
   * @var \Drupal\sqlsrv\Driver\Database\sqlsrv\Schema|null
   */
  protected $schema = NULL;

  /**
   * The temporary table prefix.
   *
   * @var string
   */
  protected $tempTablePrefix = '#';

  /**
   * The connection's unique key for global temporary tables.
   *
   * @var string
   */
  protected $tempKey;

  /**
   * {@inheritdoc}
   */
  public function queryRange($query, $from, $count, array $args = [], array $options = []) {
    if (strpos($query, " ORDER BY ") === FALSE) {
      $query .= " ORDER BY (SELECT NULL)";
    }
    $query .= " OFFSET {$from} ROWS FETCH NEXT {$count} ROWS ONLY";
    return $this->query($query, $args, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function queryTemporary($query, array $args = [], array $options = []) {
    $tablename = 'db_temporary_' . bin2hex(random_bytes(16));

    // Having comments in the query can be tricky and break the
    // SELECT FROM  -> SELECT INTO conversion.
    /** @var \Drupal\sqlsrv\Driver\Database\sqlsrv\Schema $schema */
    $schema = $this->schema();
    $query = $schema->removeSQLComments($query);

    // Replace SELECT xxx FROM table by SELECT xxx INTO #table FROM table.
    $query = preg_replace('/^SELECT(.*?)FROM/is', 'SELECT$1 INTO {' . $tablename . '} FROM', $query);
    $this->query($query, $args, $options);

    return $tablename;
  }

  /**
   * {@inheritdoc}
   */
  public function driver() {
    return 'sqlsrv';
  }

  /**
   * {@inheritdoc}
   */
  public function databaseType() {
    return 'sqlsrv';
  }

  /**
   * {@inheritdoc}
   */
  public function createDatabase($database) {
    // Escape the database name.
    $database = Database::getConnection()->escapeDatabase($database);

    try {
      // Create the database and set it as active.
      $this->connection->exec("CREATE DATABASE $database");
    }
    catch (\Exception $e) {
      throw new DatabaseNotFoundException($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function mapConditionOperator($operator) {
    return isset(static::$sqlsrvConditionOperatorMap[$operator]) ? static::$sqlsrvConditionOperatorMap[$operator] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function nextId($existing = 0) {
    // If an exiting value is passed, for its insertion into the sequence table.
    if ($existing > 0) {
      try {
        $options = [];
        $options['allow_delimiter_in_query'] = TRUE;
        $sql = 'SET IDENTITY_INSERT {sequences} ON;';
        $sql .= ' INSERT INTO {sequences} (value) VALUES(:existing);';
        $sql .= ' SET IDENTITY_INSERT {sequences} OFF';
        $this->queryDirect($sql, [':existing' => $existing], $options);
      }
      catch (\Exception $e) {
        // Doesn't matter if this fails, it just means that this value is
        // already present in the table. However, unexpected exceptions
        // should be rethrown.
      }
    }
    // Under high concurrency LAST_INSERTED_ID does not work properly.
    // Use OUTPUT.
    return $this->queryDirect('INSERT INTO {sequences} OUTPUT (Inserted.[value]) DEFAULT VALUES')->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(\PDO $connection, array $connection_options) {
    try {
      $connection->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, TRUE);
    }
    catch (\PDOException)
    {
      // Catch exception from upstream pdo_sqlsrv bug triggered by php
      // 8.1.22 / 8.2.9. No action needed to handle.
      // @see https://www.drupal.org/project/sqlsrv/issues/3379887
    }
    parent::__construct($connection, $connection_options);

    // This driver defaults to transaction support, except if explicitly passed
    // FALSE.
    $this->transactionalDDLSupport = !isset($connection_options['transactions']) || $connection_options['transactions'] !== FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * Adding schema to the connection URL.
   */
  public static function createConnectionOptionsFromUrl($url, $root) {
    $database = parent::createConnectionOptionsFromUrl($url, $root);
    $url_components = parse_url($url);
    if (isset($url_components['query'])) {
      $query = [];
      parse_str($url_components['query'], $query);
      if (isset($query['schema'])) {
        $database['schema'] = $query['schema'];
      }
      $database['cache_schema'] = isset($query['cache_schema']) && $query['cache_schema'] == 'true' ? TRUE : FALSE;
    }
    return $database;
  }

  /**
   * {@inheritdoc}
   *
   * Adding schema to the connection URL.
   */
  public static function createUrlFromConnectionOptions(array $connection_options) {
    if (!isset($connection_options['driver'], $connection_options['database'])) {
      throw new \InvalidArgumentException("As a minimum, the connection options array must contain at least the 'driver' and 'database' keys");
    }

    $user = '';
    if (isset($connection_options['username'])) {
      $user = $connection_options['username'];
      if (isset($connection_options['password'])) {
        $user .= ':' . $connection_options['password'];
      }
      $user .= '@';
    }

    $host = empty($connection_options['host']) ? 'localhost' : $connection_options['host'];

    $db_url = $connection_options['driver'] . '://' . $user . $host;

    if (isset($connection_options['port'])) {
      $db_url .= ':' . $connection_options['port'];
    }

    $db_url .= '/' . $connection_options['database'];
    $query = [];
    if (isset($connection_options['module'])) {
      $query['module'] = $connection_options['module'];
    }
    if (isset($connection_options['schema'])) {
      $query['schema'] = $connection_options['schema'];
    }
    if (isset($connection_options['cache_schema'])) {
      $query['cache_schema'] = $connection_options['cache_schema'];
    }

    if (count($query) > 0) {
      $parameters = [];
      foreach ($query as $key => $values) {
        $parameters[] = $key . '=' . $values;
      }
      $query_string = implode("&amp;", $parameters);
      $db_url .= '?' . $query_string;
    }
    if (isset($connection_options['prefix']['default']) && $connection_options['prefix']['default'] !== '') {
      $db_url .= '#' . $connection_options['prefix']['default'];
    }

    return $db_url;
  }

  /**
   * {@inheritdoc}
   *
   * Including schema name.
   */
  public function getFullQualifiedTableName($table) {
    $options = $this->getConnectionOptions();
    $prefix = $this->tablePrefix($table);
    $schema_name = $this->schema->getDefaultSchema();
    return $options['database'] . '.' . $schema_name . '.' . $prefix . $table;
  }

  /**
   * {@inheritdoc}
   *
   * Uses SQL Server format.
   */
  public static function open(array &$connection_options = []) {

    // Build the DSN.
    $options = [];
    $options['Server'] = $connection_options['host'] . (!empty($connection_options['port']) ? ',' . $connection_options['port'] : '');

    // We might not have a database in the
    // connection options, for example, during
    // database creation in Install.
    if (!empty($connection_options['database'])) {
      $options['Database'] = $connection_options['database'];
    }

    // Set sqlsrv specific DSN options.
    if (isset($connection_options['readonly'])) {
      $options['ApplicationIntent'] = 'ReadOnly';
    }

    if (isset($connection_options['pooling']) && $connection_options['pooling'] === false) {
      $options['ConnectionPooling'] = '0';
    }

    if (isset($connection_options['appname'])) {
      $options['APP'] = $connection_options['appname'];
    }

    if (isset($connection_options['encrypt'])) {
      $options['Encrypt'] = $connection_options['encrypt'];
    }

    if (isset($connection_options['trust_server_certificate'])) {
      $options['TrustServerCertificate'] = $connection_options['trust_server_certificate'];
    }

    if (isset($connection_options['multiple_active_result_sets']) && $connection_options['multiple_active_result_sets'] === false) {
      $options['MultipleActiveResultSets'] = 'false';
    }

    if (isset($connection_options['transaction_isolation'])) {
      $options['TransactionIsolation'] = $connection_options['transaction_isolation'];
    }

    if (isset($connection_options['multi_subnet_failover'])) {
      $options['MultiSubnetFailover'] = $connection_options['multi_subnet_failover'];
    }

    if (isset($connection_options['column_encryption'])) {
      $options['ColumnEncryption'] = $connection_options['column_encryption'];
    }

    if (isset($connection_options['key_store_authentication'])) {
      $options['KeyStoreAuthentication'] = $connection_options['key_store_authentication'];
    }

    if (isset($connection_options['key_store_principal_id'])) {
      $options['KeyStorePrincipalId'] = $connection_options['key_store_principal_id'];
    }

    if (isset($connection_options['key_store_secret'])) {
      $options['KeyStoreSecret'] = $connection_options['key_store_secret'];
    }

    if (isset($connection_options['login_timeout'])) {
      $options['LoginTimeout'] = $connection_options['login_timeout'];
    }

    // Build the DSN.
    $dsn = 'sqlsrv:';
    foreach ($options as $key => $value) {
      $dsn .= (empty($key) ? '' : "{$key}=") . $value . ';';
    }

    // Allow PDO options to be overridden.
    $connection_options += [
      'pdo' => [],
    ];
    $connection_options['pdo'] += [
      \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
    ];

    // Set a Statement class, unless the driver opted out.
    // $connection_options['pdo'][PDO::ATTR_STATEMENT_CLASS] =
    // array(Statement::class, array(Statement::class));.
    // Actually instantiate the PDO.
    try {
      $pdo = new \PDO($dsn, $connection_options['username'], $connection_options['password'], $connection_options['pdo']);
    }
    catch (\PDOException $e) {
      if ($e->getCode() == static::DATABASE_NOT_FOUND) {
        throw new DatabaseNotFoundException($e->getMessage(), $e->getCode(), $e);
      }
      if ($e->getCode() == static::ACCESS_DENIED) {
        throw new DatabaseAccessDeniedException($e->getMessage(), $e->getCode(), $e);
      }
      throw $e;
    }
    return $pdo;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareStatement(string $query, array $options, bool $allow_row_count = FALSE): StatementInterface {
    $default_options = [
      'emulate_prepares' => FALSE,
      'bypass_preprocess' => FALSE,
      'allow_delimiter_in_query' => FALSE,
    ];

    // Merge default statement options. These options are
    // only specific for this preparation and will only override
    // the global configuration if set to different than NULL.
    $options += $default_options;

    // Preprocess the query.
    if (!$options['bypass_preprocess']) {
      $query = $this->preprocessQuery($query);
    }

    $driver_options = [];

    $driver_options['allow_delimiter_in_query'] = $options['allow_delimiter_in_query'];

    if ($options['emulate_prepares'] === TRUE) {
      // Never use this when you need special column binding.
      // Unlike other PDO drivers, sqlsrv requires this attribute be set
      // on the statement, not the connection.
      $driver_options['pdo'][\PDO::ATTR_EMULATE_PREPARES] = TRUE;
      $driver_options['pdo'][\PDO::SQLSRV_ATTR_ENCODING] = \PDO::SQLSRV_ENCODING_UTF8;
    }

    // We run the statements in "direct mode" because the way PDO prepares
    // statement in non-direct mode cause temporary tables to be destroyed
    // at the end of the statement.
    // If you are using the PDO_SQLSRV driver and you want to execute a query
    // that changes a database setting (e.g. SET NOCOUNT ON), use the PDO::query
    // method with the PDO::SQLSRV_ATTR_DIRECT_QUERY attribute.
    // http://blogs.iis.net/bswan/archive/2010/12/09/how-to-change-database-settings-with-the-pdo-sqlsrv-driver.aspx
    // If a query requires the context that was set in a previous query,
    // you should execute your queries with PDO::SQLSRV_ATTR_DIRECT_QUERY set to
    // True. For example, if you use temporary tables in your queries,
    // PDO::SQLSRV_ATTR_DrIRECT_QUERY must be set to True.
    $driver_options['pdo'][\PDO::SQLSRV_ATTR_DIRECT_QUERY] = TRUE;

    // It creates a cursor for the query, which allows you to iterate over the
    // result set without fetching the whole result at once. A scrollable
    // cursor, specifically, is one that allows iterating backwards.
    // https://msdn.microsoft.com/en-us/library/hh487158%28v=sql.105%29.aspx
    $driver_options['pdo'][\PDO::ATTR_CURSOR] = \PDO::CURSOR_SCROLL;

    // Lets you access rows in any order. Creates a client-side cursor query.
    $driver_options['pdo'][\PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE] = \PDO::SQLSRV_CURSOR_BUFFERED;

    /** @var \Drupal\Core\Database\Statement $stmt */
    $stmt = parent::prepareStatement($query, $driver_options, $allow_row_count);
    return $stmt;
  }

  /**
   * {@inheritdoc}
   *
   * SQL Server does not support RELEASE SAVEPOINT.
   */
  protected function popCommittableTransactions() {
    // Commit all the committable layers.
    foreach (array_reverse($this->transactionLayers) as $name => $active) {
      // Stop once we found an active transaction.
      if ($active) {
        break;
      }
      // If there are no more layers left then we should commit.
      unset($this->transactionLayers[$name]);
      if (empty($this->transactionLayers)) {
        $this->doCommit();
      }
      else {
        // Nothing to do in SQL Server.
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * Using SQL Server query syntax.
   */
  public function pushTransaction($name) {
    if (isset($this->transactionLayers[$name])) {
      throw new TransactionNameNonUniqueException($name . " is already in use.");
    }
    // If we're already in a transaction then we want to create a savepoint
    // rather than try to create another transaction.
    if ($this->inTransaction()) {
      $this->queryDirect('SAVE TRANSACTION ' . $name);
    }
    else {
      $this->connection->beginTransaction();
    }
    $this->transactionLayers[$name] = $name;
  }

  /**
   * {@inheritdoc}
   *
   * This method is overriden to manage EMULATE_PREPARE
   * behaviour to prevent some compatibility issues with SQL Server.
   */
  public function query($query, array $args = [], $options = []) {
    // Use default values if not already set.
    $options += $this->defaultOptions();
    assert(!isset($options['target']), 'Passing "target" option to query() has no effect. See https://www.drupal.org/node/2993033');

    // We allow either a pre-bound statement object (deprecated) or a literal
    // string. In either case, we want to end up with an executed statement
    // object, which we pass to StatementInterface::execute.
    if (is_string($query)) {
      $this->expandArguments($query, $args);
      $emulate = isset($options['emulate_prepares']) ? $options['emulate_prepares'] : FALSE;
      // Try to detect duplicate place holders, this check's performance
      // is not a good addition to the driver, but does a good job preventing
      // duplicate placeholder errors.
      $argcount = count($args);
      if ($emulate === TRUE || $argcount >= 2100 || ($argcount != substr_count($query, ':'))) {
        $emulate = TRUE;
      }
      $options['emulate_prepares'] = $emulate;
      // Replace CONCAT_WS to ensure SQL Server 2016 compatibility.
      while (($pos1 = strpos($query, 'CONCAT_WS')) !== FALSE) {
        // We assume the the separator does not contain any single-quotes
        // and none of the arguments contain commas.
        $pos2 = Utils::findParenMatch($query, $pos1 + 9);
        $argument_list = substr($query, $pos1 + 10, $pos2 - 10 - $pos1);
        $arguments = explode(', ', $argument_list);
        $closing_quote_pos = stripos($argument_list, '\'', 1);
        $separator = substr($argument_list, 1, $closing_quote_pos - 1);
        $strings_list = substr($argument_list, $closing_quote_pos + 3);
        $arguments = explode(', ', $strings_list);
        $replace = "STUFF(";
        $coalesce = [];
        foreach ($arguments as $argument) {
          if (substr($argument, 0, 1) == ':') {
            $args[$argument . '_sqlsrv_concat'] = $args[$argument];
            $coalesce[] = "CASE WHEN $argument IS NULL THEN '' ELSE CONCAT('{$separator}', {$argument}_sqlsrv_concat) END";
          }
          else {
            $coalesce[] = "CASE WHEN $argument IS NULL THEN '' ELSE CONCAT('{$separator}', {$argument}) END";
          }
        }
        $coalesce_string = implode(' + ', $coalesce);
        $sep_len = strlen($separator);
        $replace = "STUFF({$coalesce_string}, 1, {$sep_len}, '')";
        $query = substr($query, 0, $pos1) . $replace . substr($query, $pos2 + 1);
      }
      $stmt = $this->prepareStatement($query, $options);
    }
    elseif ($query instanceof StatementInterface) {
      @trigger_error('Passing a StatementInterface object as a $query argument to Drupal\\Core\\Database\\Connection::query is deprecated in drupal:9.2.0 and is removed in drupal:10.0.0. Call the execute method from the StatementInterface object directly instead. See https://www.drupal.org/node/3154439', E_USER_DEPRECATED);
      $stmt = $query;
    }
    elseif ($query instanceof \PDOStatement) {
      @trigger_error('Passing a \\PDOStatement object as a $query argument to Drupal\\Core\\Database\\Connection::query is deprecated in drupal:9.2.0 and is removed in drupal:10.0.0. Call the execute method from the StatementInterface object directly instead. See https://www.drupal.org/node/3154439', E_USER_DEPRECATED);
      $stmt = $query;
    }

    try {
      if (is_string($query)) {
        $stmt->execute($args, $options);
      }
      elseif ($query instanceof StatementInterface) {
        $stmt->execute(NULL, $options);
      }
      elseif ($query instanceof \PDOStatement) {
        $stmt->execute();
      }

      // Depending on the type of query we may need to return a different value.
      // See DatabaseConnection::defaultOptions() for a description of each
      // value.
      switch ($options['return'] ?? Database::RETURN_STATEMENT) {
        case Database::RETURN_STATEMENT:
          return $stmt;

        case Database::RETURN_AFFECTED:
          $stmt->allowRowCount = TRUE;
          return $stmt->rowCount();

        case Database::RETURN_INSERT_ID:
          $sequence_name = isset($options['sequence_name']) ? $options['sequence_name'] : NULL;
          return $this->connection->lastInsertId($sequence_name);

        case Database::RETURN_NULL:
          return NULL;

        default:
          throw new \PDOException('Invalid return directive: ' . $options['return']);

      }
    }
    catch (\Exception $e) {
      // Most database drivers will return NULL here, but some of them
      // (e.g. the SQLite driver) may need to re-run the query, so the return
      // value will be the same as for static::query().
      if (is_string($query)) {
        $this->exceptionHandler()->handleExecutionException($e, $stmt, $args, $options);
        return NULL;
      }
      else {
        return $this->handleQueryException($e, $query, $args, $options);
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * Using SQL Server query syntax.
   */
  public function rollBack($savepoint_name = 'drupal_transaction') {
    if (!$this->inTransaction()) {
      throw new TransactionNoActiveException();
    }
    // A previous rollback to an earlier savepoint may mean that the savepoint
    // in question has already been accidentally committed.
    if (!isset($this->transactionLayers[$savepoint_name])) {
      throw new TransactionNoActiveException();
    }
    // We need to find the point we're rolling back to, all other savepoints
    // before are no longer needed. If we rolled back other active savepoints,
    // we need to throw an exception.
    $rolled_back_other_active_savepoints = FALSE;
    while ($savepoint = array_pop($this->transactionLayers)) {
      if ($savepoint == $savepoint_name) {
        // If it is the last the transaction in the stack, then it is not a
        // savepoint, it is the transaction itself so we will need to roll back
        // the transaction rather than a savepoint.
        if (empty($this->transactionLayers)) {
          break;
        }
        try {
          $this->queryDirect('ROLLBACK TRANSACTION ' . $savepoint_name);
        }
        catch (DatabaseExceptionWrapper $e) {
          // If an exception occured within a savepoint, the savepoint is
          // automatically destroyed in ODBC. We want to know the real cause
          // for the exception occuring, so catch and keep any "savepoint not
          // found" exception, to allow any try / catch block further up the
          // stack to throw their exception.
          if (strpos($e->getMessage(), 'No transaction or savepoint of that name was found.') === FALSE) {
            // Here throw $e; Here.
            throw $e;
          }

        }
        $this->popCommittableTransactions();
        if ($rolled_back_other_active_savepoints) {
          throw new TransactionOutOfOrderException();
        }
        return;
      }
      else {
        $rolled_back_other_active_savepoints = TRUE;
      }
    }
    // Notify the callbacks about the rollback.
    $callbacks = $this->rootTransactionEndCallbacks;
    $this->rootTransactionEndCallbacks = [];
    foreach ($callbacks as $callback) {
      call_user_func($callback, FALSE);
    }
    $this->connection->rollBack();
    if ($rolled_back_other_active_savepoints) {
      throw new TransactionOutOfOrderException();
    }
  }

  /**
   * {@inheritdoc}
   *
   * Adding logic for temporary tables.
   */
  protected function setPrefix($prefix) {
    parent::setPrefix($prefix);
    // Add this to the front of the array so it is done before
    // the default action.
    array_unshift($this->prefixSearch, '{db_temporary_');

    // If there is a period in the prefix, apply the temp prefix to the final
    // piece.
    $default_parts = explode('.', $this->tablePrefix());
    $table_part = array_pop($default_parts);
    $default_parts[] = '[' . $this->tempTablePrefix . $table_part;
    $full_prefix = implode('.', $default_parts);
    array_unshift($this->prefixReplace, $full_prefix . 'db_temporary_');
  }

  /**
   * {@inheritdoc}
   *
   * Adding logic for temporary tables.
   */
  public function tablePrefix($table = 'default') {
    if ($this->isTemporaryTable($table)) {
      // If there is a period in the prefix, apply the temp prefix to the final
      // piece.
      $default_parts = explode('.', parent::tablePrefix());
      $table_part = array_pop($default_parts);
      $default_parts[] = $this->tempTablePrefix . $table_part;
      return implode('.', $default_parts);
    }
    return parent::tablePrefix();
  }

  /**
   * The temporary table prefix.
   *
   * @return string
   *   The temporary table prefix.
   */
  public function getTempTablePrefix() {
    return $this->tempTablePrefix;
  }

  /**
   * Is this table a temporary table?
   *
   * @var string $table
   *   The table name.
   *
   * @return bool
   *   True is the table is a temporary table.
   */
  public function isTemporaryTable($table) {
    return stripos($table, 'db_temporary_') !== FALSE;
  }

  /**
   * Like query but with no query preprocessing.
   *
   * The caller is sure that the query is MS SQL compatible! Used internally
   * from the schema class, but could be called from anywhere.
   *
   * @param string $query
   *   Query.
   * @param array $args
   *   Query arguments.
   * @param mixed $options
   *   Query options.
   *
   * @throws \PDOException
   *
   * @return mixed
   *   Query result.
   */
  public function queryDirect($query, array $args = [], $options = []) {
    // Use default values if not already set.
    $options += $this->defaultOptions();

    // Core tests run faster without emulating.
    $direct_query_options = [
      'direct_query' => TRUE,
      'bypass_preprocess' => TRUE,
      'emulate_prepares' => FALSE,
    ];
    $stmt = $this->prepareStatement($query, $direct_query_options + $options);

    try {
      $stmt->execute($args, $options);

      // Depending on the type of query we may need to return a different value.
      // See DatabaseConnection::defaultOptions() for a description of each
      // value.
      switch ($options['return'] ?? Database::RETURN_STATEMENT) {
        case Database::RETURN_STATEMENT:
          return $stmt;

        case Database::RETURN_AFFECTED:
          $stmt->allowRowCount = TRUE;
          return $stmt->rowCount();

        case Database::RETURN_INSERT_ID:
          return $this->connection->lastInsertId();

        case Database::RETURN_NULL:
          return NULL;

        default:
          throw new \PDOException('Invalid return directive: ' . $options['return']);
      }
    }
    catch (\Exception $e) {
      // Most database drivers will return NULL here, but some of them
      // (e.g. the SQLite driver) may need to re-run the query, so the return
      // value will be the same as for static::query().
      $this->exceptionHandler()->handleExecutionException($e, $stmt, $args, $options);
    }
  }

  /**
   * Massage a query to make it compliant with SQL Server.
   *
   * @param mixed $query
   *   Query string.
   *
   * @return string
   *   Query string in MS SQL format.
   */
  public function preprocessQuery($query) {
    // Last chance to modify some SQL Server-specific syntax.
    $replacements = [];

    // Add prefixes to Drupal-specific functions.
    /** @var \Drupal\sqlsrv\Driver\Database\sqlsrv\Schema $schema */
    $schema = $this->schema();
    $defaultSchema = $schema->GetDefaultSchema();
    foreach ($schema->DrupalSpecificFunctions() as $function) {
      $replacements['/\b(?<![:.])(' . preg_quote($function) . ')\(/i'] = "{$defaultSchema}.$1(";
    }

    // Rename some functions.
    $funcs = [
      'LENGTH' => 'LEN',
      'POW' => 'POWER',
    ];

    foreach ($funcs as $function => $replacement) {
      $replacements['/\b(?<![:.])(' . preg_quote($function) . ')\(/i'] = $replacement . '(';
    }

    // Replace the ANSI concatenation operator with SQL Server poor one.
    $replacements['/\|\|/'] = '+';

    // Now do all the replacements at once.
    $query = preg_replace(array_keys($replacements), array_values($replacements), $query);

    // Substitute the LEAST operator with something that works in SQL Server.
    while (($pos1 = strpos($query, 'LEAST(')) !== FALSE) {
      $name_length = 5;
      $pos2 = Utils::findParenMatch($query, $pos1 + $name_length);
      $argument_list = substr($query, $pos1 + $name_length + 1, $pos2 - $name_length - 1 - $pos1);
      $arguments = explode(', ', $argument_list);
      $new_arguments = implode('), (', $arguments);
      $replace = '(SELECT MIN(i) FROM (VALUES (' . $new_arguments . ')) AS T(i)) sqlsrv_least';
      $query = substr($query, 0, $pos1) . $replace . substr($query, $pos2 + 1);
    }
    return $query;
  }

  /**
   * A map of condition operators to sqlsrv operators.
   *
   * SQL Server doesn't need special escaping for the \ character in a string
   * literal, because it uses '' to escape the single quote, not \'.
   *
   * @var array
   */
  protected static $sqlsrvConditionOperatorMap = [
    // These can be changed to 'LIKE' => ['postfix' => " ESCAPE '\\'"],
    // if https://bugs.php.net/bug.php?id=79276 is fixed.
    'LIKE' => [],
    'NOT LIKE' => [],
    'LIKE BINARY' => ['operator' => 'LIKE'],
  ];

  public function hasJson(): bool {
    try {
      return (bool) $this->query('SELECT ISJSON(\'[1]\')');
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

}
