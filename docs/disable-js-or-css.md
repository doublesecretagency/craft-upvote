---
description: Upvote includes a small amount of CSS and JS to work its magic. However, they can be easily disabled if your setup doesn't require them.
---

# Disable JS or CSS

Upvote includes a small amount of CSS and JavaScript to work its magic. However, if your setup requires that the plugin does not instantiate its JS and/or CSS in the usual fashion, they can be easily disabled.

Simply add this code to the top of your Twig template:

```twig
{% do craft.upvote.disable(['css','js']) %}
```

The example above will disable **both** the CSS and JS included with the plugin. If you'd like to disable only one or the other, it can be passed directly as a string (the array format is optional).

```twig
{% do craft.upvote.disable('js') %}
```

Make sure to include this code **at the top** of your Twig template, before any vote icons are rendered.
