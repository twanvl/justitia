#!/bin/sh

# Checker script

# Usage: $0 <testout> <refout> <diff> <flags>
#
# <testout>   File containing submission output.
# <refout>    File containing reference output.
# <diff>      File where to write the diff.

export FILE1="expected output"
export FILE2="your output"
perl $JUSTITIA_BACKEND_DIR/bin/htmldiff.pl $4 $2 $1 >$3
exit $?
