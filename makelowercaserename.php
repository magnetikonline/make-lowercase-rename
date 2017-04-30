#!/usr/bin/env php
<?php
class MakeLowerCaseRename {

	const MV_CMD_SPRINTF = '%s "$SOURCEDIR%s" "$SOURCEDIR%s/%s"';
	const MV_CMD_DEFAULT = 'mv';

	private $sourceDir = '';
	private $writtenBashHeader = false;
	private $mvCommand = self::MV_CMD_DEFAULT;
	private $mvTemp = false;


	public function execute(array $argv) {

		// validate source path
		if (!isset($argv[1])) {
			$this->writeLine('Please specify source directory',true);
			return;
		}

		$this->sourceDir = rtrim($argv[1],'/');
		if (!is_dir($this->sourceDir)) {
			// can't find source directory
			$this->writeLine('Source directory \'' . $this->sourceDir . '\' not found or invalid',true);
			return;
		}

		if (isset($argv[2])) {
			if ($argv[2] != '--move-temp') {
				$this->writeLine('Second optional parameter can only be \'--move-temp\'',true);
				return;
			}

			// enable move to a temp file first then back to target
			$this->mvTemp = true;
		}

		// set custom mv command (if given in env) and work source directory
		$this->mvCommand = trim((getenv('MVCMD')) ?: $this->mvCommand);
		$this->workDir();
	}

	private function workDir($childDir = '') {

		$dirHandle = @opendir($this->sourceDir . $childDir);
		if ($dirHandle === false) return;

		while (($fileItem = readdir($dirHandle)) !== false) {
			// skip current/parent directories
			if (
				($fileItem == '.') ||
				($fileItem == '..')
			) continue;

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
	}

	private function emitRenameCommand($fileItemPath) {

		// extract filename component and check if can be lowercased, otherwise exit
		$filename = basename($fileItemPath);

		$filenameLower = strtolower($filename);
		if ($filename == $filenameLower) {
			// no work
			return;
		}

		// has bash header been written?
		if (!$this->writtenBashHeader) {
			$this->writeLine('#!/bin/bash -e');
			$this->writeLine();
			$this->writeLine('SOURCEDIR="' . $this->escapeFilePath($this->sourceDir) . '"');
			$this->writeLine();
			$this->writeLine();

			$this->writtenBashHeader = true;
		}

		// build move command
		$sourceDirLen = strlen($this->sourceDir);
		$sourcePathTail = $this->escapeFilePath(substr($fileItemPath,$sourceDirLen));
		$targetDirTail = $this->escapeFilePath(substr(dirname($fileItemPath),$sourceDirLen));
		$uniqTarget = '';

		if ($this->mvTemp) {
			// move source file to an temp target first before rename - fixes broken filesystems (e.g. FAT32)
			while (file_exists($fileItemPath . $uniqTarget)) $uniqTarget .= '_';

			$this->writeLine(sprintf(
				self::MV_CMD_SPRINTF,
				$this->mvCommand,
				$sourcePathTail,
				$targetDirTail,
				$this->escapeFilePath($filename) . $uniqTarget
			));
		}

		$this->writeLine(sprintf(
			self::MV_CMD_SPRINTF,
			$this->mvCommand,
			$sourcePathTail . $uniqTarget,
			$targetDirTail,
			$this->escapeFilePath($filenameLower)
		));
	}

	private function escapeFilePath($path) {

		return str_replace('"','\"',$path);
	}

	private function writeLine($text = '',$isError = false) {

		echo((($isError) ? 'Error: ' : '') . $text . "\n");
	}
}


(new MakeLowerCaseRename())->execute($argv);
