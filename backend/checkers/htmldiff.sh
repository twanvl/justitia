#!/bin/sh

# Checker script

# Usage: $0 <testout> <refout> <diff> <flags>
#
# <testout>   File containing submission output.
# <refout>    File containing reference output.
# <diff>      File where to write the diff.
# <flags>     Extra flags.

export FILE1="your output"
export FILE2="expected output"
perl `dirname $0`/../bin/htmldiff.pl $4 $1 $2 >$3
exit $?
