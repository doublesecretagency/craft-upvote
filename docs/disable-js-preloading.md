---
description: By default, data (eg, vote tallies) will be automatically populated when a page is fully loaded. You can disable this if you'd like to load the data manually.
---

# Disable JS Preloading

When a page containing Upvote elements is loaded, virtually all of the voting data is loaded separately, after the page has finished loading.

During the initial Twig rendering, only empty DOM containers are rendered on the page. Once the page has finished loading, all of the voting data will be loaded via a separate AJAX call.

:::warning Cache-Proof
Configuring the plugin this way allows Upvote to be **cache-proof!** Upvote is fully compatible with caching plugins (like Blitz) and native Craft caching.

Read more about [caching Upvote...](/caching/)
:::

## Preload Setting

This screenshot shows the "Data Preloading" section of the plugin's config settings page...

<img :src="$withBase('/images/upvote-settings-preload-short.png')" class="dropshadow" alt="" style="max-width:600px">

If you disable this setting, you will need to load the Upvote data manually via JavaScript...

```js
upvote.pageSetup()
```

How you chose to trigger the page setup is entirely up to you.
