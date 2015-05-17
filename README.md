# Make lowercase rename
A quick, dirty and *hacky* PHP script (with a terrible project name).

Given a source directory, spits out to the console a bash script to recursively rename uppercase filenames to lowercased versions - basically a bunch of `mv "sourcefile" "sourcefilelowercase"` commands. It **will not** modify directories, only the files within.

Something I needed to lowercase around 30,000 digital camera images on a NAS drive which has a rather limited toolchain (BusyBox).

I'm sure I could have done this directly on the NAS using bash, but it proved easier/quicker to:
- Cobble this together
- Run script over NFS share to the files in question
- SCP result over to NAS as bash script
- Run bash script directly on the NAS from it's BusyBox shell

As you can probably gather, this will be little to *zero* use for anyone else.

Tested under PHP 5.4.10.

## Usage
**Note:** The second optional parameter `movetemp` will first move the source file to a temp filename, then back to it's final lowercased filename. This defeats issues with broken filesystems, such as FAT32.

	$ ./makelowercaserename.php \
		/directory/to/files [movetemp] > outbash.sh

Or use an alternative command for `mv`, e.g.

	$ MVCMD="git mv" \
		./makelowercaserename.php \
		/directory/to/files [movetemp] > outbash.sh
