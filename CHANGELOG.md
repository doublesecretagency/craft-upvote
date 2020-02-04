# Changelog

## 2.1.0 - 2020-02-04

### Added
- **Cache-proof!!** Now works flawlessly with caching plugins or native Craft [caching](https://www.doublesecretagency.com/plugins/upvote/docs/caching).
- Added "Data Preloading" setting, which can be [disabled](https://www.doublesecretagency.com/plugins/upvote/docs/disable-js-preloading) to manually trigger page setup.
- Voting now has a visible impact on the Total Upvotes and Total Downvotes (if they are being displayed).

### Changed
- Improved the parameters returned when using [events](https://www.doublesecretagency.com/plugins/upvote/docs/events).

## 2.0.4 - 2019-09-24

### Added
- Added ability to vote on behalf of a specific user.
- Allow override of `keepVoteLog`.

### Fixed
- Ensure plugins have been loaded before running `init`.

## 2.0.3 - 2018-07-01

### Fixed
- Patched to run via CLI without errors.

## 2.0.2 - 2018-06-26

### Fixed
- Fixed bug in `userHistory` variable.

## 2.0.1 - 2018-05-08

### Fixed
- Fixed "table does not exist" error when installing.

## 2.0.0 - 2018-05-08

### Added
- Craft 3 compatibility.

## 1.3.1 - 2017-11-06

### Fixed
- Fixed bug in sorting mechanism.
- Respect custom action URL.

## 1.3.0 - 2017-10-31

### Added
- Added "Vote Tally" column to entries index page.
- Added ["Total Votes"](https://www.doublesecretagency.com/plugins/upvote/docs/getting-vote-totals) column to entries index page.
- Added "Total Upvotes" column to entries index page.
- Added "Total Downvotes" column to entries index page.
- Added [`totalVotes`](https://www.doublesecretagency.com/plugins/upvote/docs/getting-vote-totals) variable and service method.
- Added `totalUpvotes` variable and service method.
- Added `totalDownvotes` variable and service method.
- Added [events](https://www.doublesecretagency.com/plugins/upvote/docs/events).

## 1.2.2 - 2016-08-19

### Fixed
- Prevents console conflicts.

## 1.2.1 - 2016-05-26

### Added
- Added `userHistory` variable, to see what specific people have voted on.

## 1.2.0 - 2015-12-22

### Added
- Now accepts an optional "key" parameter, so you can [vote on multiple things about the same element](https://www.doublesecretagency.com/plugins/upvote/docs/multiple-voting-for-the-same-element).
- Now possible to [disable JS and/or CSS](https://www.doublesecretagency.com/plugins/upvote/docs/disable-js-or-css).
- Built-in Font Awesome icons (the Font Awesome library is now included by default).
- [BREAKING CHANGE:](https://www.doublesecretagency.com/plugins/upvote/docs/breaking-change-v1-2-0) A different technique is in place for overriding default icons.

### Changed
- Compatible with Craft 2.5.

## 1.0.1 - 2015-07-15

### Fixed
- Fixed bug occurring when downvoting is disabled.

## 1.0.0 - 2014-12-04

Initial release.
