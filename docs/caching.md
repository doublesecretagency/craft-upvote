---
description: Upvote is 100% cache-proof! With only a few minor tweaks, it will work great in any cached environment.
---

# Caching

As of v2.1, you can safely use Upvote in any cached environment. This means that native Craft caching, the Blitz plugin, or any other caching solution is fair game!

:::warning Blitz
If you are using Blitz, you can stop reading right here! You don't need to do anything mentioned below, Blitz + Upvote will "just work" automagically!
:::

Before you cache the Twig code containing your Upvote elements, **view the source code** and make note of the full paths to the following files...

```
/cpresources/***/css/font-awesome.min.css
/cpresources/***/css/upvote.css

/cpresources/***/js/sizzle.js
/cpresources/***/js/superagent.js
/cpresources/***/js/upvote.js
/cpresources/***/js/unvote.js
```

Copy all of those paths, and make sure that those files are still getting properly loaded once your page is being cached. Depending on how you are caching, it is likely that you will need to manually add the `style` and `script` tags to your page.

:::warning Skippable files
If you are not using Font Awesome to generate icons, then you can safely omit that CSS file.

Similarly, if vote removal is not allowed, you can skip the `unvote.js` file.
:::

**That's it!** As long as you are properly referencing all the necessary CSS and JS files, everything else should "just work".

Happy caching!
