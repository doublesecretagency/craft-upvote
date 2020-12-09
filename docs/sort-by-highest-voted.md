---
description: You can sort your results to display the highest voted elements first. Fetch your ECM just as you normally would, then pass the ECM into the sort method.
---

# Sort by highest voted

You can sort your results to display the **highest voted** elements first!

Create an [Element Query](https://craftcms.com/docs/3.x/element-queries.html) just as you normally would, then pass the Element Query into the `sort` method.

```twig
{% set hotels = craft.entries.section('hotels') %}

{% do craft.upvote.sort(hotels) %}
```

If you want to sort by a specific [key](/multiple-voting-for-the-same-element/), simply add it as the second parameter...

```twig
{% do craft.upvote.sort(hotels, 'comfortable') %}
```

Votes can be assigned to any valid element type, whether it's native or 3rd party.

:::warning Must be an Element Query
Don't apply the `.all()` method until _after_ you have sorted the Element Query.
:::
