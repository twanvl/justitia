#!/bin/sh

# Checker script

# Usage: $0 <testout> <refout> <diff> <flags>
#
# <testout>   File containing submission output.
# <refout>    File containing reference output.
# <diff>      File where to write the diff.
# <flags>     Extra flags.

MYDIR=`dirname "$0"`
if [ "$TERM" = cygwin ]; then
	# Fix to handle paths with backslashes
	MYDIR=`echo "$0" | sed 's/\\\\/\//g' | xargs dirname`
fi

export FILE1="your output"
export FILE2="expected output"
perl $MYDIR/../bin/htmldiff.pl $4 $1 $2 >$3
exit $?
