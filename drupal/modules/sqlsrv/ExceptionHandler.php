<?php

namespace Drupal\sqlsrv\Driver\Database\sqlsrv;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\ExceptionHandler as BaseExceptionHandler;
use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Database\StatementInterface;

/**
 * Sql Server database exception handler class.
 */
class ExceptionHandler extends BaseExceptionHandler {

  /**
   * {@inheritdoc}
   */
  public function handleExecutionException(\Exception $exception, StatementInterface $statement, array $arguments = [], array $options = []): void {
    if (array_key_exists('throw_exception', $options)) {
      @trigger_error('Passing a \'throw_exception\' option to ' . __METHOD__ . ' is deprecated in drupal:9.2.0 and is removed in drupal:10.0.0. Always catch exceptions. See https://www.drupal.org/node/3201187', E_USER_DEPRECATED);
      if (!($options['throw_exception'])) {
        return;
      }
    }

    if ($exception instanceof \PDOException) {
      // Wrap the exception in another exception, because PHP does not allow
      // overriding Exception::getMessage(). Its message is the extra database
      // debug information.
      $code = is_int($exception->getCode()) ? $exception->getCode() : 0;

      $message = $exception->getMessage() . ": " . $statement->getQueryString() . "; " . print_r($arguments, TRUE);

      // SQLSTATE[23000] errors indicate an integrity constraint violation.
      // SQLSTATE[42000] is also sometimes considered an integrity violation.
      // Convert int|false to true|false.
      $identity_update = strpos($exception->getMessage(), 'Cannot update identity column') !== FALSE;
      if ($exception->getCode() == '23000' || $identity_update) {
        throw new IntegrityConstraintViolationException($message, $code, $exception);
      }

      throw new DatabaseExceptionWrapper($message, 0, $exception);
    }

    throw $exception;
  }

}
