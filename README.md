# Make lower case rename
A quick, dirty and *hacky* PHP script (with a terrible project name).

Given a source directory, spits out to the console a bash script to recursively rename upper case filenames to lower cased versions - basically a bunch of `mv sourcefile sourcefilelowercase` commands.

Something I needed to lower case around 30,000 digital camera images on a NAS drive which has a rather limited toolchain (BusyBox).

I'm sure I could have still done this directly on the NAS using bash, but it was easier/quicker to:
- Cobble this together
- Run script over NFS share to the image files
- SCP over to NAS the output as a bash script
- Run that bash script directly on the NAS from BusyBox shell

You can probably gather, this will be little to *zero* use for anyone else.

## Usage
`./makelowercaserename.php [directory-to-files] > outbash.sh`
