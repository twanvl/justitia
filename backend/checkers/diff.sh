#!/bin/sh

# Checker script

# Usage: $0 <testout> <refout> <diff>
#
# <testout>   File containing submission output.
# <refout>    File containing reference output.
# <diff>      File where to write the diff.

# Run the unix diff tool
diff -y $4 $1 $2 >$3
exit $?
