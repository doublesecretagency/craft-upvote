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

namespace doublesecretagency\upvote\events;

use yii\base\Event;

/**
 * Class UnvoteEvent
 * @since 2.0.0
 */
class UnvoteEvent extends Event
{

    /** @var int|null The element ID for the item being voted upon. */
    public $id;

    /** @var string|null An optional key. */
    public $key;

    /** @var int|null The opposing vote value. */
    public $antivote;

}