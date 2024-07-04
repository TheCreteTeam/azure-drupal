<?php

namespace Drupal\sqlsrv\Driver\Database\sqlsrv;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\Query\Update as QueryUpdate;

/**
 * Sqlsvr implementation of \Drupal\Core\Database\Query\Update.
 */
class Update extends QueryUpdate {

  /**
   * {@inheritdoc}
   */
  public function execute() {

    // Retrieve query options.
    $options = $this->queryOptions;

    // Fetch the list of blobs and sequences used on that table.
    /** @var \Drupal\sqlsrv\Driver\Database\sqlsrv\Schema $schema */
    $schema = $this->connection->schema();
    $columnInformation = $schema->queryColumnInformation($this->table);

    // Because we filter $fields the same way here and in __toString(), the
    // placeholders will all match up properly.
    /** @var \Drupal\Core\Database\StatementWrapper $stmt */
    $stmt = $this->connection->prepareStatement((string) $this, [], TRUE);
    // Expressions take priority over literal fields, so we process those first
    // and remove any literal fields that conflict.
    $fields = $this->fields;
    foreach ($this->expressionFields as $field => $data) {
      if (!empty($data['arguments'])) {
        foreach ($data['arguments'] as $placeholder => $argument) {
          // We assume that an expression will never happen on a BLOB field,
          // which is a fairly safe assumption to make since in most cases
          // it would be an invalid query anyway.
          $stmt->getClientStatement()->bindParam($placeholder, $data['arguments'][$placeholder]);
        }
      }
      if ($data['expression'] instanceof SelectInterface) {
        $data['expression']->compile($this->connection, $this);
        $select_query_arguments = $data['expression']->arguments();
        foreach ($select_query_arguments as $placeholder => $argument) {
          $stmt->getClientStatement()->bindParam($placeholder, $select_query_arguments[$placeholder]);
        }
      }
      unset($fields[$field]);
    }

    // We use this array to store references to the blob handles.
    // This is necessary because the PDO will otherwise messes up with
    // references.
    $blobs = [];
    Utils::bindValues($stmt, $fields, $blobs, ':db_update_placeholder_', $columnInformation);

    // Add conditions.
    if (count($this->condition)) {
      $this->condition->compile($this->connection, $this);
      $arguments = $this->condition->arguments();
      Utils::bindArguments($stmt, $arguments);
    }
    try {
      $stmt->execute(NULL, $this->queryOptions);
      $row_count = $stmt->rowCount();
    }
    catch (\Exception $e) {
      $this->connection->exceptionHandler()->handleExecutionException($e, $stmt, $fields, $this->queryOptions);
    }
    return $row_count;
  }

}
