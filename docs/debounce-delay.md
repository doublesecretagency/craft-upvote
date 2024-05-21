---
description: To prevent rapid-fire clicks from triggering too many votes, a debounce system consolidates multiple clicks down into a single click.
---

# Debounce Delay

In order to prevent multiple votes from being triggered in rapid succession, Upvote uses a debounce technique to delay a short burst of clicks, and consolidate them down into a single click.

By default, the debounce delay is set to `200` milliseconds.

You can adjust this value on the plugin's **Settings** page if you feel the timing is too long or short.

<img :src="$withBase('/images/debounce-delay.png')" class="dropshadow" alt="" style="max-width:433px; margin-top:13px;">
