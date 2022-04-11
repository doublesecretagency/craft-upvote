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

    /**
     * @var bool Whether to preload data into DOM elements.
     */
    public bool $preload = true;

    /**
     * @var bool Whether a user is required to login to vote.
     */
    public bool $requireLogin = true;

    /**
     * @var bool Whether it's possible to downvote.
     */
    public bool $allowDownvoting = true;

    /**
     * @var bool Whether users are allowed to remove their vote.
     */
    public bool $allowVoteRemoval = true;

    /**
     * @var bool Whether to require Font Awesome resources.
     */
    public bool $allowFontAwesome = true;

    /**
     * @var bool Whether to keep a detailed log of all votes.
     */
    public bool $keepVoteLog = false;

}
