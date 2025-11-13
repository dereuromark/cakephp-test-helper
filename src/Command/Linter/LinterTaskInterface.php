<?php

declare(strict_types=1);

namespace TestHelper\Command\Linter;

use Cake\Console\ConsoleIo;

interface LinterTaskInterface {

	/**
     * Get the task name
     *
     * @return string
     */
	public function name(): string;

	/**
     * Get the task description
     *
     * @return string
     */
	public function description(): string;

	/**
     * Run the linter task
     *
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @param array<string, mixed> $options Options including paths, fix mode, etc.
     *
     * @return int Number of issues found
     */
	public function run(ConsoleIo $io, array $options = []): int;

	/**
     * Get default paths to check
     *
     * @return array<string>
     */
	public function defaultPaths(): array;

	/**
     * Whether this task supports auto-fix
     *
     * @return bool
     */
	public function supportsAutoFix(): bool;

}
