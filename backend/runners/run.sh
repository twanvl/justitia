#!/bin/sh

# Run wrapper-script

# Usage: $0 <program> <testin> <output> <error> <flags>
#
# <program>   Executable of the program to be run.
# <testin>    File containing test-input.
# <output>    File where to write solution output.
# <error>     File where to write error messages.
# <flags>     More flags

PROGRAM="$1";   shift
TESTIN="$1";    shift
OUTPUT="$1";    shift
ERROR="$1";     shift

# Run the program while redirecting input, output and stderr
$PROGRAM <$TESTIN >$OUTPUT 2>$ERROR
exit $?
