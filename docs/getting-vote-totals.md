---
description: Retrieve the individual counts for "Vote Tally", "Total Votes", "Total Upvotes" and "Total Downvotes".
---

# Getting Vote Totals

:::tip Legacy Data
If you've updated from a version prior to 1.3.0, please pay attention to the **Legacy Data** information at the bottom of this page.
:::

So now that people are voting, you want to see how things are shaping up.

There are four key counts to be aware of:

 - **Vote Tally** - The grand sum of all votes (Upvotes - Downvotes)
 - **Total Votes** - The total count of all votes (Upvotes + Downvotes)
 - **Total Upvotes** - The total number of Upvotes
 - **Total Downvotes** - The total number of Downvotes

:::warning Difference between Vote Tally & Total Votes?

Let's say an item has **7 upvotes** and **3 downvotes**...

The vote tally is a measure of _how well liked something is overall_.

> **Vote Tally**<br>
> 7 - 3 = **4**

The total votes is simply _the number of times the item was voted on_.

> **Total Votes**<br>
> 7 + 3 = **10**

:::

Each of these values is retrievable via Twig...

```twig
{{ craft.upvote.tally(elementId) }}
{{ craft.upvote.totalVotes(elementId) }}
{{ craft.upvote.totalUpvotes(elementId) }}
{{ craft.upvote.totalDownvotes(elementId) }}
```

Or PHP...

```php
use doublesecretagency\upvote\Upvote;

Upvote::$plugin->upvote_query->tally($elementId)
Upvote::$plugin->upvote_query->totalVotes($elementId)
Upvote::$plugin->upvote_query->totalUpvotes($elementId)
Upvote::$plugin->upvote_query->totalDownvotes($elementId)
```

Or added as columns to the entries index page...

<img :src="$withBase('/images/upvote-index-columns.png')" class="dropshadow" alt="" style="max-width:600px">

## Legacy Data

For a long time, Upvote counted with a singular value (element tally). Both upvotes and downvotes contributed to the same value, with no real way to measure them separately.

With the release of Upvote v1.3.0, upvotes and downvotes are now tracked as _separate values_. This makes it possible to get individual counts for each.

If you were using Upvote prior to version 1.3.0, then you likely have legacy data in your system.

:::warning How do I know?
Go to the Upvote **Settings** page. At the bottom, you will see a message specifying whether or not your site contains legacy data.
:::

If your site **does not** contain legacy data, then you will be able to accurately use all features mentioned on this page.

If your site **does** contain legacy data, there is no harm to your site. Everything should continue working normally, although you will not be able to take advantage of many things mentioned on this page. The "Total Votes", "Total Upvotes" and "Total Downvotes" values would be inaccurate, since they **exclude** legacy data.

Fortunately, the "Vote Tally" value **includes** legacy data. It will work for everyone, regardless of whether they began tracking prior to v1.3.0.
