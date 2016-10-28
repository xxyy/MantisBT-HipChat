MantisBT-HipChat Revived
========================

This is a fork of the [MantisBT-HipChat plugin by Ben Ramsey](https://github.com/ramsey/MantisBT-HipChat), which is itself
based on [the MantisBT-Slack plugin](https://github.com/mantisbt-plugins/Slack).

This plugin for the [MantisBT](http://www.mantisbt.org/) issue tracking system extends Mantis with the capability to
send notifications to [HipChat](https://www.hipchat.com/) rooms. Specifically, it notifies on issue creation and
modification as well as on bug note creation and modification. I think this still uses the old HipChat API though.

Notable differences from upstream:

 * works on MantisBT 1.3.x ([They changed some of their events to have BugData parameters instead of the issue id](https://www.mantisbt.org/docs/master-1.3.x/en-US/Developers_Guide/html/dev.eventref.bug.action.html))
 * shows the new status if the status was changed instead of the generic 'bug updated' message
 * shows the new assignee if it is changed, providing fancy messages for self-assignment and unassignment
 * politely omits bug note contents from the notification if either the bug note or the issue itself are marked as private
 * does not prepend `@` to Mantis user names (this would lead to a lot of pings in HipChat, and also people might have different usernames there)
 * sends HTML messages instead of plain text ones, to show the icon
 * cleans up some duplicated code this is important okay
 * adds a MantisBT icon, which is currently served from my own server by default - if you use this, please change the path
    in `lang/strings_en.txt` to point to your own server, and open an issue if you have a suggestion on how I could detect
    the server path in the language files.

# Installation
* Extract this repo to your *Mantis folder/plugins/HipChat*.
* [Create an API token](https://www.hipchat.com/admin/api) for HipChat.
* On the MantisBT side, access the plugin's configuration page and fill in your HipChat API token.
* You can map your MantisBT projects to HipChat rooms by following the instructions on the plugin's configuration page.
* You can specify which bug fields appear in the HipChat notifications. Edit the *plugin_HipChat_columns* configuration option for this purpose.

