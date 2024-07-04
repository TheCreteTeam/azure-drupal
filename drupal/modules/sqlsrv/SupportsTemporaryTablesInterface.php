<?php

namespace Drupal\sqlsrv\Driver\Database\sqlsrv;

use Drupal\Core\Database\SupportsTemporaryTablesInterface as CoreSupportsTemporaryTablesInterface;

/**
 * Provide SupportsTemporaryTablesInterface for Drupal 9.
 *
 * This interface extends the D10 core interace when available, allowing us to
 * be compatible with both D9 and D10 at the same time.
 */
if (interface_exists(CoreSupportsTemporaryTablesInterface::class)) {
  /**
   * Wraps core D10 interface for supporting temporary tables.
   *
   * @ingroup database
   */
  interface SupportsTemporaryTablesInterface extends CoreSupportsTemporaryTablesInterface {

    /**
     * {@inheritdoc}
     */
    public function queryTemporary($query, array $args = [], array $options = []);

  }
}
else {
  /**
   * Adds support for temporary tables.
   *
   * Provided here as core interface is not defined in D9.
   *
   * @ingroup database
   */
  interface SupportsTemporaryTablesInterface {

    /**
     * Runs a SELECT query and stores its results in a temporary table.
     *
     * Use this as a substitute for ->query() when the results need to stored
     * in a temporary table. Temporary tables exist for the duration of the
     * page request. User-supplied arguments to the query should be passed in
     * as separate parameters so that they can be properly escaped to avoid
     * SQL injection attacks.
     *
     * Note that if you need to know how many results were returned, you should
     * do a SELECT COUNT(*) on the temporary table afterwards.
     *
     * @param string $query
     *   A string containing a normal SELECT SQL query.
     * @param array $args
     *   (optional) An array of values to substitute into the query at
     *   placeholder markers.
     * @param array $options
     *   (optional) An associative array of options to control how the query is
     *   run. See the documentation for DatabaseConnection::defaultOptions() for
     *   details.
     *
     * @return string
     *   The name of the temporary table.
     */
    public function queryTemporary($query, array $args = [], array $options = []);

  }
}
