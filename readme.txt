=== WordPress Nice Login ===
Contributors: zworks
Donate link: http://www.webocado.com/
Tags: login, popup, ajax, ajax login, nice login, login popup, registration, redirect, simple
Requires at least: 3.7
Tested up to: 4.7.2
Stable tag: 1.0
License: GPLv2 or later

Provides a simple ajax login/registration popup, callable via JavaScript.

== Description ==

WordPress Nice Login is a replacement for the standard WordPress login page, users are no longer need to leave the current page for login/registration. All is included in a single pop-up where users can switch between login, register or reset password screens.

Some of the features:

* Login/Register/Reset Password while staying on the current page! 
* Redirect users to the current page or custom URLs on Login / Register
* Users can set their own password while registering
* Sends activation email after registration
* All three features are on a single pop-up! Can switch between screens easily.
* When clicking the activation link, users will be automatically redirected to the page where they registered.
* Option for resending Activation Link

== Installation & Usage ==

1. Upload this plugin to the `/wp-content/plugins/` directory and unzip it, or simply upload the zip file within your wordpress installation.

2. Activate the plugin through the 'Plugins' menu in WordPress

3. Just call `showNiceLogin()` JavaScript function on the front-end of any page where you want the visitor to login/register.

4. Start Nice Login'ing !!


If you have any issues, please visit the [support forums](http://wordpress.org/support/plugin/wp-nice-login).

= Translations available =

Here's a list of currently translated languages. If you'd like to contribute, please let us know on the [support forums](http://wordpress.org/support/plugin/wp-nice-login).

* French - [Webocado](http://www.webocado.com)

== Notes ==

The JavaScript function is only available on pages where the user is not logged-in. This function is not available on logged-in pages.

== Examples == 

1) `<a href="#" onClick="showNiceLogin()">Login/Register</a>`
    The user will be redirected to the current page after login/registration.

2) `<a href="#" onClick="showNiceLogin('http://mysite.com/page1')">Login/Register</a>`
    The user will be redirected to the http://mysite.com/page1 after login/registration.

== Screenshots ==

1. The ajax login process.

2. Error messages.

3. After successful login, auto refresh the current page or redirect to the specified link.

4. Forgot password form.

5. The registration process.

6. After registration user is notified to activate the account by clicking the activation link.

7. Activation email.

8. Notification if the user visits a wrong activation link.



== Frequently Asked Questions ==

= The popup doesn't show. =
Make sure the plugin is activated & the function is called on from a non-loggedin page.

= Can i customize the look & feel of the popup? =
At the moment you can customize the look & feel by overriding the css styles in your theme.

= Do you have a shortcode or template tag? =
The plugin is simple 

If you have any issues, please visit the [support forums](http://wordpress.org/support/plugin/wp-nice-login).

== Upgrade Notice ==

= 1.0 =
Initial version of the plugin

== Changelog ==

= 1.0 =
* Initial version of the plugin