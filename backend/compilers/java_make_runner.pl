
# Print a run script for java, based on the verbose output of javac
# Usage: perl $0 SOURCE_FILENAME MEMLIMIT < verbose.log
#
# Looks for all classes output from the source file
# In case of ambiguities, prefers
#    * SOURCE_FILENAME
#    * non-abstract classes?
#    * classes containing a main method??

# The verbose output of javac looks something like this:
#	[parsing started two.java]
#	[parsing completed 63ms]
#	[search path for source files: [.]]
#	[search path for class files: [e:\Dev\Java\jre\lib\rt.jar, e:\Dev\Java\jre\lib\jsse.jar, e:\Dev\Java\jre\lib\jce.jar, e:\Dev\Java\jre\lib\charsets.jar, e:\Dev\Java\jre\lib\ext\dnsns.jar, e:\Dev\Java\jre\lib\ext\localedata.jar, e:\Dev\Java\jre\lib\ext\sunjce_provider.jar, e:\Dev\Java\jre\lib\ext\sunpkcs11.jar, .]]
#	[loading e:\Dev\Java\jre\lib\rt.jar(java/lang/Object.class)]
#	[checking One]
#	[wrote One.class]
#	[checking Two]
#	[wrote Two$Subclass.class]
#	[wrote Two.class]
#	[total 406ms]

use strict;

# Does a class have a main method?
sub has_main {
	my ($classname) = @_;
	return 0 if ! -f "$classname.class";
	my $disassm = `javap $classname`;
	return $disassm =~ /public[ ]static[ ]void[ ]main[(]java[.]lang[.]String\[\][)]/;
}

my @classes;
my @classes_with_main;

while(<STDIN>) {
	# This regex matches  "[wrote Something.class]"
	# Note: \w doesn't match "$", which Java uses for inner classes
	#
	if (/^\[wrote (\w*)\.class\]/) {
		push @classes, $1;
		if (has_main($1)) {
			push @classes_with_main, $1;
		}
	}
}

# Did we find any classes?
if (@classes_with_main) {
	@classes = @classes_with_main;
}
if ($#classes < 0) {
	die "No class found in the source file.\n";
}
my $source_name = $ARGV[0];
if ($#classes > 0) {
	# are there classnames matching the source name?
	my $basename = $source_name;
	$basename =~ s/^.*\///;
	$basename =~ s/[.].*$//;
	my @match = grep(/^$basename$/i, @classes);
	if ($#match >= 0) {
		@classes = @match;
	}
}

if ($#classes > 0) {
	my $classnames_all = join ', ', @classes;
	print stderr "Multiple classes found in the java file, which one contains the main function?\n";
	print stderr "Found: $classnames_all\n\n";
	print stderr "Tip: if a class has the same name as the source file, that one will be used.\n";
	exit 1;
}


# Settled on a single name
my $classname = $classes[0];
if (! -f "$classname.class") {
	die "Class file '$classname.class' not found.\n";
}

# Calculate Java program memlimit as MEMLIMIT - max. JVM memory usage:
my $memlimit = $ARGV[$#ARGV];
my $memlimit_java = $memlimit;

# Write executing script:
# Executes java byte-code interpreter with following options
# -Xmx: maximum size of memory allocation pool
# -Xrs: reduces usage signals by java, because that generates debug
#       output when program is terminated on timelimit exceeded.
print <<EOF;
#!/bin/sh
# Generated shell-script to execute java interpreter on source.

exec java -Xrs -Xmx${memlimit_java}k $classname
EOF

exit 0;
