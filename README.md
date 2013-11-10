## Using Sparkle Profile Reporting on your Web Server
(adapted from notes by Tom Harrington)

This distribution includes sample PHP code which can be used on your web server to collect the data and save it to a database.  There's also PHP scripts to display collected information and charts as web pages.

Why PHP?  Because I know PHP.  If you prefer some other scripting language, you'll have to write your own server code.

### Server Requirements

To effectively use Sparkle with profiling, your server must support a database engine and a server-side scripting language which can access that database.  The included demo code requires:

* PHP 5.3 w/mysqlnd
* MySQL 4.x or 5.x

Since the Sparkle profile code uses standard HTTP variables, it should be straightforward to write comparable code in Python, Ruby, Perl, or whatever other language you might prefer to use.

### Setting up the demo Sparkle profile code on your server

First, create a database that will contain the profile reports.  How to do this depends on your web host.  Once the database is created, load the schema from profileInfo.sql.  Using MySQL's command-line client, this would be something like:

    mysql -u username -h host -p database-name < profileInfo.sql

Your web host may make some alternative method available to you.

Now edit profileDB.php.  At the top of the file are several PHP variables that must be configured. (It is assumed that you are using the same database for each of your applications.)

These variables tell the PHP script how to access your database:

	$db_host        = "DATABASE HOST";
	$db_user        = "DATABASE USER NAME";
	$db_password    = "DATABASE PASSWORD";
	$db_name        = "DATABASE NAME";

For each application, create a duplicate of profileInfo-appname.php. In that file set one more variable that tells the PHP script the location of your appcast file:

    $appcastURL = "http://you.org/_sparkle/ayefoto-appcast.xml";

Then upload profileInfo-*appname*.php, profileInfo-common.php and profileDB.php to your web host.  The URL to profileInfo-*appname*.php should match the URL you entered in your app's Info.plist for the SUFeedURL key.

That's it!  Your web server is now ready to accept Sparkle requests with profile reports.

If you like, you can also load the files profileLookup.php, profileCharts.php and style.css on your web server.  The first file is a simple script to look up profile reports from the last month and display them in an HTML table. The second displays charts of the same information.