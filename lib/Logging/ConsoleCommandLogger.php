<?php

// SPDX-FileCopyrightText: 2025 Lennart Dohmann <lennart.dohmann@gdata.de>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\GDataVaas\Logging;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleCommandLogger implements LoggerInterface {
	private LoggerInterface $inner;

	private OutputInterface $consoleOutput;

	public function __construct(LoggerInterface $inner, OutputInterface $consoleOutput) {
		$this->inner = $inner;
		$this->consoleOutput = $consoleOutput;
	}

	/**
	 * @param $message
	 * @param array $context
	 * @return void
	 */
	public function emergency($message, array $context = []): void {
		$this->consoleOutput->writeln("<error>[emergency] $message</error>");

		$this->inner->emergency($message, $context);
	}

	/**
	 * @param $message
	 * @param array $context
	 * @return void
	 */
	public function alert($message, array $context = []): void {
		$this->consoleOutput->writeln("<error>[alert] $message</error>");

		$this->inner->alert($message, $context);
	}

	/**
	 * @param $message
	 * @param array $context
	 * @return void
	 */
	public function critical($message, array $context = []): void {
		$this->consoleOutput->writeln("<error>[critical] $message</error>");

		$this->inner->critical($message, $context);
	}

	/**
	 * @param $message
	 * @param array $context
	 * @return void
	 */
	public function error($message, array $context = []): void {
		$this->consoleOutput->writeln("<error>[error] $message</error>");

		$this->inner->error($message, $context);
	}

	/**
	 * @param $message
	 * @param array $context
	 * @return void
	 */
	public function warning($message, array $context = []): void {
		$this->consoleOutput->writeln("[warning] $message");

		$this->inner->warning($message, $context);
	}

	/**
	 * @param $message
	 * @param array $context
	 * @return void
	 */
	public function notice($message, array $context = []): void {
		$this->consoleOutput->writeln("<info>[notice] $message</info>");

		$this->inner->notice($message, $context);
	}

	/**
	 * @param $message
	 * @param array $context
	 * @return void
	 */
	public function info($message, array $context = []): void {
		$this->consoleOutput->writeln("<info>[info] $message</info>");

		$this->inner->info($message, $context);
	}

	/**
	 * @param $message
	 * @param array $context
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
	 * @param $level
	 * @param $message
	 * @param array $context
	 * @return void
	 */
	public function log($level, $message, array $context = []): void {
		$this->consoleOutput->writeln("<info>[log] $message</info>");

		$this->inner->log($level, $message, $context);
	}
}
