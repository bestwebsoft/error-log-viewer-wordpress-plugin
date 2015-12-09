=== Error Log Viewer by BestWebSoft ===
Contributors: bestwebsoft
Donate link: http://bestwebsoft.com/donate/
Tags: actions, activity, admin, add tool, add debug tool, add error log viewer, add eror log viewer, add log viewer, analytics, dashboard, best error log viewer, best error log plugin, best plugin, clear log, clear log files, debug, debug tool, display errors, email notification, error, eror, error log, error log viewer, free, free error log, free error log viewer, free error log plugin, php error log, wp error log, log errors, log, debug, debug tool, error reporting, display errors, log, log monitor, notification, save log, find log, search log, search  logs, select log, select logs, select logs by date, store log, store log files, tracking, wordpress log, wp error log, wp error log viewer, wp log, wordpress error log, wordpress error log viewer
Requires at least: 3.8
Tested up to: 4.4
Stable tag: 1.0.2
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Now it's easy to work with log files and folders on the WordPress server.

== Description ==

This plugin finds and analyzes the log files on the WordPress folders and server, and allows you to work with them. You can select the files you want to work with. You can choose one of options for viewing the file: you can view only last lines, you can select logs by date, or you can view the full file. When there are changes in log files plugin can send notifications on your mail. Also there is a possibility to clear and store the log files.

http://www.youtube.com/watch?v=8LR0F4GgXhM

<a href="http://wordpress.org/plugins/error-log-viewer/faq/" target="_blank">Error Log Viewer by BestWebSoft FAQ</a>

<a href="http://support.bestwebsoft.com" target="_blank">Error Log Viewer by BestWebSoft Support</a>

= Features =

* Search logs on the Wordpress server and in WordPress folders 
* Saving and clearing log files.
* Sending notifications on mail after log files are changed 
* Viewing the last lines in the log files.
* Viewing the log files by date
* Viewing the log files fully

= Recommended Plugins = 

The author of the Error Log Viewer also recommends the following plugins:

* <a href="http://wordpress.org/plugins/updater/">Updater</a> - This plugin updates WordPress core and the plugins to the recent versions. You can also use the auto mode or manual mode for updating and set email notifications.
There is also a premium version of the plugin <a href="http://bestwebsoft.com/products/updater/">Updater Pro</a> with more useful features available. It can make backup of all your files and database before updating. Also it can forbid some plugins or WordPress Core update.

= Translation =

* Russian (ru_RU)
* Ukrainian (uk)

If you would like to create your own language pack or update the existing one, you can send <a href="http://codex.wordpress.org/Translating_WordPress" target="_blank">the text of PO and MO files</a> to <a href="http://support.bestwebsoft.com" target="_blank">BestWebSoft</a>, and we'll add it to the plugin. You can download the latest version of the program for working with PO and MO files  <a href="http://www.poedit.net/download.php" target="_blank">Poedit</a>.

= Technical support =

Dear users, our plugins are available for free download. If you have any questions or recommendations regarding the functionality of our plugins (existing options, new options, current issues), please feel free to contact us. Please note that we accept requests in English only. All messages in another languages won't be accepted.

If you notice any bugs in the plugin's work, you can notify us about them and we'll investigate and fix the issue then. Your request should contain website URL, issues description and WordPress admin panel credentials.
Moreover, we can customize the plugin according to your requirements. It's a paid service (as a rule it costs $40, but the price can vary depending on the amount of the necessary changes and their complexity). Please note that we could also include this or that feature (developed for you) in the next release and share with the other users then.
We can fix some things for free for the users who provide a translation of our plugin into their native language (this should be a new translation of a certain plugin, you can check available translations on the official plugin page).

== Installation == 

1. Upload the `error-log-viewer` folder to `/wp-content/plugins/` directory.
2. Activate the plugin using the 'Plugins' menu in your WordPress admin panel.
3. You can adjust the necessary settings using your WordPress admin panel in "BWS Plugins" > "Error Log Viewer".

<a href="https://docs.google.com/document/d/1MSbArf3NiazpfFL-kbaSqTjJbVhlVYq_wtgbU1739A8/edit" target="_blank">View a Step-by-step Instruction on Error Log Viewer Installation</a>.

