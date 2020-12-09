---
description: A simple example of how to display a standard layout, with an upvote button, downvote button, and total element tally.
---

# Display voting arrows

Displaying an element's vote tally is very simple...

```twig
{{ craft.upvote.tally(elementId [, key = null]) }}
```

The `key` parameter is optional (see [rating multiple things about the same element](/multiple-voting-for-the-same-element/)).

#### BASIC EXAMPLE:

```twig
<table>
    {% for entry in craft.entries.section('musicCollection') %}
        <tr>
            <td>
                <div>{{ craft.upvote.upvote(entry.id) }}</div>
                <div>{{ craft.upvote.tally(entry.id) }}</div>
                <div>{{ craft.upvote.downvote(entry.id) }}</div>
            </td>
            <td>{{ entry.title }}</td>
        </tr>
    {% endfor %}
</table>
```
