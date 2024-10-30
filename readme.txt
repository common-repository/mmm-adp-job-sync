=== Mmm ADP Job Sync ===
Contributors: MManifesto
Donate link: http://www.mediamanifesto.com/donate/
Tags: Automation, ADP, Careers, Jobs
Requires at least: 3.4
Tested up to: 4.9.1
Stable tag: 4.9.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows you to read public ADP job listings from workforcenow.adp.com and sync them as posts on your site.

== Description ==

This is a relatively simple plugin that given a workforcenow company name can pull all public job listings and use them to create posts on your site. It also includes an hourly event that will check for changes on your listings to turn into posts. If a job is no longer available then the plugin will set the post status to pending otherwise it'll leave it alone.

There is a small settings page under the Settings > menu in the admin dashboard where you can set your company key (the page to check), a default post category (e.g. Careers), a default user to set as the post's owner, default post status (pending, draft, publish) and a template for the post content.

With the post template you can write some basic html along with placeholders for variables from the job posting. This way you can add a custom CTA at the top or bottom (or both) of the post. Variables include {{title}}, {{description}} and {{joburl}}. Simply drop those squiggly brackets into the template textarea on the settings page and your template will be applied to any new posts. Note that this template will be applied to all posts on save.

Lastly - on the settings page there is a manual sync button (because who wants to wait an hour to see if it works right?)

== Installation ==

1. Download and install the plugin from WordPress dashboard. You can also upload the entire folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the ‘Plugins’ menu in WordPress=

== Frequently Asked Questions ==

= Why should I use this plugin? =

If you are like me and find copy / paste efforts with HR teams tedious and soul crushing and your HR team uses workforcenow.adp.com then this plugin is for you!

== Changelog ==

= 1.0.0 =
Initial commit