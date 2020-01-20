<?php
/**
 * Upvote plugin for Craft CMS
 *
 * Lets your users upvote/downvote, "like", or favorite any type of element.
 *
 * @author    Double Secret Agency
 * @link      https://www.doublesecretagency.com/
 * @copyright Copyright (c) 2014 Double Secret Agency
 */

namespace doublesecretagency\upvote\models;

use craft\base\Model;

/**
 * Class Settings
 * @since 2.0.0
 */
class Settings extends Model
{

    /** @var bool $preload Whether to preload data into DOM elements. */
    public $preload = true;

    /** @var bool $requireLogin Whether a user is required to login to vote. */
    public $requireLogin = true;

    /** @var bool $allowDownvoting Whether it's possible to downvote. */
    public $allowDownvoting = true;

    /** @var bool $allowVoteRemoval Whether users are allowed to remove their vote. */
    public $allowVoteRemoval = true;

    /** @var bool $allowFontAwesome Whether to require Font Awesome resources. */
    public $allowFontAwesome = true;

    /** @var bool $keepVoteLog Whether to keep a detailed log of all votes. */
    public $keepVoteLog = false;

}
