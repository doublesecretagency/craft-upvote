---
description: You can use Upvote to handle a system of "Favorites".
---

# Using "Favorites"

To create a system of "favorites":

1. Render [only an upvote button](/display-voting-arrows/) (omitting the downvote button).
2. Customize the [icon](/customize-your-icons/) if desired.
2. Use functions like [`userHistory`](/user-vote-history/) and [`elementHistory`](/element-vote-history/) to retrieve the favorites data.

## List all favorited entries

Display a list of the current user's favorited entries...

```twig
{% if currentUser %}
    <h1>Your Favorite Posts</h1>
    
    {# Get complete user history #}
    {% set history = craft.upvote.userHistory(currentUser.id) %}
    
    {# Get IDs of all favorited entries #}
    {% set favoriteIds = history | filter(v => v == 1) | keys %}
    
    {# Get entries with matching IDs #}
    {% set entries = craft.entries().id(favoriteIds).all() %}
    
    {# Loop over favorited entries #}
    {% for entry in entries %}
        <div>{{ entry.id }} - {{ entry.title }}</div>
    {% endfor %}
    
{% endif %}
```
