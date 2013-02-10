#!/usr/bin/env php
<?php
// makelowercaserename.php



class MakeLowerCaseRename {

	const LE = "\n";

	private $sourceDir = '';
	private $writtenHeader = false;



	public function __construct(array $argv) {

		// validate source path
		if (!isset($argv[1])) {
			$this->writeLine('Please specify source directory',true);
			return;
		}

		$this->sourceDir = rtrim($argv[1],'/');
		if (!is_dir($this->sourceDir)) {
			$this->writeLine('Source directory \'' . $this->sourceDir . '\' not found or invalid',true);
			return;
		}

		$this->workDir();
	}

	private function workDir($childDir = '') {

		$dirHandle = @opendir($this->sourceDir . $childDir);
		if ($dirHandle === false) return;

		while (($fileItem = readdir($dirHandle)) !== false) {
			// skip current/parent directories
			if (($fileItem == '.') || ($fileItem == '..')) continue;

			$fileItemPath = $this->sourceDir . $childDir . '/' . $fileItem;

			if (is_dir($fileItemPath)) {
				// file is a directory, call $this->workDir() recursively
				$this->workDir($childDir . '/' . $fileItem);
				continue;
			}

			// output rename bash command
			$this->emitRenameCommand($fileItemPath);
		}

		// close directory handle
		closedir($dirHandle);
		return;
	}

	private function emitRenameCommand($fileItemPath) {

		// extract filename component and check if can be lowercased, otherwise exit
		$filename = basename($fileItemPath);
		$dirname = dirname($fileItemPath);
		$filenameLower = strtolower($filename);
		if ($filename == $filenameLower) return;

		// has bash header been written?
		if (!$this->writtenHeader) {
			$this->writeLine('#!/bin/bash');
			$this->writeLine('SOURCEDIR="' . $this->escapeFilePath($this->sourceDir) . '"');
			$this->writtenHeader = true;
		}

		$underscores = "_";
		while(file_exists($fileItemPath . $underscores))
			$underscores .= "_";

		// build move command
		$sourceDirLen = strlen($this->sourceDir);
		$this->writeLine(sprintf(
			'mv "$SOURCEDIR/%s" "$SOURCEDIR/%s"',
			$this->escapeFilePath(substr($fileItemPath,$sourceDirLen)),
			$this->escapeFilePath(substr($fileItemPath,$sourceDirLen).$underscores)
		));
		$this->writeLine(sprintf(
			'mv "$SOURCEDIR/%s" "$SOURCEDIR/%s/%s"',
			$this->escapeFilePath(substr($fileItemPath,$sourceDirLen).$underscores),
			$this->escapeFilePath(substr(dirname($fileItemPath),$sourceDirLen)),
			$this->escapeFilePath($filenameLower)
		));
	}

	private function escapeFilePath($path) {

		return str_replace('"','\"',$path);
	}

	private function writeLine($text = '',$isError = false) {

		echo((($isError) ? 'Error: ' : '') . $text . self::LE);
	}
}


new MakeLowerCaseRename($argv);
