# line_stats

Tells you how many lines of code you've added and removed from a given git repo.

The fun part is trying to be negative each week and negative all time (`line_stats -f`).

## --help
```
line_stats [-d <date>] [-u <user_name>] [-sf] [-r <repo_directory>]

Prints your line changes from the last week by default.

-s, --short             Use a shorter output format.
-d, --date ...          Start date, at day granularity. Any string you could pass to strtotime(). Defaults to 1 week ago.
-u, --user ...          The user who changes to view. Defaults to \`whoami'
-f, --forever           Look through all the commits of ALL TIME. Can't be combined with an explicit start date.
-r, --repo              The directory of the git repo to use.
-h, --help              Prints this usage message.
```

## Example output
```
Changes by jane since 2016-02-05:

Type   Added  Removed  Total
----   -----  -------  -----
all    2334   1513     821
conf   29     2        27
css	   14     0        14
html   75     143      -68
java   1617   1322     295
js     498    2        496
scala  36     17       19
sql    6      0        6
xml    36     0        36
other  23     27       -4

other: *.sbt, *.plugins
```
