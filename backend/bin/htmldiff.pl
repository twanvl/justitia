#!/usr/bin/perl

#
# works like diff, but gives html output
#
# working options:
#
# -b ignore changes in number of whitespace
# -w totally ignore whitespace
# -i ignore case differences

#use Algorithm::Diff qw/sdiff diff/;
use Getopt::Long qw/GetOptions/;
#use HTML::Entities (encode_entities);
my %entities = ('&'=>'&amp;', '<'=>'&lt;', '>'=>'&gt;', '"'=>'&quot;', "'"=>'&apos;');
use strict;

Getopt::Long::Configure ('bundling');
Getopt::Long::Configure ('require_order');

my$ignore_whitespace_change=0;
my$ignore_whitespace=0;
my$ignore_case=0;

GetOptions ('b'=>\$ignore_whitespace_change,
	    'w'=>\$ignore_whitespace,
	    'i'=>\$ignore_case);

my $name_a = '';#$ENV{'FILE1'};
my $file_a = $ARGV[$#ARGV-1];
my $name_b = '';#$ENV{'FILE2'};
my $file_b = $ARGV[$#ARGV];

open(FILEA, $file_a) or die "File not found: $file_a";
my@a=<FILEA>;
close(FILEA);

open(FILEB, $file_b) or die "File not found: $file_b";
my@b=<FILEB>;
close(FILEB);

my@a2=@a;
my@b2=@b;

my @diffs = diff(\@a2,\@b2,\&hash);
exit 0 if$#diffs<=0;


printheader();
print "<tr><td class=\"filename left\">".encode_entities($name_a)."</td>".
    "<td class=\"filename center\"> </td>".
    "<td class=\"filename right\">".encode_entities($name_b)."</td></tr>\n";
my@sdiffs = sdiff( \@a, \@b,\&hash);
for(my$x=0;$x<=$#sdiffs;$x++){
    my ($ch,$l,$r) = @{$sdiffs[$x]};
    if    ($ch eq 'u'){printrow(encode_entities($l),'normal',encode_entities($r),'normal',"|","isame");}
    elsif ($ch eq '+'){printrow(encode_entities($l),'empty',encode_entities($r),'added',">","iadd");}
    elsif ($ch eq '-'){printrow(encode_entities($l),'removed',encode_entities($r),'empty',"<","iminus");}
    elsif ($ch eq 'c'){printchangedrow($l,$r);}
}
printfooter();
exit 1;





sub sdif {
    my ($l,$r,$split)=@_;
    my@ll=split$split,$l;
    my@rr=split$split,$r;
    return sdiff( \@ll, \@rr ,\&hash);
}

sub printrow {
    my ($l,$lc,$r,$rc,$ch,$ic) = @_;
    print "<tr><td class=\"left $lc\"><pre>$l</pre></td>".
	"<td class=\"center idiff\"><span class=\"$ic\">$ch</span></td>".
	"<td class=\"right $rc\"><pre>$r</pre></td></tr>\n";
}

sub printchangedrow {
    my($l,$r) = @_;
    my@rdiff = sdif( $l, $r, '(?!\s)|(?<!\s)' );
    my@rdif = ();
    if ($#rdiff>=0){
	my($l_ch,$l_l,$l_r)=@{$rdiff[0]};
	for my$x(1..$#rdiff){
	    my ($r_ch,$r_l,$r_r) = @{$rdiff[$x]};
	    if ($r_ch eq $l_ch){
		$l_l.=$r_l;$l_r.=$r_r;
	    }else{
		$rdif[$#rdif+1]=[$l_ch,$l_l,$l_r];
		($l_ch,$l_l,$l_r)=($r_ch,$r_l,$r_r);
	    }
	}
	$rdif[$#rdif+1]=[$l_ch,$l_l,$l_r];
    }
    my ($left,$right);
    for my$rd(@rdif){
	my ($r_ch,$r_l,$r_r) = @$rd;
	if    ($r_ch eq 'u'){$left .=encode_entities($r_l);
                             $right.=encode_entities($r_r);}
	elsif ($r_ch eq '+'){$right.=spanword(encode_entities($r_r),"addedw");}
	elsif ($r_ch eq '-'){$left .=spanword(encode_entities($r_l),"removedw");}
	elsif ($r_ch eq 'c'){$left .=spanword(encode_entities($r_l),"changedw");
			     $right.=spanword(encode_entities($r_r),"changedw");}
    }
    printrow($left,'changed',$right,'changed',"~","ichanged");
}

sub spanword {
    my ($ch,$class) = @_;
    return "<span class=\"$class\">$ch</span>";
}


sub printheader{
    print<<____END_DINGES;
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
    <html>
    <head>
        <title>Differences</title>
	<style type="text/css">
	* {padding:0;margin:0;}
	.empty{
	    background-color:#ccc;
	}
	.added{
	    background-color:#9f9;
	}
	.removed{
	    background-color:#f99;
	}
	.changed{
	    background-color:#ff9;
	}

        td, td *, td * *, td * * *{
	    white-space:pre;
        }

	.addedw{
	    background-color:#9f9;
	}
	.removedw{
	    background-color:#f99;
	}
	.changedw{
	    background-color:#99f;
	}
	.filename{
	    background-color:#009;
	    color:white;
	    text-align:center;
	}
        table,tbody,tr,td {
	    margin:0;
	    padding:0;
            border:0;
	    border-spacing:0;
        }
        table {
	    width:100%;
        }
        td.left, td.right{
	    width:50%;
	      padding-left:0.5em;
	      padding-right:0.5em;
        }
        td.center {
	    max-width:11px;
	    width:11px;
            text-align:center;
            vertical-align:middle;
        }
        td{
            font-family:monospace;
        }
        td.diff{
	  background: url(booooring.gif) center center repeat-y;
        }
        .isame{
	  visibility:none;
        }
	</style>
    </head>
    <body>
    <table>
____END_DINGES
    ;
}

sub printfooter{
    print<<____END_DINGES;
    </table>
    <br><br><br>
    <hr width="100%">
    <center>
    <h2>Legend</h2>
    <table style="width:33%;">
____END_DINGES
    ;
    print "<tr><td class=\"filename left\">FILE A</td>".
	"<td class=\"filename center\"> </td>".
	"<td class=\"filename right\">FILE B</td></tr>\n";

    printrow("","empty","Excess line","added",">","iadded");
    printrow("Missing line","removed","","empty",">","iremoved");
    printrow("Excess letter:","changed",
	     "Excess letter: <span class=\"addedw\">a</span>","changed",
	     ,"~","ichanged");
    printrow("Missing letter: <span class=\"removedw\">a</span>","changed",
	     "Missing letter: ","changed",
	     ,"~","ichanged");
    printrow("Changed letter: <span class=\"changedw\">a</span>","changed",
	     "Changed letter: <span class=\"changedw\">b</span>","changed",
	     ,"~","ichanged");
    print<<____END_DINGES;
    </table></center>

    </body>
    </html>
____END_DINGES
    ;
}


sub hash{
    ($_)=@_;
    s/[ \t]+/ /smg if($ignore_whitespace_change);
    s/[ \t]+//smg  if($ignore_whitespace);
    y/A-Z/a-z/     if($ignore_case);
    return$_;
}

sub encode_entities {
    ($_)=@_;
    s/([&<>"'])/$entities{$1}/ge;
    return $_;
}

#------------------------------------------------------- : Algorithm::Diff


# Create a hash that maps each element of $aCollection to the set of positions
# it occupies in $aCollection, restricted to the elements within the range of
# indexes specified by $start and $end.
# The fourth parameter is a subroutine reference that will be called to
# generate a string to use as a key.
# Additional parameters, if any, will be passed to this subroutine.
#
# my $hashRef = _withPositionsOfInInterval( \@array, $start, $end, $keyGen );

sub _withPositionsOfInInterval
{
	my $aCollection = shift;    # array ref
	my $start       = shift;
	my $end         = shift;
	my $keyGen      = shift;
	my %d;
	my $index;
	for ( $index = $start ; $index <= $end ; $index++ )
	{
		my $element = $aCollection->[$index];
		my $key = &$keyGen( $element, @_ );
		if ( exists( $d{$key} ) )
		{
			unshift ( @{ $d{$key} }, $index );
		}
		else
		{
			$d{$key} = [$index];
		}
	}
	return wantarray ? %d : \%d;
}

# Find the place at which aValue would normally be inserted into the array. If
# that place is already occupied by aValue, do nothing, and return undef. If
# the place does not exist (i.e., it is off the end of the array), add it to
# the end, otherwise replace the element at that point with aValue.
# It is assumed that the array's values are numeric.
# This is where the bulk (75%) of the time is spent in this module, so try to
# make it fast!

sub _replaceNextLargerWith
{
	my ( $array, $aValue, $high ) = @_;
	$high ||= $#$array;

	# off the end?
	if ( $high == -1 || $aValue > $array->[-1] )
	{
		push ( @$array, $aValue );
		return $high + 1;
	}

	# binary search for insertion point...
	my $low = 0;
	my $index;
	my $found;
	while ( $low <= $high )
	{
		$index = ( $high + $low ) / 2;

		#		$index = int(( $high + $low ) / 2);		# without 'use integer'
		$found = $array->[$index];

		if ( $aValue == $found )
		{
			return undef;
		}
		elsif ( $aValue > $found )
		{
			$low = $index + 1;
		}
		else
		{
			$high = $index - 1;
		}
	}

	# now insertion point is in $low.
	$array->[$low] = $aValue;    # overwrite next larger
	return $low;
}

# This method computes the longest common subsequence in $a and $b.

# Result is array or ref, whose contents is such that
# 	$a->[ $i ] == $b->[ $result[ $i ] ]
# foreach $i in ( 0 .. $#result ) if $result[ $i ] is defined.

# An additional argument may be passed; this is a hash or key generating
# function that should return a string that uniquely identifies the given
# element.  It should be the case that if the key is the same, the elements
# will compare the same. If this parameter is undef or missing, the key
# will be the element as a string.

# By default, comparisons will use "eq" and elements will be turned into keys
# using the default stringizing operator '""'.

# Additional parameters, if any, will be passed to the key generation routine.

sub _longestCommonSubsequence
{
	my $a      = shift;    # array ref
	my $b      = shift;    # array ref
	my $keyGen = shift;    # code ref
	my $compare;           # code ref

	# set up code refs
	# Note that these are optimized.
	if ( !defined($keyGen) )    # optimize for strings
	{
		$keyGen = sub { $_[0] };
		$compare = sub { my ( $a, $b ) = @_; $a eq $b };
	}
	else
	{
		$compare = sub {
			my $a = shift;
			my $b = shift;
			&$keyGen( $a, @_ ) eq &$keyGen( $b, @_ );
		};
	}

	my ( $aStart, $aFinish, $bStart, $bFinish, $matchVector ) =
	  ( 0, $#$a, 0, $#$b, [] );

	# First we prune off any common elements at the beginning
	while ( $aStart <= $aFinish
		and $bStart <= $bFinish
		and &$compare( $a->[$aStart], $b->[$bStart], @_ ) )
	{
		$matchVector->[ $aStart++ ] = $bStart++;
	}

	# now the end
	while ( $aStart <= $aFinish
		and $bStart <= $bFinish
		and &$compare( $a->[$aFinish], $b->[$bFinish], @_ ) )
	{
		$matchVector->[ $aFinish-- ] = $bFinish--;
	}

	# Now compute the equivalence classes of positions of elements
	my $bMatches =
	  _withPositionsOfInInterval( $b, $bStart, $bFinish, $keyGen, @_ );
	my $thresh = [];
	my $links  = [];

	my ( $i, $ai, $j, $k );
	for ( $i = $aStart ; $i <= $aFinish ; $i++ )
	{
		$ai = &$keyGen( $a->[$i], @_ );
		if ( exists( $bMatches->{$ai} ) )
		{
			$k = 0;
			for $j ( @{ $bMatches->{$ai} } )
			{

				# optimization: most of the time this will be true
				if ( $k and $thresh->[$k] > $j and $thresh->[ $k - 1 ] < $j )
				{
					$thresh->[$k] = $j;
				}
				else
				{
					$k = _replaceNextLargerWith( $thresh, $j, $k );
				}

				# oddly, it's faster to always test this (CPU cache?).
				if ( defined($k) )
				{
					$links->[$k] =
					  [ ( $k ? $links->[ $k - 1 ] : undef ), $i, $j ];
				}
			}
		}
	}

	if (@$thresh)
	{
		for ( my $link = $links->[$#$thresh] ; $link ; $link = $link->[0] )
		{
			$matchVector->[ $link->[1] ] = $link->[2];
		}
	}

	return wantarray ? @$matchVector : $matchVector;
}

sub traverse_sequences
{
	my $a                 = shift;                                  # array ref
	my $b                 = shift;                                  # array ref
	my $callbacks         = shift || {};
	my $keyGen            = shift;
	my $matchCallback     = $callbacks->{'MATCH'} || sub { };
	my $discardACallback  = $callbacks->{'DISCARD_A'} || sub { };
	my $finishedACallback = $callbacks->{'A_FINISHED'};
	my $discardBCallback  = $callbacks->{'DISCARD_B'} || sub { };
	my $finishedBCallback = $callbacks->{'B_FINISHED'};
	my $matchVector = _longestCommonSubsequence( $a, $b, $keyGen, @_ );

	# Process all the lines in @$matchVector
	my $lastA = $#$a;
	my $lastB = $#$b;
	my $bi    = 0;
	my $ai;

	for ( $ai = 0 ; $ai <= $#$matchVector ; $ai++ )
	{
		my $bLine = $matchVector->[$ai];
		if ( defined($bLine) )    # matched
		{
			&$discardBCallback( $ai, $bi++, @_ ) while $bi < $bLine;
			&$matchCallback( $ai,    $bi++, @_ );
		}
		else
		{
			&$discardACallback( $ai, $bi, @_ );
		}
	}

	# The last entry (if any) processed was a match.
	# $ai and $bi point just past the last matching lines in their sequences.

	while ( $ai <= $lastA or $bi <= $lastB )
	{

		# last A?
		if ( $ai == $lastA + 1 and $bi <= $lastB )
		{
			if ( defined($finishedACallback) )
			{
				&$finishedACallback( $lastA, @_ );
				$finishedACallback = undef;
			}
			else
			{
				&$discardBCallback( $ai, $bi++, @_ ) while $bi <= $lastB;
			}
		}

		# last B?
		if ( $bi == $lastB + 1 and $ai <= $lastA )
		{
			if ( defined($finishedBCallback) )
			{
				&$finishedBCallback( $lastB, @_ );
				$finishedBCallback = undef;
			}
			else
			{
				&$discardACallback( $ai++, $bi, @_ ) while $ai <= $lastA;
			}
		}

		&$discardACallback( $ai++, $bi, @_ ) if $ai <= $lastA;
		&$discardBCallback( $ai, $bi++, @_ ) if $bi <= $lastB;
	}

	return 1;
}

sub traverse_balanced
{
	my $a                 = shift;                                  # array ref
	my $b                 = shift;                                  # array ref
	my $callbacks         = shift || {};
	my $keyGen            = shift;
	my $matchCallback     = $callbacks->{'MATCH'} || sub { };
	my $discardACallback  = $callbacks->{'DISCARD_A'} || sub { };
	my $discardBCallback  = $callbacks->{'DISCARD_B'} || sub { };
	my $changeCallback    = $callbacks->{'CHANGE'};
	my $matchVector = _longestCommonSubsequence( $a, $b, $keyGen, @_ );

	# Process all the lines in match vector
	my $lastA = $#$a;
	my $lastB = $#$b;
	my $bi    = 0;
	my $ai    = 0;
	my $ma    = -1;
	my $mb;

	while (1)
	{

		# Find next match indices $ma and $mb
		do { $ma++ } while ( $ma <= $#$matchVector && !defined $matchVector->[$ma] );

		last if $ma > $#$matchVector;    # end of matchVector?
		$mb = $matchVector->[$ma];

		# Proceed with discard a/b or change events until
		# next match
		while ( $ai < $ma || $bi < $mb )
		{

			if ( $ai < $ma && $bi < $mb )
			{

				# Change
				if ( defined $changeCallback )
				{
					&$changeCallback( $ai++, $bi++, @_ );
				}
				else
				{
					&$discardACallback( $ai++, $bi, @_ );
					&$discardBCallback( $ai, $bi++, @_ );
				}
			}
			elsif ( $ai < $ma )
			{
				&$discardACallback( $ai++, $bi, @_ );
			}
			else
			{

				# $bi < $mb
				&$discardBCallback( $ai, $bi++, @_ );
			}
		}

		# Match
		&$matchCallback( $ai++, $bi++, @_ );
	}

	while ( $ai <= $lastA || $bi <= $lastB )
	{
		if ( $ai <= $lastA && $bi <= $lastB )
		{

			# Change
			if ( defined $changeCallback )
			{
				&$changeCallback( $ai++, $bi++, @_ );
			}
			else
			{
				&$discardACallback( $ai++, $bi, @_ );
				&$discardBCallback( $ai, $bi++, @_ );
			}
		}
		elsif ( $ai <= $lastA )
		{
			&$discardACallback( $ai++, $bi, @_ );
		}
		else
		{

			# $bi <= $lastB
			&$discardBCallback( $ai, $bi++, @_ );
		}
	}

	return 1;
}

sub LCS
{
	my $a = shift;                                           # array ref
	my $matchVector = _longestCommonSubsequence( $a, @_ );
	my @retval;
	my $i;
	for ( $i = 0 ; $i <= $#$matchVector ; $i++ )
	{
		if ( defined( $matchVector->[$i] ) )
		{
			push ( @retval, $a->[$i] );
		}
	}
	return wantarray ? @retval : \@retval;
}

sub diff
{
	my $a      = shift;    # array ref
	my $b      = shift;    # array ref
	my $retval = [];
	my $hunk   = [];
	my $discard = sub { push ( @$hunk, [ '-', $_[0], $a->[ $_[0] ] ] ) };
	my $add = sub { push ( @$hunk, [ '+', $_[1], $b->[ $_[1] ] ] ) };
	my $match = sub { push ( @$retval, $hunk ) if scalar(@$hunk); $hunk = [] };
	traverse_sequences( $a, $b,
		{ MATCH => $match, DISCARD_A => $discard, DISCARD_B => $add }, @_ );
	&$match();
	return wantarray ? @$retval : $retval;
}

sub sdiff
{
	my $a      = shift;    # array ref
	my $b      = shift;    # array ref
	my $retval = [];
	my $discard = sub { push ( @$retval, [ '-', $a->[ $_[0] ], "" ] ) };
	my $add = sub { push ( @$retval, [ '+', "", $b->[ $_[1] ] ] ) };
	my $change = sub {
		push ( @$retval, [ 'c', $a->[ $_[0] ], $b->[ $_[1] ] ] );
	};
	my $match = sub {
		push ( @$retval, [ 'u', $a->[ $_[0] ], $b->[ $_[1] ] ] );
	};
	traverse_balanced(
		$a,
		$b,
		{
			MATCH     => $match,
			DISCARD_A => $discard,
			DISCARD_B => $add,
			CHANGE    => $change,
		},
		@_
	);
	return wantarray ? @$retval : $retval;
}
