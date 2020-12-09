---
description: There are 3 formatting options when handling a numeric value in Twig. You can output a value as an integer, or a placeholder container, or both.
---

# Control the output format

There are [four methods](/getting-vote-totals/) that will output a numeric value...

```twig
{{ craft.upvote.tally(elementId) }}
{{ craft.upvote.totalVotes(elementId) }}
{{ craft.upvote.totalUpvotes(elementId) }}
{{ craft.upvote.totalDownvotes(elementId) }}
```

You may also know that each of these methods will accept a second parameter, which allows you to vote on [multiple attributes of the same element](/multiple-voting-for-the-same-element/).

But did you know that there is an optional _third_ parameter? (introduced in v2.1.2)

```twig
{{ craft.upvote.tally(elementId, null, "container") }}
```

The third parameter controls **how the value is returned**. If you have a special use-case, this parameter may come in handy. The format parameter can have three potential string values...

`"container"` _(default behavior)_

 - Renders a simple HTML container, which is effectively empty. After the page has finished loading, the container will get dynamically populated with a numeric value via JavaScript. This technique helps make the plugin [cache-proof](/caching/).

```twig
{# Populates via JS after the page loads #}
<span data-id="1" class="...">&nbsp;</span>
```

`"number"`

 - Outputs a simple integer. If you want to use the value for math, this option is for you.

```twig
{# A simple integer #}
42
```

`"both"`

 - Renders an integer inside of an HTML container. It quite literally merges the other two formats together. This is most useful if you are bothered by the [FOUC](https://en.wikipedia.org/wiki/Flash_of_unstyled_content), and you are _**not**_ using any sort of caching.

```twig
{# Both formats combined #}
<span data-id="1" class="...">42</span>
```

:::warning Skipping the second parameter
If you want to skip the [key parameter](/multiple-voting-for-the-same-element/), you can simply set it to `null`.
:::
