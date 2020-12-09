---
description: These methods allow you to see what votes a user has cast. Retrieve the entire user vote history, or a subset of the history, or identify a specific vote.
---

# User Vote History

The following methods allow you to see what votes a user has cast. Depending on what you are trying to achieve, you can retrieve the entire user vote history, or a subset based on a unique key, or even identify which specific vote was cast by a user for a given element.

:::warning Requires Logged-in Users
Only logged-in users will have their votes recorded. Voting history is not available in anonymous voting systems.
:::

## userHistory(userId = null)

You can optionally specify the user ID to get the voting history of a specific user.

If you do not specify a user, by default the method will return the voting history of the **current user**.

```twig
{% for user in craft.users.all() %}
    {% set history = craft.upvote.userHistory(user.id) %}
    {# Do whatever you want with the history #}
{% endfor %}
```

The results will be an array of **element IDs**, and the user's corresponding **vote** for each element.

```js
// Element ID : User's Vote
{
    14:  1
    33:  1
    39: -1
    42:  1
}
```

The array **keys** are the element IDs of the elements being voted on.

The array **values** represent the direction of the vote. An upvote is **1**, a downvote is **-1**.

## userHistoryByKey(userId = null, keyFilter = false)

Nearly identical to the `userHistory` method (above), except you can also specify a [unique key](/multiple-voting-for-the-same-element/) with which to filter by.

:::warning Defaults to Current User
If you want to specify the `keyFilter` without specifying the `userId`, you can bypass it with _null_. That will default to providing the history of the **current user**.
:::

If you omit the `keyFilter`, the history will be the complete voting history for the specified user. It will be organized by keys, and arranged into subsets.

If you specify the `keyFilter`, the history will be only a subset of the complete user voting history.

```twig
{# Get the complete history, organized by keys #}
{% set history = craft.upvote.userHistoryByKey(currentUser.id) %}

{# Get a subset of the history, filtered by the specified key #}
{% set history = craft.upvote.userHistoryByKey(currentUser.id, 'funny') %}
```

## userVote(userId, elementId, key = null)

Get the _specific_ vote of a _specific_ user for a _specific_ element.

Returns `1`, `-1`, or `0`.

```
 1 = User has cast an upvote
-1 = User has cast a downvote
 0 = User has not voted on this element
 ```

Here's an example of how you might check to see whether a user has already voted on an entry...

```twig
{% if 1 == craft.upvote.userVote(currentUser.id, entry.id) %}
    {# User has upvoted this entry #}
{% endif %}
```
