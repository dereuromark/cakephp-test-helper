<?php

namespace TestApp\TestSuite;

use Cake\Utility\Hash;
use RuntimeException;

/**
 * A class to contain and retain the session during integration testing.
 * Read only.
 */
class TestSession {

	/**
	 * @var array|null
	 */
	protected $session;

	/**
	 * @param array|null $session
	 */
	public function __construct(?array $session)
	{
		$this->session = $session;
	}

	/**
	 * Returns true if given variable name is set in session.
	 *
	 * @param string|null $name Variable name to check for
	 * @return bool True if variable is there
	 */
	public function check(?string $name = null): bool
	{
		if ($this->session === null) {
			return false;
		}

		return Hash::get($this->session, $name) !== null;
	}

	/**
	 * Returns given session variable, or all of them, if no parameters given.
	 *
	 * @param string|null $name The name of the session variable (or a path as sent to Hash.extract)
	 * @return string|array|null The value of the session variable, null if session not available,
	 *   session not started, or provided name not found in the session.
	 */
	public function read(?string $name = null)
	{
		if ($this->session === null) {
			return null;
		}

		if ($name === null) {
			return $this->session ?: [];
		}

		return Hash::get($this->session, $name);
	}

	/**
	 * The session key/value pair fetched via this method is expected to exist.
	 * In case it does not an exception will be thrown.
	 *
	 * Usage:
	 * ```
	 * ->readOrFail('Name'); will return all values for Name
	 * ->readOrFail('Name.key'); will return only the value of session Name[key]
	 * ```
	 *
	 * @param string $var Variable to obtain. Use '.' to access array elements.
	 * @return mixed Value stored in session.
	 * @throws \RuntimeException if the requested session is not set.
	 */
	public function readOrFail($var)
	{
		if ($this->check($var) === false) {
			throw new RuntimeException(sprintf('Expected session key "%s" not found.', $var));
		}

		return $this->read($var);
	}
}