== Frequently Asked Questions ==

= Why I can't select all three methods to enable debug? =

Because all methods are equivalent, so when you turn on them all only one of them will work.
There may be unwanted conflicts.

= I clicked on the checkbox to receive notification about the logs to my mailbox. But the letters come less than it exposed in the settings. Why? =

The function of notification sending implemented using Wordpress hook wp_shedule_event (). If during the chosen period of time the site has been inactive (no sign on it), this hook won't work.

= After creating a log file there are identical files appear in tabs PHP Error Log and WP Error Log. Why? =
 
It depends on the configuration of your server. In the tab of the log viewing the file will be only one.

= I can't view, download or clear the log file. =

Probably there is a problem with access to files and folders. For more information, please go to <a href="https://codex.wordpress.org/Changing_File_Permissions" target="_blank">Changing File Permissions</a> 

= What is the difference between three methods of a log file creating, which are offered by plugin? =

All methods are equivalent, so when you turn on them all only one of them will work.
There may be unwanted conflicts.

1) Error logging via '.htaccess' using 'ini_set'

This method is suitable if you have an access to the file '.htaccess' to edit it.
Also this method allows you to create a log file, its name, change the absolute path to it.
'php_flag' and 'php_value' change the value of Apache directives by changing the server configuration. The plugin uses this method only to enable PHP errors logging and specifying the path to the log files.  Other configuration settings you can change by yourself. More information you can find here <a href="http://php.net/manual/en/configuration.changes.php" target="_blank">How to change configuration settings</a> and here <a href="http://php.net/manual/en/ini.list.php" target="_blank">Directives list php.ini</a>

2) Error logging via 'wp-config.php' using 'ini_set'

If you don't have an access to '.htaccess', you can use file 'wp-config.php' to change server configuration settings using the 'ini_set' option and specifying a variety of error logging  settings and other options. The plugin uses this method only to activate the PHP error logging and specifying the path to the log files. More information you can find here <a href="http://php.net/manual/en/errorfunc.configuration.php#ini.error-log" target="_blank">Runtime Configuration</a> and
<a href="http://php.net/manual/en/function.ini-set.php" target="_blank">ini_set</a>

3) Error logging via 'wp-config.php' using 'WP_DEBUG'

This method is used for debugging errors using the WordPress PHP constants and declaring them in the 'wp-config.php' file. This is a standard WordPress debugging method. This is a very good method which is recommended for using on WordPress sites, but errors are recorded in the file 'debug.log' to the 'wp-content' directory. You can't change the absolute path to file logs. This method is considered to be a priority on the WordPress sites. After declaring of these constants other methods won't work. More information you can find here <a href="https://codex.wordpress.org/Debugging_in_WordPress" target="_blank">Errors Debugging on the WordPress</a>

= I have some problems with the plugin's work. What Information should I provide to receive proper support? =

Please make sure that the problem hasn't been discussed on our forum yet (<a href="http://support.bestwebsoft.com" target="_blank">http://support.bestwebsoft.com</a>). If not, please provide the following data along with your problem's description:

1. the link to the page, on which the problem occurs
2. the plugin's name and version. If you are using a pro version - your order number.
3. the version of your WordPress installation
4. copy and paste your system status report into the message. Please read more here: <a href="https://docs.google.com/document/d/1Wi2X8RdRGXk9kMszQy1xItJrpN0ncXgioH935MaBKtc/edit" target="_blank">Instuction on System Status</a>

== Screenshots ==

1. Settings page for create log file.
2. Settings page for selecting viewed file.
3. Settings page for sending e-mail.
4. PHP error log monitor.
5. WP error log monitor.

== Changelog ==

= V1.0.2 - 09.12.2015 =
* Bugfix : The bug with plugin menu duplicating was fixed.

= V1.0.1 - 20.10.2015 =
* NEW : We added ability to restore settings to defaults.

= V1.0.0 - 08.09.2015 =
* Release date of Error Log Viewer

== Upgrade Notice ==

= V1.0.2 =
The bug with plugin menu duplicating was fixed.

= V1.0.1 =
We added ability to restore settings to defaults.

= V1.0.0 =
* Release date of Error Log Viewer
