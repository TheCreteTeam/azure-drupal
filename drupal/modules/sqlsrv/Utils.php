<?php

namespace Drupal\sqlsrv\Driver\Database\sqlsrv;

use Drupal\Core\Database\StatementWrapper;
use Symfony\Component\Yaml\Parser;

/**
 * Utility function for the SQL Server driver.
 */
class Utils {

  /**
   * Bind the arguments to the statement.
   *
   * @param \Drupal\Core\Database\StatementWrapper $stmt
   *   Statement.
   * @param array $values
   *   Argument values.
   */
  public static function bindArguments(StatementWrapper $stmt, array &$values) {
    foreach ($values as $key => &$value) {
      $stmt->getClientStatement()->bindParam($key, $value, \PDO::PARAM_STR);
    }
  }

  /**
   * Binds a set of values to a PDO Statement.
   *
   * Takes care of properly managing binary data.
   *
   * @param \Drupal\Core\Database\StatementWrapper $stmt
   *   PDOStatement to bind the values to.
   * @param array $values
   *   Values to bind. It's an array where the keys are column
   *   names and the values what is going to be inserted.
   * @param array $blobs
   *   When sending binary data to the PDO driver, we need to keep
   *   track of the original references to data.
   * @param mixed $placeholder_prefix
   *   Prefix to use for generating the query placeholders.
   * @param array $columnInformation
   *   Column information.
   * @param mixed $max_placeholder
   *   Placeholder count, if NULL will start with 0.
   * @param mixed $blob_suffix
   *   Suffix for the blob key.
   */
  public static function bindValues(StatementWrapper $stmt, array &$values, array &$blobs, $placeholder_prefix, array $columnInformation, &$max_placeholder = NULL, $blob_suffix = NULL) {
    if (empty($max_placeholder)) {
      $max_placeholder = 0;
    }
    foreach ($values as $field_name => &$field_value) {
      $placeholder = $placeholder_prefix . $max_placeholder++;
      $blob_key = $placeholder . $blob_suffix;
      if (isset($columnInformation['blobs'][$field_name])) {
        if ($field_value === NULL) {
          $blobs[$blob_key] = NULL;
        }
        else {
          $blobs[$blob_key] = fopen('php://memory', 'a');
          fwrite($blobs[$blob_key], $field_value);
          rewind($blobs[$blob_key]);
        }
        $stmt->getClientStatement()->bindParam($placeholder, $blobs[$blob_key], \PDO::PARAM_LOB, 0, \PDO::SQLSRV_ENCODING_BINARY);
      }
      else {
        // Even though not a blob, make sure we retain a copy of these values.
        $blobs[$blob_key] = $field_value;
        $stmt->getClientStatement()->bindParam($placeholder, $blobs[$blob_key], \PDO::PARAM_STR);
      }
    }
  }

  /**
   * Returns the spec for a MSSQL data type definition.
   *
   * @param string $type
   *   Data type.
   *
   * @return string
   *   Data type spec.
   */
  public static function getMssqlType($type) {
    $matches = [];
    if (preg_match('/^[a-zA-Z]*/', $type, $matches)) {
      return reset($matches);
    }
    return $type;
  }

  /**
   * Deploy custom functions for Drupal Compatiblity.
   *
   * @param Connection $connection
   *   Connection used for deployment.
   * @param bool $redeploy
   *   Wether to redeploy existing functions, or only missing ones.
   */
  public static function deployCustomFunctions(Connection $connection, $redeploy = FALSE) {
    $yaml = new Parser();
    $base_path = dirname(__FILE__) . '/Programability';
    $configuration = $yaml->parse(file_get_contents("$base_path/configuration.yml"));

    /** @var Schema $schema */
    $schema = $connection->schema();

    foreach ($configuration['functions'] as $function) {
      $name = $function['name'];
      $path = "$base_path/{$function['file']}";
      $exists = $schema->functionExists($name);
      if ($exists && !$redeploy) {
        continue;
      }
      if ($exists) {
        $connection->queryDirect("DROP FUNCTION [{$name}]");
      }
      $script = trim(static::removeUtf8Bom(file_get_contents($path)));
      $connection->queryDirect($script);
    }
  }

  /**
   * Remove UTF8 BOM.
   *
   * @param string $text
   *   UTF8 text.
   *
   * @return string
   *   Text without UTF8 BOM.
   */
  private static function removeUtf8Bom($text) {
    $bom = pack('H*', 'EFBBBF');
    $text = preg_replace("/^$bom/", '', $text);
    return $text;
  }

  /**
   * Given a string find the matching parenthesis after the given point.
   *
   * @param string $string
   *   The input string.
   * @param int $start_paren
   *   The 0 indexed position of the open-paren, for which we would like
   *   to find the matching closing-paren.
   *
   * @return int|false
   *   The 0 indexed position of the close paren.
   */
  public static function findParenMatch($string, $start_paren) {
    $len = strlen($string);
    if ($len < $start_paren + 1 || $len < 2 || $start_paren < 0) {
      return FALSE;
    }
    if ($string[$start_paren] !== '(') {
      return FALSE;
    }
    $str_array = str_split(substr($string, $start_paren + 1));
    $paren_num = 1;
    foreach ($str_array as $i => $char) {
      if ($char == '(') {
        $paren_num++;
      }
      elseif ($char == ')') {
        $paren_num--;
      }
      if ($paren_num == 0) {
        return $i + $start_paren + 1;
      }
    }
    return FALSE;
  }

}
