---
description: If your version of Upvote is 2.0.0 or newer, then the following information is not relevant to you.
---

# BREAKING CHANGE (v2.0.0)

:::warning Is this relevant for you?
This warning is only for people who are:

 - Updating from an existing Craft 2 site.
 - Allowed **anonymous voting** on your site.

If you are not upgrading from Craft 2, or your site does not allow anonymous voting, then this warning is not for you.
:::

## What was the change?

Between Craft 2 and Craft 3, there was a significant internal change _within Craft_ which changes how cookies are stored. Specifically, it changed how cookies were named, and how their values were encoded.

## What are the repercussions?

It means that your anonymous voters will lose their existing vote history when you upgrade to Craft 3. Since their vote history was stored in a cookie, and those cookies are irretrievable, their vote histories are essentially gone.

To be fair, anonymous voting was never all that secure, which is why it was always _highly discouraged_.

## What can I do about it?

Sadly, nothing.

A valiant effort was made to salvage existing vote history cookies. Unfortunately, those efforts were dashed by the truly complex nature of the Craft 2 cookie encoding mechanism.

For full details, see this [Stack Exchange post...](https://craftcms.stackexchange.com/a/25570/45)
