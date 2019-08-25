#!/usr/bin/env php
<?php
class MakeLowerCaseRename {
	const MV_CMD_DEFAULT = 'mv';

	private $sourceDir = '';
	private $writtenBashHeader = false;
	private $mvCommand = self::MV_CMD_DEFAULT;
	private $mvTemp = false;


	public function execute(array $argv) {
		// validate source path
		if (!isset($argv[1])) {
			$this->exitError('Please specify source directory');
		}

		$this->sourceDir = rtrim($argv[1],'/');
		if (!is_dir($this->sourceDir)) {
			// can't find source directory
			$this->exitError(sprintf('Source directory [%s] not found or invalid',$this->sourceDir));
		}

		if (isset($argv[2])) {
			if ($argv[2] != '--move-temp') {
				$this->exitError('Second optional parameter can only be \'--move-temp\'');
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
		if ($dirHandle === false) {
			return;
		}

		while (($fileItem = readdir($dirHandle)) !== false) {
			// skip current/parent directories
			if (
				($fileItem == '.') ||
				($fileItem == '..')
			) continue;

			$fileItemPath = sprintf('%s%s/%s',$this->sourceDir,$childDir,$fileItem);

			if (is_dir($fileItemPath)) {
				// file is a directory, call $this->workDir() recursively
				$this->workDir(sprintf('%s/%s',$childDir,$fileItem));
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

		// create bash header
		if (!$this->writtenBashHeader) {
			$this->writeLine('#!/bin/bash -e');
			$this->writeLine();
			$this->writeLine('SOURCE_DIR="' . $this->escapeFilePath($this->sourceDir) . '"');
			$this->writeLine();

			$this->writeLine(
<<<EOT
function lowerIt {
	local sourceFile="\$SOURCE_DIR\$1"
	local targetFile="\$SOURCE_DIR\$2"
	if [[ -f \$targetFile ]]; then
		echo "Notice: Target [\$targetFile] already exists - skipping"
		return
	fi

	if [[ -n \$3 ]]; then
		mv "\$sourceFile" "\$sourceFile\$3"
		sourceFile+=\$3
	fi

	$this->mvCommand "\$sourceFile" "\$targetFile"
}

EOT
			);

			$this->writtenBashHeader = true;
		}

		// build move command
		$uniqTarget = '';
		if ($this->mvTemp) {
			// move source file to an temp target first before rename - defeats broken filesystems (e.g. FAT32)
			while (file_exists($fileItemPath . $uniqTarget)) {
				$uniqTarget .= '_';
			}
		}

		$sourcePathTail = $this->escapeFilePath(substr(
			$fileItemPath,
			strlen($this->sourceDir)
		));

		$sourcePathTailDir = dirname($sourcePathTail);

		$this->writeLine(sprintf(
			'lowerIt "%s" "%s%s" %s',
			$sourcePathTail,
			($sourcePathTailDir != '/')
				? $sourcePathTailDir .= '/'
				: $sourcePathTailDir,
			$this->escapeFilePath($filenameLower),
			($uniqTarget != '')
				? sprintf('"%s"',$uniqTarget)
				: ''
		));

	}

	private function escapeFilePath($path) {
		return str_replace('"','\"',$path);
	}

	private function writeLine($text = '') {
		echo($text . "\n");
	}

	private function exitError($message) {
		fwrite(STDERR,sprintf("Error: %s\n",$message));
		exit(1);
	}
}


(new MakeLowerCaseRename())->execute($argv);
