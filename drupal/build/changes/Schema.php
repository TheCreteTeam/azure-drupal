<?php

namespace Drupal\sqlsrv\Driver\Database\sqlsrv;

use Drupal\Core\Database\Schema as DatabaseSchema;
use Drupal\Core\Database\SchemaObjectDoesNotExistException;
use Drupal\Core\Database\SchemaObjectExistsException;

/**
 * @addtogroup schemaapi
 * @{
 */
class Schema extends DatabaseSchema {

  /**
   * The database connection.
   *
   * @var \Drupal\sqlsrv\Driver\Database\sqlsrv\Connection
   */
  protected $connection;

  /**
   * Default schema for SQL Server databases.
   *
   * @var string
   */
  protected $defaultSchema;

  /**
   * Maximum length of a comment in SQL Server.
   *
   * @var int
   */
  const COMMENT_MAX_BYTES = 7500;

  /**
   * Maximum length of a Primary Key.
   *
   * @var int
   */
  const PRIMARY_KEY_BYTES = 900;

  /**
   * Maximum length of a clustered index.
   *
   * @var int
   */
  const CLUSTERED_INDEX_BYTES = 900;

  /**
   * Maximum length of a non-clustered index.
   *
   * @var int
   */
  const NONCLUSTERED_INDEX_BYTES = 1700;

  /**
   * Maximum index length with XML field.
   *
   * @var int
   */
  const XML_INDEX_BYTES = 128;

  // Name for the technical column used for computed key sor technical primary
  // key.
  // IMPORTANT: They both start with "__" because the statement class will
  // remove those columns from the final result set.

  /**
   * Computed primary key name.
   *
   * @var string
   */
  const COMPUTED_PK_COLUMN_NAME = '__pkc';

  /**
   * Computed primary key index.
   *
   * @var string
   */
  const COMPUTED_PK_COLUMN_INDEX = '__ix_pkc';

  /**
   * Technical primary key name.
   *
   * @var string
   */
  const TECHNICAL_PK_COLUMN_NAME = '__pk';

  /**
   * Version information for the SQL Server engine.
   *
   * @var array
   */
  protected $engineVersion;

  /**
   * Should we cache table schema?
   *
   * @var bool
   */
  private $cacheSchema;

  /**
   * Table schema.
   *
   * @var mixed
   */
  private $columnInformation = [];

  /**
   * {@inheritdoc}
   */
  public function getFieldTypeMap() {
    // Put :normal last so it gets preserved by array_flip.  This makes
    // it much easier for modules (such as schema.module) to map
    // database types back into schema types.
    $utf8_string_types = [
      'varchar:normal' => 'varchar',
      'char:normal' => 'char',

      'text:tiny' => 'varchar(255)',
      'text:small' => 'varchar(255)',
      'text:medium' => 'varchar(max)',
      'text:big' => 'varchar(max)',
      'text:normal' => 'varchar(max)',
    ];

    $ucs2_string_types = [
      'varchar:normal' => 'nvarchar',
      'char:normal' => 'nchar',

      'text:tiny' => 'nvarchar(255)',
      'text:small' => 'nvarchar(255)',
      'text:medium' => 'nvarchar(max)',
      'text:big' => 'nvarchar(max)',
      'text:normal' => 'nvarchar(max)',
    ];

    $standard_types = [
      'varchar_ascii:normal' => 'varchar(255)',

      'serial:tiny'     => 'smallint',
      'serial:small'    => 'smallint',
      'serial:medium'   => 'int',
      'serial:big'      => 'bigint',
      'serial:normal'   => 'int',

      'int:tiny' => 'smallint',
      'int:small' => 'smallint',
      'int:medium' => 'int',
      'int:big' => 'bigint',
      'int:normal' => 'int',

      'float:tiny' => 'real',
      'float:small' => 'real',
      'float:medium' => 'real',
      'float:big' => 'float(53)',
      'float:normal' => 'real',

      'numeric:normal' => 'numeric',

      'blob:big' => 'varbinary(max)',
      'blob:normal' => 'varbinary(max)',

      'date:normal'     => 'date',
      'datetime:normal' => 'datetime2(0)',
      'time:normal'     => 'time(0)',
    ];
    $standard_types += $this->isUtf8() ? $utf8_string_types : $ucs2_string_types;
    return $standard_types;
  }

  /**
   * {@inheritdoc}
   */
  public function renameTable($table, $new_name) {
    if (!$this->tableExists($table)) {
      throw new SchemaObjectDoesNotExistException("Cannot rename '$table' to '$new_name': table '$table' doesn't exist.");
    }
    if ($this->tableExists($new_name)) {
      throw new SchemaObjectExistsException("Cannot rename '$table' to '$new_name': table '$new_name' already exists.");
    }

    $old_table_info = $this->getPrefixInfo($table);
    $new_table_info = $this->getPrefixInfo($new_name);

    // We don't support renaming tables across schemas (yet).
    if ($old_table_info['schema'] != $new_table_info['schema']) {
      throw new \PDOException('Cannot rename a table across schema.');
    }

    $this->connection->queryDirect('EXEC sp_rename :old, :new', [
      ':old' => $old_table_info['schema'] . '.' . $old_table_info['table'],
      ':new' => $new_table_info['table'],
    ]);

    // Constraint names are global in SQL Server, so we need to rename them
    // when renaming the table. For some strange reason, indexes are local to
    // a table.
    $objects = $this->connection->queryDirect('SELECT name FROM sys.objects WHERE parent_object_id = OBJECT_ID(:table)', [':table' => $new_table_info['schema'] . '.' . $new_table_info['table']]);
    foreach ($objects as $object) {
      if (preg_match('/^' . preg_quote($old_table_info['table']) . '_(.*)$/', $object->name, $matches)) {
        $this->connection->queryDirect('EXEC sp_rename :old, :new, :type', [
          ':old' => $old_table_info['schema'] . '.' . $object->name,
          ':new' => $new_table_info['table'] . '_' . $matches[1],
          ':type' => 'OBJECT',
        ]);
      }
    }
    $this->resetColumnInformation($table);
  }

