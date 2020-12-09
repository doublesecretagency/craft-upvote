---
description: You can see which users have voted on a particular element. It's possible to filter the vote history by a unique key.
---

# Element Vote History

:::warning Requires Logged-in Users
Only logged-in users will have their votes recorded. Voting history is not available in anonymous voting systems.
:::

---
---

## elementHistory(elementId, key = null)

You can see which users have voted on a particular element, and how they voted.

```twig
{% set history = craft.upvote.elementHistory(entry.id) %}
```

If you are using [unique keys](/multiple-voting-for-the-same-element/), it's possible to filter the history results.

```twig
{% set history = craft.upvote.elementHistory(entry.id, 'funny') %}
```

The results will be an array of **user IDs**, and each user's corresponding **vote** for the specified element.

```js
// User ID : User's Vote
{
     1:  1
    17: -1
    22:  1
    24:  1
}
```

The array **keys** are the user IDs of all users who have voted on this element.

The array **values** represent the direction of the vote. An upvote is **1**, a downvote is **-1**.
