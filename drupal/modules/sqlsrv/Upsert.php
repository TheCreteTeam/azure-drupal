<?php

namespace Drupal\sqlsrv\Driver\Database\sqlsrv;

use Drupal\Core\Database\Query\Upsert as QueryUpsert;

/**
 * Implements Native Upsert queries for MSSQL.
 */
class Upsert extends QueryUpsert {

  const MAX_BATCH_SIZE = 200;

  /**
   * Does the upsert require setting an identity column?
   *
   * @var bool
   */
  protected $setIdentity = FALSE;

  /**
   * The connection object on which to run this query.
   *
   * @var \Drupal\sqlsrv\Driver\Database\sqlsrv\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if (!$this->preExecute()) {
      return NULL;
    }
    // Fetch the list of blobs and sequences used on that table.
    /** @var \Drupal\sqlsrv\Driver\Database\sqlsrv\Schema $schema */
    $schema = $this->connection->schema();
    $columnInformation = $schema->queryColumnInformation($this->table);
    $this->queryOptions['allow_delimiter_in_query'] = TRUE;
    $max_placeholder = -1;
    $values = [];
    $affected_rows = 0;
    foreach ($this->insertValues as $insert_values) {
      foreach ($insert_values as $value) {
        $values[':db_upsert_placeholder_' . ++$max_placeholder] = $value;
      }
    }
    $batch = array_splice($this->insertValues, 0, min(intdiv(2000, count($this->insertFields)), self::MAX_BATCH_SIZE));

    // If we are going to need more than one batch for this, start a
    // transaction.
    if (empty($this->queryOptions['sqlsrv_skip_transactions']) && !empty($this->insertValues)) {
      $transaction = $this->connection->startTransaction();
    }

    while (!empty($batch)) {
      // Give me a query with the amount of batch inserts.
      $query = $this->buildQuery(count($batch));

      // Prepare the query.
      /** @var \Drupal\Core\Database\Statement $stmt */
      $stmt = $this->connection->prepareStatement($query, $this->queryOptions, TRUE);

      // We use this array to store references to the blob handles.
      // This is necessary because the PDO will otherwise mess up with
      // references.
      $blobs = [];

      $max_placeholder = 0;
      foreach ($batch as $insert_index => $insert_values) {
        $values = array_combine($this->insertFields, $insert_values);
        Utils::bindValues($stmt, $values, $blobs, ':db_upsert_placeholder_', $columnInformation, $max_placeholder, $insert_index);
      }
      // If one of the insert fields is part of the primary key, the insert must
      // be done with the "IDENTITY_INSERT" setting on.
      $identity_insert = FALSE;
      foreach ($this->insertFields as $insert_field) {
        if ($this->isPartOfPrimaryKey($insert_field)) {
          $identity_insert = TRUE;
        }
      }
      try {
        if ($identity_insert) {
          $this->connection->queryDirect('SET IDENTITY_INSERT {' . $this->table . '} ON;');
        }
        // Run the query.
        $stmt->execute(NULL, $this->queryOptions);
        $affected_rows += $stmt->rowCount();
        if ($identity_insert) {
          $this->connection->queryDirect('SET IDENTITY_INSERT {' . $this->table . '} OFF;');
        }
      }
      catch (\Exception $e) {
        $this->connection->exceptionHandler()->handleExecutionException($e, $stmt, $batch, $this->queryOptions);
      }

      // Fetch the next batch.
      $batch = array_splice($this->insertValues, 0, min(intdiv(2000, count($this->insertFields)), self::MAX_BATCH_SIZE));
    }
    // Re-initialize the values array so that we can re-use this query.
    $this->insertValues = [];

    return $affected_rows;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return $this->buildQuery(count($this->insertValues));
  }

  /**
   * The aspect of the query depends on the batch size...
   *
   * @param int $batch_size
   *   The number of inserts to perform on a single statement.
   *
   * @throws \Exception
   *
   * @return string
   *   SQL Statement.
   */
  private function buildQuery($batch_size) {
    // Make sure we don't go crazy with this numbers.
    if ($batch_size > self::MAX_BATCH_SIZE) {
      throw new \Exception("MSSQL Native Batch Insert limited to 250.");
    }
    // Do we to escape fields?
    $key = $this->connection->escapeField($this->key);
    $all_fields = array_merge($this->defaultFields, $this->insertFields);

    $placeholders = [];
    $row = [];
    $max_placeholder = -1;
    $field_count = count($this->insertFields);
    for ($i = 0; $i < $batch_size; $i++) {
      for ($j = 0; $j < $field_count; $j++) {
        $row[] = ':db_upsert_placeholder_' . ++$max_placeholder;
      }
      $placeholders[] = '(' . implode(', ', $row) . ')';
      $row = [];
    }
    $placeholder_list = implode(', ', $placeholders);
    $insert_count = count($this->insertValues);
    $field_count = count($all_fields);

    $insert_fields = [];
    $update_fields = [];
    $all_fields_escaped = [];
    foreach ($all_fields as $field) {
      $is_primary_key = $this->isPartOfPrimaryKey($field);
      $this->setIdentity = $this->setIdentity || $is_primary_key;
      $field = $this->connection->escapeField($field);
      $all_fields_escaped[] = $field;
      $insert_fields[] = 'src.' . $field;
      if (!$is_primary_key) {
        $update_fields[] = $field . '=src.' . $field;
      }
    }
    $insert_list = '(' . implode(', ', $insert_fields) . ')';
    $update_list = implode(', ', $update_fields);
    $field_list = '(' . implode(', ', $all_fields_escaped) . ')';
    $values_string = 'VALUES ' . $placeholder_list;
    $update_string = 'UPDATE SET ' . $update_list;
    $insert_string = 'INSERT ' . $field_list . ' VALUES ' . $insert_list;
    $query = 'MERGE {' . $this->table . '} AS tgt USING(' . $values_string . ')';
    $query .= ' AS src ' . $field_list . ' ON tgt.' . $key . '=src.' . $key;
    $query .= ' WHEN MATCHED THEN ' . $update_string;
    $query .= ' WHEN NOT MATCHED THEN ' . $insert_string . ';';

    return $query;
  }

  /**
   * Checks if the column is part of the primary key.
   *
   * @param string $column_name
   *   The number of inserts to perform on a single statement.
   *
   * @return bool
   *   True when the column is part of the primary key, otherwise false.
   */
  private function isPartOfPrimaryKey(string $column_name): bool {
    /** @var \Drupal\sqlsrv\Driver\Database\sqlsrv\Schema $schema */
    $schema = $this->connection->schema();
    $columnInformation = $schema->queryColumnInformation($this->table);
    $primary_key_columns = $columnInformation['identities'] ?? [];
    return array_key_exists($column_name, $primary_key_columns);
  }

}