  /**
   * {@inheritdoc}
   */
  public function dropTable($table) {
    if (!$this->tableExists($table)) {
      return FALSE;
    }
    $this->connection->queryDirect('DROP TABLE {' . $table . '}');
    $this->resetColumnInformation($table);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldExists($table, $field) {
    $prefixInfo = $this->getPrefixInfo($table, TRUE);
    return $this->connection
      ->queryDirect('SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = :table AND column_name = :name', [
        ':table' => $prefixInfo['table'],
        ':name' => $field,
      ])
      ->fetchField() !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function addField($table, $field, $spec, $keys_new = []) {
    if (!$this->tableExists($table)) {
      throw new SchemaObjectDoesNotExistException("Cannot add field '$table.$field': table doesn't exist.");
    }
    if ($this->fieldExists($table, $field)) {
      throw new SchemaObjectExistsException("Cannot add field '$table.$field': field already exists.");
    }

    // Fields that are part of a PRIMARY KEY must be added as NOT NULL.
    $is_primary_key = isset($keys_new['primary key']) && in_array($field, $keys_new['primary key'], TRUE);
    if ($is_primary_key) {
      $this->ensureNotNullPrimaryKey($keys_new['primary key'], [$field => $spec]);
    }

    $transaction = $this->connection->startTransaction();

    // Prepare the specifications.
    $spec = $this->processField($spec);

    // Use already prefixed table name.
    $prefixInfo = $this->getPrefixInfo($table, TRUE);
    $table_prefixed = $prefixInfo['table'];

    if ($this->findPrimaryKeyColumns($table) !== [] && isset($keys_new['primary key']) && in_array($field, $keys_new['primary key'])) {
      $this->cleanUpPrimaryKey($table);
    }
    // If the field is declared NOT NULL, we have to first create it NULL insert
    // the initial data (or populate default values) and then switch to NOT
    // NULL.
    $fixnull = FALSE;
    if (!empty($spec['not null'])) {
      $fixnull = TRUE;
      $spec['not null'] = FALSE;
    }

    // Create the field.
    // Because the default values of fields can contain string literals
    // with braces, we CANNOT allow the driver to prefix tables because the
    // algorithm to do so is a crappy str_replace.
    $query = "ALTER TABLE {{$table}} ADD ";
    $query .= $this->createFieldSql($table, $field, $spec);
    $this->connection->queryDirect($query, []);
    $this->resetColumnInformation($table);
    // Load the initial data.
    if (isset($spec['initial_from_field'])) {
      if (isset($spec['initial'])) {
        $expression = 'COALESCE(' . $spec['initial_from_field'] . ', :default_initial_value)';
        $arguments = [':default_initial_value' => $spec['initial']];
      }
      else {
        $expression = $spec['initial_from_field'];
        $arguments = [];
      }
      $this->connection->update($table)
        ->expression($field, $expression, $arguments)
        ->execute();
    }
    elseif (isset($spec['initial'])) {
      $this->connection->update($table)
        ->fields([$field => $spec['initial']])
        ->execute();
    }

    // Switch to NOT NULL now.
    if ($fixnull === TRUE) {
      // There is no warranty that the old data did not have NULL values, we
      // need to populate nulls with the default value because this won't be
      // done by MSSQL by default.
      if (isset($spec['default'])) {
        $default_expression = $this->defaultValueExpression($spec['sqlsrv_type'], $spec['default']);
        $sql = "UPDATE {{$table}} SET {$field}={$default_expression} WHERE {$field} IS NULL";
        $this->connection->queryDirect($sql);
      }

      // Now it's time to make this non-nullable.
      $spec['not null'] = TRUE;
      $field_sql = $this->createFieldSql($table, $field, $spec, TRUE);
      $this->connection->queryDirect("ALTER TABLE {{$table}} ALTER COLUMN {$field_sql}");
      $this->resetColumnInformation($table);
    }

    $this->recreateTableKeys($table, $keys_new);

    if (isset($spec['description'])) {
      $this->connection->queryDirect($this->createCommentSql($spec['description'], $table, $field));
    }
  }

  /**
   * {@inheritdoc}
   *
   * Should this be in a Transaction?
   */
  public function dropField($table, $field) {
    if (!$this->fieldExists($table, $field)) {
      return FALSE;
    }
    $primary_key_fields = $this->findPrimaryKeyColumns($table);

    if (in_array($field, $primary_key_fields)) {
      // Let's drop the PK.
      $this->cleanUpPrimaryKey($table);
      $this->createTechnicalPrimaryColumn($table);
    }

    // Drop the related objects.
    $this->dropFieldRelatedObjects($table, $field);

    // Drop field comments.
    if ($this->getComment($table, $field) !== FALSE) {
      $this->connection->queryDirect($this->deleteCommentSql($table, $field));
    }

    $this->connection->query('ALTER TABLE {' . $table . '} DROP COLUMN ' . $field);
    $this->resetColumnInformation($table);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function indexExists($table, $name) {
    $prefixInfo = $this->getPrefixInfo($table, TRUE);
    return (bool) $this->connection->query('SELECT 1 FROM sys.indexes WHERE object_id = OBJECT_ID(:table) AND name = :name', [
      ':table' => $prefixInfo['table'],
      ':name' => $name . '_idx',
    ])->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function addPrimaryKey($table, $fields) {
    if (!$this->tableExists($table)) {
      throw new SchemaObjectDoesNotExistException("Cannot add primary key to table '$table': table doesn't exist.");
    }

    if ($primary_key_name = $this->primaryKeyName($table)) {
      if ($this->isTechnicalPrimaryKey($primary_key_name)) {
        // Destroy the existing technical primary key.
        $this->connection->queryDirect('ALTER TABLE {' . $table . '} DROP CONSTRAINT [' . $primary_key_name . ']');
        $this->resetColumnInformation($table);
        $this->cleanUpTechnicalPrimaryColumn($table);
      }
      else {
        throw new SchemaObjectExistsException("Cannot add primary key to table '$table': primary key already exists.");
      }
    }
    $this->createPrimaryKey($table, $fields);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function dropPrimaryKey($table) {
    if (!$this->primaryKeyName($table)) {
      return FALSE;
    }
    $this->cleanUpPrimaryKey($table);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function findPrimaryKeyColumns($table) {
    if (!$this->tableExists($table)) {
      return FALSE;
    }
    // Use already prefixed table name.
    $prefixInfo = $this->getPrefixInfo($table, TRUE);
    $query = "SELECT column_name FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS TC "
      . "INNER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS KU "
      . "ON TC.CONSTRAINT_TYPE = 'PRIMARY KEY' AND "
      . "TC.CONSTRAINT_NAME = KU.CONSTRAINT_NAME AND "
      . "KU.table_name=:table AND column_name != '__pk' AND column_name != '__pkc' "
      . "ORDER BY KU.ORDINAL_POSITION";
    $result = $this->connection->query($query, [':table' => $prefixInfo['table']])->fetchAllAssoc('column_name');
    return array_keys($result);
  }

  /**
   * {@inheritdoc}
   */
  public function addUniqueKey($table, $name, $fields) {
    if (!$this->tableExists($table)) {
      throw new SchemaObjectDoesNotExistException("Cannot add unique key '$name' to table '$table': table doesn't exist.");
    }
    if ($this->uniqueKeyExists($table, $name)) {
      throw new SchemaObjectExistsException("Cannot add unique key '$name' to table '$table': unique key already exists.");
    }

    $this->createTechnicalPrimaryColumn($table);

    // Then, build a expression based on the columns.
    $column_expression = [];
    foreach ($fields as $field) {
      if (is_array($field)) {
        $column_expression[] = 'SUBSTRING(CAST(' . $field[0] . ' AS varbinary(max)),1,' . $field[1] . ')';
      }
      else {
        $column_expression[] = 'CAST(' . $field . ' AS varbinary(max))';
      }
    }
    $column_expression = implode(' + ', $column_expression);

    // Build a computed column based on the expression that replaces NULL
    // values with the globally unique identifier generated previously.
    // This is (very) unlikely to result in a collision with any actual value
    // in the columns of the unique key.
    $this->connection->query("ALTER TABLE {{$table}} ADD __unique_{$name} AS CAST(HashBytes('MD4', COALESCE({$column_expression}, CAST(" . self::TECHNICAL_PK_COLUMN_NAME . " AS varbinary(max)))) AS varbinary(16))");
    $this->connection->query("CREATE UNIQUE INDEX {$name}_unique ON {{$table}} (__unique_{$name})");
    $this->resetColumnInformation($table);
  }

  /**
   * {@inheritdoc}
   */
  public function dropUniqueKey($table, $name) {
    if (!$this->uniqueKeyExists($table, $name)) {
      return FALSE;
    }

    $this->connection->query("DROP INDEX {$name}_unique ON {{$table}}");
    $this->connection->query("ALTER TABLE {{$table}} DROP COLUMN __unique_{$name}");
    $this->resetColumnInformation($table);
    // Try to clean-up the technical primary key if possible.
    $this->cleanUpTechnicalPrimaryColumn($table);

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function addIndex($table, $name, $fields, array $spec = []) {
    if (!$this->tableExists($table)) {
      throw new SchemaObjectDoesNotExistException("Cannot add index '$name' to table '$table': table doesn't exist.");
    }
    if ($this->indexExists($table, $name)) {
      throw new SchemaObjectExistsException("Cannot add index '$name' to table '$table': index already exists.");
    }

    $info = $this->queryColumnInformation($table);
    $index_size = 0;
    $indexable_type = TRUE;
    foreach ($fields as $field) {
      $field = is_array($field) ? $field[0] : $field;
      $size = $info['columns'][$field]['max_length'];
      $index_size += $size;
      if (isset($info['columns'][$field])) {
        $field_def = $info['columns'][$field];
        $non_indexable_types = ['varchar', 'nvarchar', 'varbinary'];
        // A max_length of -1 means the variable length field is 'max'.
        // These are always unable to be indexed.
        if ($field_def['max_length'] == -1 && in_array($field_def['type'], $non_indexable_types)) {
          $indexable_type = FALSE;
          break;
        }
      }
    }
    $sql = $this->createIndexSql($table, $name, $fields);
    if ($index_size <= self::NONCLUSTERED_INDEX_BYTES && $indexable_type) {
      // If we used the spec instead of the max_length from queryColumnInfo,
      // we would have to divide by three if UTF8 collation.
      $this->connection->query($sql);
      $this->resetColumnInformation($table);
    }
    // If the field is too large, do not create an index.
  }

  /**
   * {@inheritdoc}
   */
  public function dropIndex($table, $name) {
    if (!$this->indexExists($table, $name)) {
      return FALSE;
    }

    $this->connection->query('DROP INDEX ' . $name . '_idx ON {' . $table . '}');
    $this->resetColumnInformation($table);

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function introspectIndexSchema($table) {
    if (!$this->tableExists($table)) {
      throw new SchemaObjectDoesNotExistException("The table $table doesn't exist.");
    }
    $index_schema = [
      'primary key' => $this->findPrimaryKeyColumns($table),
      'unique keys' => [],
      'indexes' => [],
    ];
    $column_information = $this->queryColumnInformation($table);
    foreach ($column_information['indexes'] as $key => $values) {
      if ($values['is_primary_key'] !== 1 && $values['data_space_id'] == 1 && $values['is_unique'] == 0) {
        foreach ($values['columns'] as $num => $stats) {
          $index_schema['indexes'][substr($key, 0, -4)][] = $stats['name'];
        }
      }
    }
    foreach ($column_information['columns'] as $name => $spec) {
      if (substr($name, 0, 9) == '__unique_' && $column_information['indexes'][substr($name, 9) . '_unique']['is_unique'] == 1) {
        $definition = $spec['definition'];
        $matches = [];
        preg_match_all("/CONVERT\(\[varbinary\]\(max\),\[([a-zA-Z0-9_]*)\]/", $definition, $matches);
        foreach ($matches[1] as $match) {
          if ($match != '__pk') {
            $index_schema['unique keys'][substr($name, 9)][] = $match;
          }
        }
      }
    }
    return $index_schema;
  }

  /**
   * {@inheritdoc}
   */
  public function changeField($table, $field, $field_new, $spec, $keys_new = []) {
    if (!$this->fieldExists($table, $field)) {
      throw new SchemaObjectDoesNotExistException("Cannot change the definition of field '$table.$field': field doesn't exist.");
    }
    if (($field != $field_new) && $this->fieldExists($table, $field_new)) {
      throw new SchemaObjectExistsException("Cannot rename field '$table.$field' to '$field_new': target field already exists.");
    }
    if (isset($keys_new['primary key']) && in_array($field_new, $keys_new['primary key'], TRUE)) {
      $this->ensureNotNullPrimaryKey($keys_new['primary key'], [$field_new => $spec]);
    }
    // Check if we need to drop field comments.
    $drop_field_comment = FALSE;
    if ($this->getComment($table, $field) !== FALSE) {
      $drop_field_comment = TRUE;
    }

    // SQL Server supports transactional DDL, so we can just start a transaction
    // here and pray for the best.
    $transaction = $this->connection->startTransaction();

    // Prepare the specifications.
    $spec = $this->processField($spec);

    // If the field type is 'serial' and the table has data, we have to clone
    // the table, add the new field, and copy the data from the old table.
    // We only need to know if there are rows for 'serial' field types.
    // Otherwise $serial_field_has_rows will always be false.
    $serial_field_has_rows = FALSE;
    if ($spec['type'] == 'serial') {
      $row_count = (int) $this->connection->select($table)->countQuery()->execute()->fetchField();
      $serial_field_has_rows = $row_count > 0;
    }

    $prefixInfo = $this->getPrefixInfo($table, TRUE);

    // No need to do this if the field type is 'serial' and there is existing
    // data because we will need to recreate the table and copy data over.
    if (!$serial_field_has_rows) {
      /*
       * IMPORTANT NOTE: To maintain database portability, you have to
       * explicitly recreate all indices and primary keys that are using the
       * changed field. That means that you have to drop all affected keys and
       * indexes with db_drop_{primary_key,unique_key,index}() before calling
       * db_change_field().
       *
       * @see https://api.drupal.org/api/drupal/includes!database!database.inc/function/db_change_field/7
       *
       * What we are going to do in the SQL Server Driver is a best-effort try
       * to preserve original keys if they do not conflict with the keys_new
       * parameter, and if the callee has done it's job (droping
       * constraints/keys) then they will of course not be recreated.
       *
       * Introspect the schema and save the current primary key if the column
       * we are modifying is part of it. Make sure the schema is FRESH.
       */
      $primary_key_fields = $this->findPrimaryKeyColumns($table);

      if (in_array($field, $primary_key_fields)) {
        // Let's drop the PK.
        $this->cleanUpPrimaryKey($table);
      }

      // If there is a generated unique key for this field, we will need to
      // add it back in when we are done.
      $unique_key = $this->uniqueKeyExists($table, $field);

      // Drop the related objects.
      $this->dropFieldRelatedObjects($table, $field);

      if ($drop_field_comment) {
        $this->connection->queryDirect($this->deleteCommentSql($table, $field));
      }
      // Start by renaming the current column.
      $this->connection->queryDirect('EXEC sp_rename :old, :new, :type', [
        ':old'  => $prefixInfo['table'] . '.' . $field,
        ':new'  => $field . '_old',
        ':type' => 'COLUMN',
      ]);
      $this->resetColumnInformation($table);

      // If the new column does not allow nulls, we need to
      // create it first as nullable, then either migrate
      // data from previous column or populate default values.
      $fixnull = FALSE;
      if (!empty($spec['not null'])) {
        $fixnull = TRUE;
        $spec['not null'] = FALSE;
      }

      // Create a new field.
      $this->addField($table, $field_new, $spec);

      // Don't need to do this if there is no data
      // Cannot do this it column is serial.
      if ($spec['type'] != 'serial') {
        $new_data_type = $this->createDataType($table, $field_new, $spec);
        // Migrate the data over.
        // Explicitly cast the old value to the new value to avoid conversion
        // errors.
        $sql = "UPDATE {{$table}} SET {$field_new}=CAST({$field}_old AS {$new_data_type})";
        $this->connection->queryDirect($sql);
        $this->resetColumnInformation($table);
      }

      // Switch to NOT NULL now.
      if ($fixnull === TRUE) {
        // There is no warranty that the old data did not have NULL values, we
        // need to populate nulls with the default value because this won't be
        // done by MSSQL by default.
        if (!empty($spec['default'])) {
          $default_expression = $this->defaultValueExpression($spec['sqlsrv_type'], $spec['default']);
          $sql = "UPDATE {{$table}} SET {$field_new} = {$default_expression} WHERE {$field_new} IS NULL";
          $this->connection->queryDirect($sql);
          $this->resetColumnInformation($table);
        }
        // Now it's time to make this non-nullable.
        $spec['not null'] = TRUE;
        $field_sql = $this->createFieldSql($table, $field_new, $spec, TRUE);
        $sql = "ALTER TABLE {{$table}} ALTER COLUMN {$field_sql}";
        $this->connection->queryDirect($sql);
        $this->resetColumnInformation($table);
      }
      // Recreate the primary key if no new primary key has been sent along with
      // the change field.
      if (in_array($field, $primary_key_fields) && (!isset($keys_new['primary key']) || empty($keys_new['primary key']))) {
        // The new primary key needs to have the new column name, and be in the
        // same order.
        if ($field !== $field_new) {
          $primary_key_fields[array_search($field, $primary_key_fields)] = $field_new;
        }
        $keys_new['primary key'] = $primary_key_fields;
      }

      // Recreate the unique constraint if it existed.
      if ($unique_key && (!isset($keys_new['unique keys']) || !in_array($field_new, $keys_new['unique keys']))) {
        $keys_new['unique keys'][$field] = [$field_new];
      }

      // Drop the old field.
      $this->dropField($table, $field . '_old');

      // Add the new keys.
      $this->recreateTableKeys($table, $keys_new);
    }
    else {
      // The field type is 'serial' and the table has data in it. We have to
      // clone the table, delete the old field from the new table, add the
      // new field to the new table, and copy the data over to the new table.
      // Then we have to drop the old table and rename the new table to the old
      // table's name.
      $table_old = $table . '_old';
      $table_new = $table . '_new';

      // Rename table to old first to acquire the schema lock early.
      $this->renameTable($table, $table_old);

      // Clone Table without any data.
      // No fields generated by this module will be added to the table.
      // Only the primary key (PK) will get copied over.
      // We'll add other indexes to the new table after the data is copied over.
      $this->createTableAs($table_new, $table_old);

      $prefixInfoOld = $this->getPrefixInfo($table_old, TRUE);
      $prefixInfoNew = $this->getPrefixInfo($table_new, TRUE);

      // Drop the PK in {$table_new} if {$field_new} is part of it.
      $primary_key_fields = $this->findPrimaryKeyColumns($table_new);

      if (in_array($field, $primary_key_fields)) {
        // Let's drop the PK.
        $this->cleanUpPrimaryKey($table_new);
      }

      // Drop old field from {$table_new}.
      $this->dropField($table_new, $field);

      // Add new field to {$new_table}.
      $this->addField($table_new, $field_new, $spec);

      // Get CSV list of non-generated fields in the source table.
      $column_info = $this->queryColumnInformation($table_old);
      $columns_clean_string = implode(', ', array_keys($column_info['columns_clean']));

      // Copy rows from {$table} to {$table_new}.
      $query['ident_insert_on'] = "SET IDENTITY_INSERT {$prefixInfoNew['table']} ON";
      $query['copy_rows'] = "INSERT INTO {$prefixInfoNew['table']} ({$columns_clean_string}) SELECT {$columns_clean_string} FROM {$prefixInfoOld['table']}";
      $query['ident_insert_off'] = "SET IDENTITY_INSERT {$prefixInfoNew['table']} OFF";

      $this->connection->queryDirect($query['ident_insert_on']);
      $this->connection->queryDirect($query['copy_rows']);
      $this->connection->queryDirect($query['ident_insert_off']);

      // Get all the indexes in the source table, so we can update {$keys_new}
      // with all the indexes and then add them to the new table.
      $indexes = $this->introspectIndexSchema($table_old);

      // Merge {$keys_new} into {$indexes}. Remove old field from all the
      // indexes if {$field_new} is not the same as {$field}.
      if ($field !== $field_new) {
        if (in_array($field, $indexes['primary key'])) {
          $indexes['primary key'] = [];
        }
        foreach ($indexes['unique keys'] as $key => $unique_key) {
          if (in_array($field, $unique_key)) {
            unset($indexes['unique keys'][$key]);
          }
        }
        foreach ($indexes['indexes'] as $key => $index) {
          if (in_array($field, $index)) {
            unset($indexes['indexes'][$key]);
          }
        }
      }

      // Add {$keys_new} to {$indexes}.
      foreach ($keys_new as $type => $key) {
        if ($type == 'primary key') {
          $indexes['primary key'] = $key;
        }
        if ($type == 'unique keys') {
          foreach ($indexes['unique keys'] as $uk_type => $uk_key) {
            $indexes['unique keys'][$uk_type] = $uk_key;
          }
        }
        if ($type == 'indexes') {
          foreach ($indexes['indexes'] as $idx_type => $idx_key) {
            $indexes['indexes'][$idx_type] = $idx_key;
          }
        }
      }

      // Recreate the primary key if no new primary key has been sent along with
      // the change field.
      if (in_array($field, $primary_key_fields) && (!isset($keys_new['primary key']) || empty($keys_new['primary key']))) {
        // The new primary key needs to have the new column name, and be in the
        // same order.
        if ($field !== $field_new) {
          $primary_key_fields[array_search($field, $primary_key_fields)] = $field_new;
        }
        $indexes['primary key'] = $primary_key_fields;
      }

      // If there is a generated unique key for this field, we will need to
      // add it back in when we are done.
      $unique_key = $this->uniqueKeyExists($table_old, $field);

      // Recreate the unique constraint if it existed.
      if ($unique_key && (!isset($keys_new['unique keys']) || !in_array($field_new, $keys_new['unique keys']))) {
        $indexes['unique keys'][$field] = [$field_new];
      }

      // Drop the old table and rename the new table now.
      $this->dropTable($table_old);
      $this->renameTable($table_new, $table);

      // Add the new keys.
      $this->recreateTableKeys($table, $indexes);
    }

    // Commit the transaction by unsetting the $transaction variable.
    // This should happen anyway once the variable goes out of scope but
    // it's super non-obvious.
    unset($transaction);
  }

  /**
   * {@inheritdoc}
   *
   * Adding abilty to pass schema in configuration.
   */
  public function __construct($connection) {
    parent::__construct($connection);
    $options = $connection->getConnectionOptions();
    if (isset($options['schema'])) {
      $this->defaultSchema = $options['schema'];
    }
    $this->cacheSchema = $options['cache_schema'] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * The $this->defaultSchema seems to be not set in drush command context,
   * we use $this->getDefaultSchema() instead to make the drush command work.
   */
  protected function getPrefixInfo($table = 'default', $add_prefix = TRUE) {
    // Call $this->getDefaultSchema() to ensure $this->defaultSchema is set
    // before being referenced in parent::getPrefixInfo.
    $this->getDefaultSchema();
    return parent::getPrefixInfo($table, $add_prefix);
  }

  /**
   * {@inheritdoc}
   *
   * Temporary tables and regular tables cannot be verified in the same way.
   *
   * ED edits as per https://www.drupal.org/project/sqlsrv/issues/3456815
      */

  public function tableExists($table, bool $add_prefix = TRUE) {
    if (empty($table)) {
      return FALSE;
    }
    // Temporary tables and regular tables cannot be verified in the same way.
    $query = NULL;
    $prefixInfo = $this->getPrefixInfo($table, $add_prefix);
    $args = [];
    if ($this->connection->isTemporaryTable($table)) {
      $query = "SELECT 1 FROM tempdb.sys.tables WHERE [object_id] = OBJECT_ID(:table)";
      $args = [':table' => 'tempdb.[' . $this->getDefaultSchema() . '].[' . $prefixInfo['table'] . ']'];
    }
    else {
      $query = "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE [table_schema] = :schema AND [table_name] = :table";
      $args = [
        ':schema' => $prefixInfo['schema'],
        ':table' => $prefixInfo['table'],
      ];
    }

    return (bool) $this->connection->queryDirect($query, $args)->fetchField();
  }

  /**
   * Drupal specific functions.
   *
   * Returns a list of functions that are not available by default on SQL
   * Server, but used in Drupal Core or contributed modules because they are
   * available in other databases such as MySQL.
   *
   * @return array
   *   List of functions.
   */
  public function drupalSpecificFunctions() {
    $functions = [
      'SUBSTRING',
      'SUBSTRING_INDEX',
      'GREATEST',
      'MD5',
      'LPAD',
      'REGEXP',
      'IF',
      'CONNECTION_ID',
    ];
    return $functions;
  }

  /**
   * Return active default Schema.
   */
  public function getDefaultSchema() {
    if (!isset($this->defaultSchema)) {
      $result = $this->connection->queryDirect("SELECT SCHEMA_NAME()")->fetchField();
      $this->defaultSchema = $result;
    }
    return $this->defaultSchema;
  }

  /**
   * Database introspection: fetch technical information about a table.
   *
   * @return array
   *   An array with the following structure:
   *   - blobs[]: Array of column names that should be treated as blobs in this
   *     table.
   *   - identities[]: Array of column names that are identities in this table.
   *   - identity: The name of the identity column
   *   - columns[]: An array of specification details for the columns
   *      - name: Column name.
   *      - max_length: Maximum length.
   *      - precision: Precision.
   *      - collation_name: Collation.
   *      - is_nullable: Is nullable.
   *      - is_ansi_padded: Is ANSI padded.
   *      - is_identity: Is identity.
   *      - type: field type.
   *      - definition: If a computed column, the computation formulae.
   *      - default_value: Default value for the column (if any).
   */
  public function queryColumnInformation($table) {

    if (empty($table) || !$this->tableExists($table)) {
      return [];
    }

    if ($this->cacheSchema && isset($this->columnInformation[$table])) {
      return $this->columnInformation[$table];
    }

    $table_info = $this->getPrefixInfo($table);

    // We could adapt the current code to support temporary table introspection,
    // but for now this is not supported.
    if ($this->connection->isTemporaryTable($table)) {
      throw new \Exception('Temporary table introspection is not supported.');
    }

    $info = [];

    // Don't use {} around information_schema.columns table.
    $sql = "SELECT sysc.name, sysc.max_length, sysc.precision, sysc.collation_name,
      sysc.is_nullable, sysc.is_ansi_padded, sysc.is_identity, sysc.is_computed, TYPE_NAME(sysc.user_type_id) as type,
      syscc.definition, sm.[text] as default_value
      FROM sys.columns AS sysc
      INNER JOIN sys.syscolumns AS sysc2 ON sysc.object_id = sysc2.id and sysc.name = sysc2.name
      LEFT JOIN sys.computed_columns AS syscc ON sysc.object_id = syscc.object_id AND sysc.name = syscc.name
      LEFT JOIN sys.syscomments sm ON sm.id = sysc2.cdefault
      WHERE sysc.object_id = OBJECT_ID(:table)";
    $args = [':table' => $table_info['schema'] . '.' . $table_info['table']];
    $result = $this->connection->queryDirect($sql, $args);

    foreach ($result as $column) {
      if ($column->type == 'varbinary') {
        $info['blobs'][$column->name] = TRUE;
      }
      $info['columns'][$column->name] = (array) $column;
      // Provide a clean list of columns that excludes the ones internally
      // created by the database driver.
      if (!(isset($column->name[1]) && substr($column->name, 0, 2) == "__")) {
        $info['columns_clean'][$column->name] = (array) $column;
      }
    }

    // If we have computed columns, it is important to know what other columns
    // they depend on!
    $column_names = array_keys($info['columns']);
    $column_regex = implode('|', $column_names);
    foreach ($info['columns'] as &$column) {
      $dependencies = [];
      if (!empty($column['definition'])) {
        $matches = [];
        if (preg_match_all("/\[[{$column_regex}\]]*\]/", $column['definition'], $matches) > 0) {
          $dependencies = array_map(function ($m) {
            return trim($m, "[]");
          }, array_shift($matches));
        }
      }
      $column['dependencies'] = array_flip($dependencies);
    }

    // Don't use {} around system tables.
    $result = $this->connection->queryDirect('SELECT name FROM sys.identity_columns WHERE object_id = OBJECT_ID(:table)', [':table' => $table_info['schema'] . '.' . $table_info['table']]);
    unset($column);
    $info['identities'] = [];
    $info['identity'] = NULL;
    foreach ($result as $column) {
      $info['identities'][$column->name] = $column->name;
      $info['identity'] = $column->name;
    }

    // Now introspect information about indexes.
    $result = $this->connection->queryDirect("select tab.[name]  as [table_name],
         idx.[name]  as [index_name],
         allc.[name] as [column_name],
         idx.[type_desc],
         idx.[is_unique],
         idx.[data_space_id],
         idx.[ignore_dup_key],
         idx.[is_primary_key],
         idx.[is_unique_constraint],
         idx.[fill_factor],
         idx.[is_padded],
         idx.[is_disabled],
         idx.[is_hypothetical],
         idx.[allow_row_locks],
         idx.[allow_page_locks],
         idxc.[is_descending_key],
         idxc.[is_included_column],
         idxc.[index_column_id],
         idxc.[key_ordinal]
    FROM sys.[tables] as tab
    INNER join sys.[indexes]       idx  ON tab.[object_id] =  idx.[object_id]
    INNER join sys.[index_columns] idxc ON idx.[object_id] = idxc.[object_id] and  idx.[index_id]  = idxc.[index_id]
    INNER join sys.[all_columns]   allc ON tab.[object_id] = allc.[object_id] and idxc.[column_id] = allc.[column_id]
    WHERE tab.object_id = OBJECT_ID(:table)
    ORDER BY tab.[name], idx.[index_id], idxc.[index_column_id]
                    ",
                  [':table' => $table_info['schema'] . '.' . $table_info['table']]);

    foreach ($result as $index_column) {
      if (!isset($info['indexes'][$index_column->index_name])) {
        $ic = clone $index_column;
        // Only retain index specific details.
        unset($ic->column_name);
        unset($ic->index_column_id);
        unset($ic->is_descending_key);
        unset($ic->table_name);
        unset($ic->key_ordinal);
        $info['indexes'][$index_column->index_name] = (array) $ic;
        if ($index_column->is_primary_key) {
          $info['primary_key_index'] = $ic->index_name;
        }
      }
      $index = &$info['indexes'][$index_column->index_name];
      $index['columns'][$index_column->key_ordinal] = [
        'name' => $index_column->column_name,
        'is_descending_key' => $index_column->is_descending_key,
        'key_ordinal' => $index_column->key_ordinal,
      ];
      // Every columns keeps track of what indexes it is part of.
      $info['columns'][$index_column->column_name]['indexes'][] = $index_column->index_name;
      if (isset($info['columns_clean'][$index_column->column_name])) {
        $info['columns_clean'][$index_column->column_name]['indexes'][] = $index_column->index_name;
      }
    }
    if ($this->cacheSchema) {
      $this->columnInformation[$table] = $info;
    }

    return $info;
  }

  /**
   * Unset cached table schema.
   */
  public function resetColumnInformation($table) {
    unset($this->columnInformation[$table]);
  }

  /**
   * {@inheritdoc}
   */
  public function createTable($name, $table) {

    // Build the table and its unique keys in a transaction, and fail the whole
    // creation in case of an error.
    $transaction = $this->connection->startTransaction();

    parent::createTable($name, $table);

    // If the spec had a primary key, set it now after all fields have been
    // created. We are creating the keys after creating the table so that
    // createPrimaryKey is able to introspect column definition from the
    // database to calculate index sizes. This adds quite quite some overhead,
    // but is only noticeable during table creation.
    if (!empty($table['primary key']) && is_array($table['primary key'])) {
      $this->ensureNotNullPrimaryKey($table['primary key'], $table['fields']);
      $this->createPrimaryKey($name, $table['primary key']);
    }
    // Now all the unique keys.
    if (isset($table['unique keys']) && is_array($table['unique keys'])) {
      foreach ($table['unique keys'] as $key_name => $key) {
        $this->addUniqueKey($name, $key_name, $key);
      }
    }

    unset($transaction);

    // Create the indexes but ignore any error during the creation. We do that
    // do avoid pulling the carpet under modules that try to implement indexes
    // with invalid data types (long columns), before we come up with a better
    // solution.
    if (isset($table['indexes']) && is_array($table['indexes'])) {
      foreach ($table['indexes'] as $key_name => $key) {
        $this->addIndex($name, $key_name, $key, $table);
      }
    }
  }

  /**
   * Remove comments from an SQL statement.
   *
   * @param mixed $sql
   *   SQL statement to remove the comments from.
   * @param mixed $comments
   *   Comments removed from the statement.
   *
   * @return string
   *   SQL statement without comments.
   *
   * @see http://stackoverflow.com/questions/9690448/regular-expression-to-remove-comments-from-sql-statement
   */
  public function removeSqlComments($sql, &$comments = NULL) {
    $sqlComments = '@(([\'"]).*?[^\\\]\2)|((?:\#|--).*?$|/\*(?:[^/*]|/(?!\*)|\*(?!/)|(?R))*\*\/)\s*|(?<=;)\s+@ms';
    /* Commented version
    $sqlComments = '@
    (([\'"]).*?[^\\\]\2) # $1 : Skip single & double quoted expressions
    |(                   # $3 : Match comments
    (?:\#|--).*?$    # - Single line comments
    |                # - Multi line (nested) comments
    /\*             #   . comment open marker
    (?: [^/*]    #   . non comment-marker characters
    |/(?!\*) #   . ! not a comment open
    |\*(?!/) #   . ! not a comment close
    |(?R)    #   . recursive case
    )*           #   . repeat eventually
    \*\/             #   . comment close marker
    )\s*                 # Trim after comments
    |(?<=;)\s+           # Trim after semi-colon
    @msx';
     */
    $uncommentedSQL = trim(preg_replace($sqlComments, '$1', $sql));
    if (is_array($comments)) {
      preg_match_all($sqlComments, $sql, $comments);
      $comments = array_filter($comments[3]);
    }
    return $uncommentedSQL;
  }

  /**
   * Returns an array of current connection user options.
   *
   * Textsize    2147483647
   * language    us_english
   * dateformat    mdy
   * datefirst    7
   * lock_timeout    -1
   * quoted_identifier    SET
   * arithabort    SET
   * ansi_null_dflt_on    SET
   * ansi_warnings    SET
   * ansi_padding    SET
   * ansi_nulls    SET
   * concat_null_yields_null    SET
   * isolation level    read committed.
   *
   * @return mixed
   *   User options.
   */
  public function userOptions() {
    $result = $this->connection->queryDirect('DBCC UserOptions')->fetchAllKeyed();
    return $result;
  }

  /**
   * Retrieve Engine Version information.
   *
   * @return array
   *   Engine version.
   */
  public function engineVersion() {
    if (!isset($this->engineVersion)) {
      $this->engineVersion = $this->connection
        ->queryDirect(<<< EOF
          SELECT CONVERT (varchar,SERVERPROPERTY('productversion')) AS VERSION,
          CONVERT (varchar,SERVERPROPERTY('productlevel')) AS LEVEL,
          CONVERT (varchar,SERVERPROPERTY('edition')) AS EDITION
EOF
        )->fetchAssoc();
    }
    return $this->engineVersion;
  }

  /**
   * Retrieve Major Engine Version Number as integer.
   *
   * @return int
   *   Engine Version Number.
   */
  public function engineVersionNumber() {
      $version = $this->EngineVersion();
      $start = strpos($version['VERSION'], '.');
      return intval(substr($version['VERSION'], 0, $start));
    // https://www.drupal.org/project/sqlsrv/issues/3432661
    // Check EDITION if it has 'SQL Azure' then return version more than 13.
//     if (str_contains($version['EDITION'], 'SQL Azure')) {
// log version
//error_log('SQL Azure version: ' . $version['VERSION']);
//      return 16;
//     }

//     $start = strpos($version['VERSION'], '.');
//     return intval(substr($version['VERSION'], 0, $start));
  }

  /**
   * Find if a table function exists.
   *
   * @param string $function
   *   Name of the function.
   *
   * @return bool
   *   True if the function exists, false otherwise.
   */
  public function functionExists($function) {
    // FN = Scalar Function
    // IF = Inline Table Function
    // TF = Table Function
    // FS | AF = Assembly (CLR) Scalar Function
    // FT | AT = Assembly (CLR) Table Valued Function.
    return $this->connection
      ->queryDirect("SELECT 1 FROM sys.objects WHERE object_id = OBJECT_ID('" . $function . "') AND type in (N'FN', N'IF', N'TF', N'FS', N'FT', N'AF')")
      ->fetchField() !== FALSE;
  }

  /**
   * Check if CLR is enabled.
   *
   * Required to run GROUP_CONCAT.
   *
   * @return bool
   *   Is CLR enabled?
   */
  public function clrEnabled() {
    return $this->connection
      ->queryDirect("SELECT CONVERT(int, [value]) as [enabled] FROM sys.configurations WHERE name = 'clr enabled'")
      ->fetchField() !== 1;
  }

  /**
   * Check if a column is of variable length.
   */
  private function isVariableLengthType($type) {
    $types = [
      'nvarchar' => TRUE,
      'ntext' => TRUE,
      'varchar' => TRUE,
      'varbinary' => TRUE,
      'image' => TRUE,
    ];
    return isset($types[$type]);
  }

  /**
   * Load field spec.
   *
   * Retrieve an array of field specs from
   * an array of field names.
   *
   * @param array $fields
   *   Table fields.
   * @param mixed $table
   *   Table name.
   */
  private function loadFieldsSpec(array $fields, $table) {
    $result = [];
    $info = $this->queryColumnInformation($table);
    foreach ($fields as $field) {
      $result[$field] = $info['columns'][$field];
    }
    return $result;
  }

  /**
   * Estimates the row size of a clustered index.
   *
   * @see https://msdn.microsoft.com/en-us/library/ms178085.aspx
   */
  public function calculateClusteredIndexRowSizeBytes($table, $fields, $unique = TRUE) {
    // The fields must already be in the database to retrieve their real size.
    $info = $this->queryColumnInformation($table);

    // Specify the number of fixed-length and variable-length columns
    // and calculate the space that is required for their storage.
    $num_cols = count($fields);
    $num_variable_cols = 0;
    $max_var_size = 0;
    $max_fixed_size = 0;
    foreach ($fields as $field) {
      if ($this->isVariableLengthType($info['columns'][$field]['type'])) {
        $num_variable_cols++;
        $max_var_size += $info['columns'][$field]['max_length'];
      }
      else {
        $max_fixed_size += $info['columns'][$field]['max_length'];
      }
    }

    // If the clustered index is nonunique, account for the uniqueifier column.
    if (!$unique) {
      $num_cols++;
      $num_variable_cols++;
      $max_var_size += 4;
    }

    // Part of the row, known as the null bitmap, is reserved to manage column
    // nullability. Calculate its size.
    $null_bitmap = 2 + (($num_cols + 7) / 8);

    // Calculate the variable-length data size.
    $variable_data_size = empty($num_variable_cols) ? 0 : 2 + ($num_variable_cols * 2) + $max_var_size;

    // Calculate total row size.
    $row_size = $max_fixed_size + $variable_data_size + $null_bitmap + 4;

    return $row_size;
  }

  /**
   * Create primary key.
   *
   * Create a Primary Key for the table, does not drop
   * any prior primary keys neither it takes care of cleaning
   * technical primary column. Only call this if you are sure
   * the table does not currently hold a primary key.
   *
   * @param string $table
   *   Table name.
   * @param mixed $fields
   *   Array of fields.
   */
  private function createPrimaryKey($table, $fields) {
    // To be on the safe side, on the most restrictive use case the limit
    // for a primary key clustered index is of 128 bytes (usually 900).
    // @see https://web.archive.org/web/20140510074940/http://blogs.msdn.com/b/jgalla/archive/2005/08/18/453189.aspx
    // If that is going to be exceeded, use a computed column.
    $csv_fields = $this->createKeySql($fields);
    $size = $this->calculateClusteredIndexRowSizeBytes($table, $this->createKeySql($fields, TRUE));
    $result = [];
    $index = FALSE;
    $field_specs = $this->loadFieldsSpec($fields, $table);

    if ($size >= self::PRIMARY_KEY_BYTES) {
      // Use a computed column instead, and create a custom index.
      $result[] = self::COMPUTED_PK_COLUMN_NAME . " AS (CONVERT(VARCHAR(32), HASHBYTES('MD5', CONCAT('',{$csv_fields})), 2)) PERSISTED NOT NULL";
      $result[] = "CONSTRAINT {{$table}_pkey} PRIMARY KEY CLUSTERED (" . self::COMPUTED_PK_COLUMN_NAME . ")";
      $index = TRUE;
    }
    else {
      $result[] = "CONSTRAINT {{$table}_pkey} PRIMARY KEY CLUSTERED ({$csv_fields})";
    }

    $this->connection->queryDirect('ALTER TABLE {' . $table . '} ADD ' . implode(' ', $result));
    $this->resetColumnInformation($table);
    // If we relied on a computed column for the Primary Key,
    // at least index the fields with a regular index.
    if ($index) {
      $this->addIndex($table, self::COMPUTED_PK_COLUMN_INDEX, $fields);
    }
  }

  /**
   * Generate SQL to create a new table from a Drupal schema definition.
   *
   * @param string $name
   *   The name of the table to create.
   * @param array $table
   *   A Schema API table definition array.
   *
   * @return array
   *   A collection of SQL statements to create the table.
   */
  protected function createTableSql($name, $table) {
    $statements = [];
    $sql_fields = [];
    foreach ($table['fields'] as $field_name => $field) {
      $sql_fields[] = $this->createFieldSql($name, $field_name, $this->processField($field));
      if (isset($field['description'])) {
        $statements[] = $this->createCommentSQL($field['description'], $name, $field_name);
      }
    }

    $sql = "CREATE TABLE {{$name}} (" . PHP_EOL;
    $sql .= implode("," . PHP_EOL, $sql_fields);
    $sql .= PHP_EOL . ")";
    array_unshift($statements, $sql);
    if (!empty($table['description'])) {
      $statements[] = $this->createCommentSql($table['description'], $name);
    }
    return $statements;
  }

  /**
   * Create Field SQL.
   *
   * Create an SQL string for a field to be used in table creation or
   * alteration.
   *
   * Before passing a field out of a schema definition into this
   * function it has to be processed by _db_process_field().
   *
   * @param string $table
   *   The name of the table.
   * @param string $name
   *   Name of the field.
   * @param mixed $spec
   *   The field specification, as per the schema data structure format.
   * @param bool $skip_checks
   *   Skip checks.
   *
   * @return string
   *   The SQL statement to create the field.
   */
  protected function createFieldSql($table, $name, $spec, $skip_checks = FALSE) {
    $sql = $this->connection->escapeField($name) . ' ';

    $sql .= $this->createDataType($table, $name, $spec);

    $sqlsrv_type = $spec['sqlsrv_type'];
    $sqlsrv_type_native = $spec['sqlsrv_type_native'];

    $is_text = in_array($sqlsrv_type_native, [
      'char',
      'varchar',
      'text',
      'nchar',
      'nvarchar',
      'ntext',
    ]);
    if ($is_text === TRUE) {
      // If collation is set in the spec array, use it.
      // Otherwise use the database default.
      if (isset($spec['binary'])) {
        $default_collation = $this->getCollation();
        if ($spec['binary'] === TRUE) {
          $sql .= ' COLLATE ' . preg_replace("/_C[IS]_/", "_CS_", $default_collation);
        }
        elseif ($spec['binary'] === FALSE) {
          $sql .= ' COLLATE ' . preg_replace("/_C[IS]_/", "_CI_", $default_collation);
        }
      }
    }

    if (isset($spec['not null']) && $spec['not null']) {
      $sql .= ' NOT NULL';
    }

    if (!$skip_checks) {
      if (isset($spec['default'])) {
        $default = $this->defaultValueExpression($sqlsrv_type, $spec['default']);
        $sql .= " CONSTRAINT {{$table}_{$name}_df} DEFAULT $default";
      }
      if (!empty($spec['identity'])) {
        $sql .= ' IDENTITY';
      }
      if (!empty($spec['unsigned'])) {
        $sql .= ' CHECK (' . $this->connection->escapeField($name) . ' >= 0)';
      }
    }
    return $sql;
  }

  /**
   * Create the data type from a field specification.
   */
  protected function createDataType($table, $name, $spec) {
    $sqlsrv_type = $spec['sqlsrv_type'];
    $sqlsrv_type_native = $spec['sqlsrv_type_native'];

    $lengthable = in_array($sqlsrv_type_native, [
      'char',
      'varchar',
      'nchar',
      'nvarchar',
    ]);

    if (!empty($spec['length']) && $lengthable) {
      $length = $spec['length'];
      if (is_int($length) && $this->isUtf8()) {
        // Do we need to check if this exceeds the max length?
        // If so, use varchar(max).
        $length *= 3;
      }
      return $sqlsrv_type_native . '(' . $length . ')';
    }
    elseif (in_array($sqlsrv_type_native, ['numeric', 'decimal']) && isset($spec['precision']) && isset($spec['scale'])) {
      // Maximum precision for SQL Server 2008 or greater is 38.
      // For previous versions it's 28.
      if ($spec['precision'] > 38) {
        // Logs an error.
        \Drupal::logger('sqlsrv')->warning("Field '@field' in table '@table' has had it's precision dropped from @precision to 38",
                [
                  '@field' => $name,
                  '@table' => $table,
                  '@precision' => $spec['precision'],
                ]
                );
        $spec['precision'] = 38;
      }
      return $sqlsrv_type_native . '(' . $spec['precision'] . ', ' . $spec['scale'] . ')';
    }
    else {
      return $sqlsrv_type;
    }
  }

  /**
   * Get the SQL expression for a default value.
   *
   * @param string $sqlsr_type
   *   Database data type.
   * @param mixed $default
   *   Default value.
   *
   * @return string
   *   An SQL Default expression.
   */
  private function defaultValueExpression($sqlsr_type, $default) {
    // The actual expression depends on the target data type as it might require
    // conversions.
    $result = is_string($default) ? $this->connection->quote($default) : $default;
    if (
      Utils::GetMSSQLType($sqlsr_type) == 'varbinary') {
      $default = addslashes($default);
      $result = "CONVERT({$sqlsr_type}, '{$default}')";
    }
    return $result;
  }

  /**
   * Create key SQL.
   *
   * Returns a list of field names comma separated ready
   * to be used in a SQL Statement.
   *
   * @param array $fields
   *   Array of field names.
   * @param bool $as_array
   *   Return an array or a string?
   *
   * @return array|string
   *   The comma separated fields, or an array of fields
   */
  protected function createKeySql(array $fields, $as_array = FALSE) {
    $ret = [];
    foreach ($fields as $field) {
      if (is_array($field)) {
        $ret[] = $field[0];
      }
      else {
        $ret[] = $field;
      }
    }
    if ($as_array) {
      return $ret;
    }
    return implode(', ', $ret);
  }

  /**
   * Returns the SQL needed to create an index.
   *
   * Supports XML indexes. Incomplete.
   *
   * @param string $table
   *   Table to create the index on.
   * @param string $name
   *   Name of the index.
   * @param array $fields
   *   Fields to be included in the Index.
   *
   * @return string
   *   SQL string.
   */
  protected function createIndexSql($table, $name, array $fields) {
    // Get information about current columns.
    $info = $this->queryColumnInformation($table);
    // Flatten $fields array if neccesary.
    $fields = $this->createKeySql($fields, TRUE);
    $fields_csv = implode(', ', $fields);
    return "CREATE INDEX {$name}_idx ON {{$table}} ({$fields_csv})";
  }

  /**
   * Set database-engine specific properties for a field.
   *
   * @param mixed $field
   *   A field description array, as specified in the schema documentation.
   */
  protected function processField($field) {
    $field['size'] = $field['size'] ?? 'normal';
    if (isset($field['type']) && ($field['type'] == 'serial' || $field['type'] == 'int') && isset($field['unsigned']) && $field['unsigned'] === TRUE && ($field['size'] == 'normal')) {
      $field['size'] = 'big';
    }
    // Set the correct database-engine specific datatype.
    // In case one is already provided, force it to lowercase.
    if (isset($field['sqlsrv_type'])) {
      $field['sqlsrv_type'] = mb_strtolower($field['sqlsrv_type']);
    }
    else {
      $map = $this->getFieldTypeMap();
      $field['sqlsrv_type'] = $map[$field['type'] . ':' . $field['size']];
    }

    $field['sqlsrv_type_native'] = Utils::GetMSSQLType($field['sqlsrv_type']);

    if (isset($field['type']) && $field['type'] == 'serial') {
      $field['identity'] = TRUE;
    }
    return $field;
  }

  /**
   * Return size information for current database.
   *
   * @return mixed
   *   Size info.
   */
  public function getSizeInfo() {
    $sql = <<< EOF
      SELECT
    DB_NAME(db.database_id) DatabaseName,
    (CAST(mfrows.RowSize AS FLOAT)*8)/1024 RowSizeMB,
    (CAST(mflog.LogSize AS FLOAT)*8)/1024 LogSizeMB,
    (CAST(mfstream.StreamSize AS FLOAT)*8)/1024 StreamSizeMB,
    (CAST(mftext.TextIndexSize AS FLOAT)*8)/1024 TextIndexSizeMB
FROM sys.databases db
    LEFT JOIN (SELECT database_id, SUM(size) RowSize FROM sys.master_files WHERE type = 0 GROUP BY database_id, type) mfrows ON mfrows.database_id = db.database_id
    LEFT JOIN (SELECT database_id, SUM(size) LogSize FROM sys.master_files WHERE type = 1 GROUP BY database_id, type) mflog ON mflog.database_id = db.database_id
    LEFT JOIN (SELECT database_id, SUM(size) StreamSize FROM sys.master_files WHERE type = 2 GROUP BY database_id, type) mfstream ON mfstream.database_id = db.database_id
    LEFT JOIN (SELECT database_id, SUM(size) TextIndexSize FROM sys.master_files WHERE type = 4 GROUP BY database_id, type) mftext ON mftext.database_id = db.database_id
    WHERE DB_NAME(db.database_id) = :database
EOF;
    // Database is defaulted from active connection.
    $options = $this->connection->getConnectionOptions();
    $database = $options['database'];
    return $this->connection->query($sql, [':database' => $database])->fetchObject();
  }

  /**
   * Get the collation.
   *
   * Get the collation of current connection whether
   * it has or not a database defined in it.
   *
   * @param string $table
   *   Table name.
   * @param string $column
   *   Column name.
   *
   * @return string
   *   Collation type.
   */
  public function getCollation($table = NULL, $column = NULL) {
    // No table or column provided, then get info about
    // database (if exists) or server default collation.
    if (empty($table) && empty($column)) {
      // Database is defaulted from active connection.
      $options = $this->connection->getConnectionOptions();
      $database = $options['database'] ?? NULL;
      if (!empty($database)) {
        // Default collation for specific table.
        // CONVERT defaults to returning only 30 chars.
        $sql = "SELECT CONVERT (varchar(50), DATABASEPROPERTYEX('$database', 'collation'))";
        return $this->connection->queryDirect($sql)->fetchField();
      }
      else {
        // Server default collation.
        $sql = "SELECT SERVERPROPERTY ('collation') as collation";
        return $this->connection->queryDirect($sql)->fetchField();
      }
    }

    $sql = <<< EOF
      SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, COLLATION_NAME, DATA_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = ':schema'
        AND TABLE_NAME = ':table'
        AND COLUMN_NAME = ':column'
EOF;
    $params = [];
    $params[':schema'] = $this->getDefaultSchema();
    $params[':table'] = $table;
    $params[':column'] = $column;
    $result = $this->connection->queryDirect($sql, $params)->fetchObject();
    return $result->COLLATION_NAME;
  }

  /**
   * Re-create keys associated to a table.
   */
  protected function recreateTableKeys($table, $new_keys) {
    if (isset($new_keys['primary key'])) {
      $this->addPrimaryKey($table, $new_keys['primary key']);
    }
    if (isset($new_keys['unique keys'])) {
      foreach ($new_keys['unique keys'] as $name => $fields) {
        $this->addUniqueKey($table, $name, $fields);
      }
    }
    if (isset($new_keys['indexes'])) {
      foreach ($new_keys['indexes'] as $name => $fields) {
        $this->addIndex($table, $name, $fields);
      }
    }
  }

  /**
   * Drop a constraint.
   *
   * @param string $table
   *   Table name.
   * @param string $name
   *   Constraint name.
   * @param bool $check
   *   Check if the constraint exists?
   */
  public function dropConstraint($table, $name, $check = TRUE) {
    // Check if constraint exists.
    if ($check) {
      // Do Something.
    }
    $sql = 'ALTER TABLE {' . $table . '} DROP CONSTRAINT [' . $name . ']';
    $this->connection->query($sql);
    $this->resetColumnInformation($table);
  }

  /**
   * Drop the related objects of a column (indexes, constraints, etc.).
   *
   * @param mixed $table
   *   Table name.
   * @param mixed $field
   *   Field name.
   */
  protected function dropFieldRelatedObjects($table, $field) {
    $prefixInfo = $this->getPrefixInfo($table, TRUE);
    // Fetch the list of indexes referencing this column.
    $sql = 'SELECT DISTINCT i.name FROM sys.columns c INNER JOIN sys.index_columns ic ON ic.object_id = c.object_id AND ic.column_id = c.column_id INNER JOIN sys.indexes i ON i.object_id = ic.object_id AND i.index_id = ic.index_id WHERE i.is_primary_key = 0 AND i.is_unique_constraint = 0 AND c.object_id = OBJECT_ID(:table) AND c.name = :name';
    $indexes = $this->connection->query($sql, [
      ':table' => $prefixInfo['table'],
      ':name' => $field,
    ]);
    foreach ($indexes as $index) {
      $this->connection->query('DROP INDEX [' . $index->name . '] ON {' . $table . '}');
      $this->resetColumnInformation($table);
    }

    // Fetch the list of check constraints referencing this column.
    $sql = 'SELECT DISTINCT cc.name FROM sys.columns c INNER JOIN sys.check_constraints cc ON cc.parent_object_id = c.object_id AND cc.parent_column_id = c.column_id WHERE c.object_id = OBJECT_ID(:table) AND c.name = :name';
    $constraints = $this->connection->query($sql, [
      ':table' => $prefixInfo['table'],
      ':name' => $field,
    ]);
    foreach ($constraints as $constraint) {
      $this->dropConstraint($table, $constraint->name, FALSE);
    }

    // Fetch the list of default constraints referencing this column.
    $sql = 'SELECT DISTINCT dc.name FROM sys.columns c INNER JOIN sys.default_constraints dc ON dc.parent_object_id = c.object_id AND dc.parent_column_id = c.column_id WHERE c.object_id = OBJECT_ID(:table) AND c.name = :name';
    $constraints = $this->connection->query($sql, [
      ':table' => $prefixInfo['table'],
      ':name' => $field,
    ]);
    foreach ($constraints as $constraint) {
      $this->dropConstraint($table, $constraint->name, FALSE);
    }

    // Drop any indexes on related computed columns when we have some.
    if ($this->uniqueKeyExists($table, $field)) {
      $this->dropUniqueKey($table, $field);
    }

    // If this column is part of a computed primary key, drop the key.
    $data = $this->queryColumnInformation($table);
    if (isset($data['columns'][self::COMPUTED_PK_COLUMN_NAME]['dependencies'][$field])) {
      $this->cleanUpPrimaryKey($table);
    }
  }

  /**
   * Return the name of the primary key of a table if it exists.
   *
   * @param mixed $table
   *   Table name.
   */
  protected function primaryKeyName($table) {
    $prefixInfo = $this->getPrefixInfo($table, TRUE);
    $sql = 'SELECT name FROM sys.key_constraints WHERE parent_object_id = OBJECT_ID(:table) AND type = :type';
    return $this->connection->query($sql, [
      ':table' => $prefixInfo['table'],
      ':type' => 'PK',
    ])->fetchField();
  }

  /**
   * Check if a key is a technical primary key.
   *
   * @param string $key_name
   *   Key name.
   */
  protected function isTechnicalPrimaryKey($key_name) {
    return $key_name && preg_match('/_pkey_technical$/', $key_name);
  }

  /**
   * Is the database configured as UTF8 character encoding?
   */
  protected function isUtf8() {
    $collation = $this->getCollation();
    return stristr($collation, '_UTF8') !== FALSE;
  }

  /**
   * Add a primary column to the table.
   *
   * @param mixed $table
   *   Table name.
   */
  protected function createTechnicalPrimaryColumn($table) {
    if (!$this->fieldExists($table, self::TECHNICAL_PK_COLUMN_NAME)) {
      $this->connection->query("ALTER TABLE {{$table}} ADD " . self::TECHNICAL_PK_COLUMN_NAME . " UNIQUEIDENTIFIER DEFAULT NEWID() NOT NULL");
      $this->resetColumnInformation($table);
    }
  }

  /**
   * Drop the primary key constraint.
   *
   * @param mixed $table
   *   Table name.
   */
  protected function cleanUpPrimaryKey($table) {
    // We are droping the constraint, but not the column.
    $existing_primary_key = $this->primaryKeyName($table);
    if ($existing_primary_key !== FALSE) {
      $this->dropConstraint($table, $existing_primary_key, FALSE);
    }
    // We are using computed columns to store primary keys,
    // try to remove it if it exists.
    if ($this->fieldExists($table, self::COMPUTED_PK_COLUMN_NAME)) {
      // The TCPK has compensation indexes that need to be cleared.
      $this->dropIndex($table, self::COMPUTED_PK_COLUMN_INDEX);
      $this->dropField($table, self::COMPUTED_PK_COLUMN_NAME);
    }
    // Try to get rid of the TPC.
    $this->cleanUpTechnicalPrimaryColumn($table);
  }

  /**
   * Tries to clean up the technical primary column.
   *
   * It will be deleted if:
   * (a) It is not being used as the current primary key and...
   * (b) There is no unique constraint because they depend on this column
   * (see addUniqueKey())
   *
   * @param string $table
   *   Table name.
   */
  protected function cleanUpTechnicalPrimaryColumn($table) {
    // Get the number of remaining unique indexes on the table, that
    // are not primary keys and prune the technical primary column if possible.
    $prefixInfo = $this->getPrefixInfo($table, TRUE);
    $sql = 'SELECT COUNT(*) FROM sys.indexes WHERE object_id = OBJECT_ID(:table) AND is_unique = 1 AND is_primary_key = 0';
    $args = [':table' => $prefixInfo['table']];
    $unique_indexes = $this->connection->query($sql, $args)->fetchField();
    $primary_key_is_technical = $this->isTechnicalPrimaryKey($this->primaryKeyName($table));
    if (!$unique_indexes && !$primary_key_is_technical) {
      $this->dropField($table, self::TECHNICAL_PK_COLUMN_NAME);
    }
  }

  /**
   * Find if an unique key exists.
   *
   * @param mixed $table
   *   Table name.
   * @param mixed $name
   *   Index name.
   *
   * @return bool
   *   Does the key exist?
   */
  protected function uniqueKeyExists($table, $name) {
    $prefixInfo = $this->getPrefixInfo($table, TRUE);
    return (bool) $this->connection->query('SELECT 1 FROM sys.indexes WHERE object_id = OBJECT_ID(:table) AND name = :name', [
      ':table' => $prefixInfo['table'],
      ':name' => $name . '_unique',
    ])->fetchField();
  }

  /**
   * Create an SQL statement to delete a comment.
   */
  protected function deleteCommentSql($table = NULL, $column = NULL) {
    $schema = $this->getDefaultSchema();
    $prefixInfo = $this->getPrefixInfo($table, TRUE);
    $prefixed_table = $prefixInfo['table'];
    $sql = "EXEC sp_dropextendedproperty @name=N'MS_Description'";
    $sql .= ",@level0type = N'Schema', @level0name = '" . $schema . "'";
    if (isset($table)) {
      $sql .= ",@level1type = N'Table', @level1name = '{$prefixed_table}'";
      if (isset($column)) {
        $sql .= ",@level2type = N'Column', @level2name = '{$column}'";
      }
    }
    return $sql;
  }

  /**
   * Create the SQL statement to add a new comment.
   */
  protected function createCommentSql($value, $table = NULL, $column = NULL) {
    $schema = $this->getDefaultSchema();
    $value = $this->prepareComment($value);
    $prefixInfo = $this->getPrefixInfo($table, TRUE);
    $prefixed_table = $prefixInfo['table'];
    $sql = "EXEC sp_addextendedproperty @name=N'MS_Description', @value={$value}";
    $sql .= ",@level0type = N'Schema', @level0name = '{$schema}'";
    if (isset($table)) {
      $sql .= ",@level1type = N'Table', @level1name = '{$prefixed_table}'";
      if (isset($column)) {
        $sql .= ",@level2type = N'Column', @level2name = '{$column}'";
      }
    }
    return $sql;
  }

  /**
   * Retrieve a table or column comment.
   */
  public function getComment($table, $column = NULL) {
    $prefixInfo = $this->getPrefixInfo($table, TRUE);
    $prefixed_table = $prefixInfo['table'];
    $schema = $this->getDefaultSchema();
    $column_string = isset($column) ? "'Column','{$column}'" : "NULL,NULL";
    $sql = "SELECT value FROM fn_listextendedproperty ('MS_Description','Schema','{$schema}','Table','{$prefixed_table}',{$column_string})";
    $comment = $this->connection->query($sql)->fetchField();
    return $comment;
  }

  /**
   * Clone a table.
   */
  public function createTableAs($new_table, $old_table, $with_data = FALSE) {
    if (!$this->tableExists($old_table)) {
      throw new SchemaObjectDoesNotExistException("The table $old_table doesn't exist.");
    }
    if ($this->tableExists($new_table)) {
      throw new SchemaObjectExistsException("The table $new_table already exists.");
    }

    $column_info = $this->queryColumnInformation($old_table);
    $columns_clean = $column_info['columns_clean'];
    $columns_clean_string = implode(', ', array_keys($columns_clean));

    $prefix = $this->connection->tablePrefix($old_table);

    $data = '';
    if (!$with_data) {
      $data = "WHERE 0=1";
    }

    return $this->connection->query("SELECT {$columns_clean_string} INTO {$prefix}{$new_table} FROM {$prefix}{$old_table} {$data}");
  }

}

/**
 * @} End of "addtogroup schemaapi".
 */
