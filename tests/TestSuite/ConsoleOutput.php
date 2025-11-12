<?php

namespace TestHelper\Test\TestSuite;

use Cake\Console\ConsoleOutput as CakeConsoleOutput;

/**
 * Console Output stub for capturing output in tests
 */
class ConsoleOutput extends CakeConsoleOutput {

	/**
	 * @var array<string>
	 */
	protected array $messages = [];

	/**
	 * Write output to the buffer.
	 *
	 * @param array<string>|string $message A string or an array of strings to output
	 * @param int $newlines Number of newlines to append
	 * @return int The number of bytes written.
	 */
	public function write(array|string $message, int $newlines = 1): int {
		if (is_array($message)) {
			$message = implode('', $message);
		}

		$message .= str_repeat("\n", $newlines);
		$this->messages[] = $message;

		return strlen($message);
	}

	/**
	 * Get all output that has been written.
	 *
	 * @return string
	 */
	public function output(): string {
		return implode('', $this->messages);
	}

	/**
	 * Get all messages as array.
	 *
	 * @return array<string>
	 */
	public function messages(): array {
		return $this->messages;
	}

	/**
	 * Clear the output buffer.
	 *
	 * @return void
	 */
	public function clear(): void {
		$this->messages = [];
	}

}
