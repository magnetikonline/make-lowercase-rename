# Make lower case rename
A quick, dirty and *hacky* PHP script (with a terrible project name).

Given a source directory, spits out to the console a bash script to recursively rename upper case filenames to lower cased versions - basically a bunch of `mv "sourcefile" "sourcefilelowercase"` commands.

Something I needed to lower case around 30,000 digital camera images on a NAS drive which has a rather limited toolchain (BusyBox).

I'm sure I could have done this directly on the NAS using what was available, but was easier/quicker to:
- Cobble this together
- Run script over NFS share to the files in question
- SCP result over to NAS as bash script
- Run bash script directly on the NAS from it's BusyBox shell

As you can probably gather, this will be little to *zero* use for anyone else.

## Usage
`./makelowercaserename.php [directory-to-files] > outbash.sh`
