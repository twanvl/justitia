#!/bin/sh

# Checker script

# Usage: $0 <testout> <refout> <diff>
#
# <testout>   File containing submission output.
# <refout>    File containing reference output.
# <diff>      File where to write the diff.

TESTOUT="$1";   shift
REFOUT="$1";    shift
DIFF="$1";      shift

# Run the unix diff tool
diff -y $TESTOUT $REFOUT >$DIFF
exit $?
