---
description: It's simple to replace the default upvote & downvote icons.
---

# Customize your icons

It's incredibly easy to customize your icons...

#### BASIC EXAMPLE:

```twig
{% do craft.upvote.setIcons({
    up   : '<i class="fa fa-thumbs-up"></i>',
    down : '<i class="fa fa-thumbs-down"></i>',
}) %}
```

#### RESULTS:

<img :src="$withBase('/images/like-dislike.png')" class="dropshadow" alt="">

Practically any HTML is acceptable. And since **Font Awesome** is natively included in the plugin, you can easily use any other [Font Awesome icons!](https://fontawesome.com/icons)

:::warning Disabling Font Awesome
If you don't need the Font Awesome library to be run by the plugin, you can simply disable it on the plugin's Settings page.
:::

If you'd like to change the colors or other formatting, please read how to [customize your CSS](/customize-your-css/).
