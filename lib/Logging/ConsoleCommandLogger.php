<?php

namespace OCA\GDataVaas\Logging;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleCommandLogger implements LoggerInterface {
	private $inner;

	private $consoleOutput;

	public function __construct(LoggerInterface $inner, OutputInterface $consoleOutput) {
		$this->inner = $inner;
		$this->consoleOutput = $consoleOutput;
	}

	/**
	 * @return void
	 */
	public function emergency($message, array $context = []): void {
		$this->consoleOutput->writeln("<error>[emergency] $message</error>");

		$this->inner->emergency($message, $context);
	}

	/**
	 * @return void
	 */
	public function alert($message, array $context = []): void {
		$this->consoleOutput->writeln("<error>[alert] $message</error>");

		$this->inner->alert($message, $context);
	}

	/**
	 * @return void
	 */
	public function critical($message, array $context = []): void {
		$this->consoleOutput->writeln("<error>[critical] $message</error>");

		$this->inner->critical($message, $context);
	}

	/**
	 * @return void
	 */
	public function error($message, array $context = []): void {
		$this->consoleOutput->writeln("<error>[error] $message</error>");

		$this->inner->error($message, $context);
	}

	/**
	 * @return void
	 */
	public function warning($message, array $context = []): void {
		$this->consoleOutput->writeln("[warning] $message");

		$this->inner->warning($message, $context);
	}
	
	/**
	 * @return void
	 */
	public function notice($message, array $context = []): void {
		$this->consoleOutput->writeln("<info>[notice] $message</info>");

		$this->inner->notice($message, $context);
	}

	/**
	 * @return void
	 */
	public function info(string $message, array $context = []): void {
		$this->consoleOutput->writeln("<info>[info] $message</info>");

		$this->inner->info($message, $context);
	}
	
	/**
	 * @return void
	 */
	public function debug($message, array $context = []): void {
		if ($this->consoleOutput->getVerbosity() < OutputInterface::VERBOSITY_DEBUG) {
			return;
		}

		$this->consoleOutput->writeln("[debug] $message");

		$this->inner->debug($message, $context);
	}

	/**
	 * @return void
	 * @throws \Psr\Log\InvalidArgumentException
	 */
	public function log($level, $message, array $context = []): void {
		$this->consoleOutput->writeln("<info>[log] $message</info>");

		$this->inner->log($level, $message, $context);
	}
}
