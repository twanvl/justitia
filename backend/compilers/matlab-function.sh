#!/bin/sh

# Matlab (octave) compiler wrapper
# This just generates a script that runs the interpreter

SOURCE="$1"
DEST="$2"
ERROR="$3"

if [ "$TERM" = cygwin ]; then
	# We are on windows, where shell scripts must be named .sh
	echo "(Using windows shell script workaround)"
	rm $DEST
	DEST="$DEST.sh"
fi

# write script
echo "octave --silent --norc" > $DEST
chmod a+x $DEST
