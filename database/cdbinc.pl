#!/usr/bin/perl -w

@files = <*>;
foreach $file (@files) {
open (FILE, "<$file") or die "Can't open $file: $!\n";
@lines = <FILE>;
close FILE;

#Open same file for writing, reusing STDOUT
open (STDOUT, ">$file") or die "Can't open $file: $!\n";

#Walk through lines
for ( @lines ) {
    s/require "db/require "\/home\/jcobban\/includes\/db/;
    print;
}

#Finish up
close STDOUT;

}

