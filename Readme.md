Lightweight Subscribe To Comments 2
===================================

Let visitors subscribe to comments to get an email notification of new comments.

This is a fork of [Isabel Catillos >Lightweight Subscribe To Comments<](https://github.com/isabelc/Comment-Notifier-No-Spammers)

The original plugin was hasn't received any updated for years, but still works as expected in WP 6.0.x, therefore, I decided to maintain it myself.

Feel free to open issues if you experince bugs or miss some functionality.

Resources:

- [Corresponding Github](https://github.com/isabelc/Comment-Notifier-No-Spammers)

- [Wordpress Support](https://wordpress.org/support/plugin/comment-notifier-no-spammers/)

## Migration

This plugin is compatible with the database from the old plugin but not with the backend options (was too lazy lol)

1. Do a backup of the "comment-notifier" database table (just in case)
2. Make sure to **uncheck** the "delete all data on deactivation" checkbox at the old plugin menu
3. save all text changes you made
4. get this plugin
5. deactivate the old one
6. activate this one
7. reconfigure all texts in the new backend menu

All subscriptions are now visible in the new "Subscription Management".

## Differences to the old plugin

- no thank you massages
- own backend menu with settings and subscription management based on the current settings API
- no migration from other plugins
- own json endpoint for unsubscription