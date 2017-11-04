<?php

/*
 * This file is part of ieUtilities HTTP.
 *
 * (c) 2016 Philipp Steingrebe <development@steingrebe.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Everest\App;
use Countable;
use IteratorAggregate;
use ArrayIterator;

class Session implements 
    SessionInterface, 
    Countable, 
    IteratorAggregate
{
  /**
   * The default session options
   * @var array
   */
  
  private $options = [
      'secure'     => false,
      'httponly'   => false,
      'cookieonly' => true
      /*
      'lifetime'   => 1800,
      'domain'     => '.example.com',
      'path'       => '/'
      */
  ];

  /**
   * The session state
   * @var bool
   */
  
  private $started;


  /**
   * Returns wether or not a session is currently running
   * @return bool
   */
  
  private static function started() : bool
  {
    return function_exists('session_status') ? session_status() === \PHP_SESSION_ACTIVE : '' !== session_id();
  }

  /**
   * Returns wether or not the session is currently stopped
   * @return bool
   */
  
  private static function stoped() : bool
  {
    return function_exists('session_status') ? session_status() === \PHP_SESSION_NONE : '' === session_id();
  }


  /**
   * Constructor
   *
   * @param array $options 
   *    The options to use for this session instance
   */
  
  function __construct(array $options = [])
  {
    $this->options = array_merge(session_get_cookie_params(), $this->options, $options);
    $this->started = $this->started();
  }


  /**
   * {@inheritDoc}
   */
  
  public function start()
  {
    if ($this->started) {
      return $this;
    }

    if (self::started()) {
      throw new \Exception('Session is already running.');
    }

    
    if (ini_set('session.use_only_cookies', $this->options['cookieonly']) === false) {
      throw new \Exception('Error setting \'session.use_only_cookies\'.');
    }
   
    if (ini_set('session.cookie_httponly', $this->options['httponly']) === false) {
      throw new \Exception('Error setting \'session.cookie_httponly\'.');
    }

    session_set_cookie_params(
      $this->options['lifetime'],
      $this->options['path'],
      $this->options['domain'],
      $this->options['secure'], 
      $this->options['httponly']
    );

    if (!($this->started = session_start())) {
      throw new \Exception('Unable to start session.');
    }
  }


  /**
   * {@inheritDoc}
   */
  
  public function stop()
  {
      if (!session_write_close()) {
          throw new \Exception('Unable to stop session.');
      }

      $this->started = false;

      return $this;
  }


  /**
   * {@inheritDoc}
   */

  public function has(string $key) : bool
  {
      return isset($_SESSION[$key]);
  }


  /**
   * {@inheritDoc}
   */

  public function get(string $key) 
  {
      return $_SESSION[$key] ?? null;
  }


  /**
   * {@inheritDoc}
   */

  public function set(string $key, $value, array $options = [])
  {
      $_SESSION[$key] = $value;
      return $this;
  }


  /**
   * {@inheritDoc}
   * 
   * Not implemented
   * 
   * @throws \Exeption 
   *    When called
   */

  public function with(string $key, $value, array $options = [])
  {
    throw new \LogicException('Not implemented');
  }


  /**
   * {@inheritDoc}
   */
  
  public function push(string $key, $value)
  {
      // Not yet set
      if (!$this->has($key)) {
          return $this->set($key, [$value]);
      }

      // Already set with array value
      if (is_array($_SESSION[$key])) {
          $_SESSION[$key][] = $value;
          return $this;
      }

      // Set with single value
      $_SESSION[$key] = [$_SESSION[$key], $value];

      return $this;
  }


  /**
   * {@inheritDoc}
   * 
   * Not implemented
   * 
   * @throws \Exeption 
   *    When called
   */

  public function withAdded(string $key, $value)
  {
    throw new \LogicException('Not implemented');
  }


  /**
   * {@inheritDoc}
   */

  public function delete(string $key)
  {
      unset($_SESSION[$key]);
  }


  /**
   * {@inheritDoc}
   * 
   * Not implemented
   * 
   * @throws \Exeption 
   *    When called
   */

  public function without(string $key)
  {
    throw new \LogicException('Not implemented');
  }


  /**
   * {@inheritDoc}
   */
  
  public function destroy()
  {
      unset($_SESSION);
      $this->stop();
  }


  /**
   * Returns string representation of this collection for debug proposes
   * @return string
   */

  public function __toString()
  {
      $string = '';

      foreach($_SESSION as $key => $parameter) {
          $string .= sprintf("%s = %s\r\n", $key, $parameter);
      }

      return $string;
  }


  /**
   * Gets the parameter count of this collection to satisfy the Countable interface.
   * @return int
   */
  
  public function count()
  {
      return count($_SESSION);
  }


  /**
   * Creates a new ArrayIterator to satisfy the IteratorAggregate interface.
   * @return ArrayIterator
   */
  
  public function getIterator()
  {
    return new ArrayIterator($_SESSION);
  }
}