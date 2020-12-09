---
description: The icons are naturally formatted to show whether or not the user has clicked on them. You are free to override and customize the CSS however you wish.
---

# Customize your CSS

The icons are naturally formatted to show whether or not the user has clicked on them. You are free to override and customize the CSS however you wish.

<img :src="$withBase('/images/upvote-default.png')" class="dropshadow" alt="">

## CSS classes

The main reason to override the icon CSS would be to adjust their colors. Of course, any other CSS adjustments can be made at your discretion. If you'd like to change the icons being used, please read how to [customize your icons](/customize-your-icons/).

| Class                | Default Color    | Applies to...
|:---------------------|:-----------------|:--------------
| `.upvote-vote`       | `#cdcdcd` (grey) | All icons by default.
| `.upvote-vote-match` | `#d1202a` (red)  | Icons which match the user's vote history.
