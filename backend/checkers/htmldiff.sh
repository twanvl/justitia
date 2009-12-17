#!/bin/sh

# Checker script

# Usage: $0 <testout> <refout> <diff> <flags>
#
# <testout>   File containing submission output.
# <refout>    File containing reference output.
# <diff>      File where to write the diff.

perl $JUSTITIA_BACKEND_DIR/bin/htmldiff.pl $4 "reference output:$2" "your output:$1" >$3
exit $?
