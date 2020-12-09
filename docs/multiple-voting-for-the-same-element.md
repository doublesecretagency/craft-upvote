---
description: It's possible to allow voting across various aspects of the same element.
---

# Multiple voting for the same element

It's possible to allow voting across various aspects of the same element.

For example, if you have a section for Hotels, you can allow voting for "Comfort", "Cleanliness", "Friendliness", etc.

```twig
<p>Was the hotel comfortable?</p>
{{ craft.upvote.upvote(hotel.id, 'comfortable') }}
{{ craft.upvote.downvote(hotel.id, 'comfortable') }}

<p>Was the hotel clean?</p>
{{ craft.upvote.upvote(hotel.id, 'clean') }}
{{ craft.upvote.downvote(hotel.id, 'clean') }}

<p>Was the hotel staff friendly?</p>
{{ craft.upvote.upvote(hotel.id, 'friendlyStaff') }}
{{ craft.upvote.downvote(hotel.id, 'friendlyStaff') }}
```
