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

namespace doublesecretagency\upvote\variables;

use Craft;
use craft\elements\db\ElementQuery;
use craft\elements\User;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use doublesecretagency\upvote\Upvote;
use doublesecretagency\upvote\web\assets\CssAssets;
use doublesecretagency\upvote\web\assets\FontAwesomeAssets;
use doublesecretagency\upvote\web\assets\JsAssets;
use Twig\Markup;
use yii\base\InvalidConfigException;

/**
 * Class UpvoteVariable
 * @since 2.0.0
 */
class UpvoteVariable
{

    /**
     * Display formats.
     */
    const CONTAINER = 'container';
    const NUMBER = 'number';
    const BOTH = 'both';

    /**
     * @var array List of disabled assets.
     */
    private array $_disabled = [];

    /**
     * @var bool Whether the CSS assets have already been loaded.
     */
    private bool $_cssLoaded = false;

    /**
     * @var bool Whether the JavaScript assets have already been loaded.
     */
    private bool $_jsLoaded  = false;

    // ========================================================================= //

    /**
     * Get complete record of which users
     * have voted on a particular element,
     * and how they voted.
     *
     * @param int $elementId
     * @param null|string $key
     * @return array
     */
    public function elementHistory(int $elementId, ?string $key = null): array
    {
        return Upvote::$plugin->upvote_query->elementHistory($elementId, $key);
    }

    // ========================================================================= //

    /**
     * Get the vote history of specified user.
     *
     * @param null|User|int $userId
     * @return array
     */
    public function userHistory(null|User|int $userId = null): array
    {
        // Ensure the user ID is valid (defaults to current user)
        Upvote::$plugin->upvote->validateUserId($userId);
        // Get the vote history of specified user
        return Upvote::$plugin->upvote_query->userHistory($userId);
    }

    /**
     * Get the vote history of specified user, filtered by specified key.
     *
     * @param null|User|int $userId
     * @param null|string $key
     * @return array
     */
    public function userHistoryByKey(null|User|int $userId = null, ?string $key = null): array
    {
        // Ensure the user ID is valid (defaults to current user)
        Upvote::$plugin->upvote->validateUserId($userId);
        // Get the vote history of specified user, filtered by specified key
        return Upvote::$plugin->upvote_query->userHistoryByKey($userId, $key);
    }

    /**
     * Get the specific vote of a specific user for a specific element.
     *
     * @param int $userId
     * @param int $elementId
     * @param null|string $key
     * @return int
     */
    public function userVote(int $userId, int $elementId, ?string $key = null): int
    {
        return Upvote::$plugin->upvote_query->userVote($userId, $elementId, $key);
    }

    // ========================================================================= //

    /**
     * Display an upvote button.
     *
     * @param int $elementId
     * @param null|string $key
     * @return Markup
     */
    public function upvote(int $elementId, ?string $key = null): Markup
    {
        return $this->_renderIcon(Upvote::UPVOTE, $elementId, $key);
    }

    /**
     * Display a downvote button.
     *
     * @param int $elementId
     * @param null|string $key
     * @return Markup
     */
    public function downvote(int $elementId, ?string $key = null): Markup
    {
        return $this->_renderIcon(Upvote::DOWNVOTE, $elementId, $key);
    }

    // ========================================================================= //

    /**
     * Display cumulative vote tally for specified element.
     *
     * @param int $elementId
     * @param null|string $key
     * @param string $format
     * @return int|Markup
     */
    public function tally(int $elementId, ?string $key = null, string $format = self::CONTAINER): int|Markup
    {
        // Get value of element
        $value = $this->_getValue('tally', $elementId, $key, $format);

        // If number format, return integer value
        if (static::NUMBER == $format) {
            return (int) $value;
        }

        // Return number
        return $this->_renderNumber('upvote-tally', $elementId, $key, $value);
    }

    /**
     * Display total votes of specified element.
     *
     * @param int $elementId
     * @param null|string $key
     * @param string $format
     * @return int|Markup
     */
    public function totalVotes(int $elementId, ?string $key = null, string $format = self::CONTAINER): int|Markup
    {
        // Get value of element
        $value = $this->_getValue('totalVotes', $elementId, $key, $format);

        // If number format, return integer value
        if (static::NUMBER == $format) {
            return (int) $value;
        }

        // Return number
        return $this->_renderNumber('upvote-total-votes', $elementId, $key, $value);
    }

    /**
     * Display total upvotes of specified element.
     *
     * @param int $elementId
     * @param null|string $key
     * @param string $format
     * @return int|Markup
     */
    public function totalUpvotes(int $elementId, ?string $key = null, string $format = self::CONTAINER): int|Markup
    {
        // Get value of element
        $value = $this->_getValue('totalUpvotes', $elementId, $key, $format);

        // If number format, return integer value
        if (static::NUMBER == $format) {
            return (int) $value;
        }

        // Return number
        return $this->_renderNumber('upvote-total-upvotes', $elementId, $key, $value);
    }

    /**
     * Display total downvotes of specified element.
     *
     * @param int $elementId
     * @param null|string $key
     * @param string $format
     * @return int|Markup
     */
    public function totalDownvotes(int $elementId, ?string $key = null, string $format = self::CONTAINER): int|Markup
    {
        // Get value of element
        $value = $this->_getValue('totalDownvotes', $elementId, $key, $format);

        // If number format, return integer value
        if (static::NUMBER == $format) {
            return (int) $value;
        }

        // Return number
        return $this->_renderNumber('upvote-total-downvotes', $elementId, $key, $value);
    }

    // ========================================================================= //

    /**
     * Get value inside the container.
     *
     * @param string $method
     * @param int $elementId
     * @param null|string $key
     * @param string $format
     * @return int|string
     * @throws InvalidConfigException
     */
    private function _getValue(string $method, int $elementId, ?string $key, string $format): int|string
    {
        // If container format, ensure JS gets loaded
        if (self::CONTAINER === $format) {
            $this->_includeJs();
        }

        // If a numeric format was requested
        if ($this->_numericFormat($format)) {
            // Return the numeric value
            return Upvote::$plugin->upvote_query->{$method}($elementId, $key);
        }

        // Default to a non-breaking space
        return '&nbsp;';
    }

    /**
     * Whether we need the numeric value.
     *
     * @param string $format
     * @return bool
     */
    private function _numericFormat(string $format): bool
    {
        // Return whether we need the numeric value
        return in_array($format, [static::NUMBER, static::BOTH]);
    }

    // ========================================================================= //

    /**
     * Render the Twig Markup of a number container.
     *
     * @param string $class
     * @param int $elementId
     * @param null|string $key
     * @param int|string $value
     * @return Markup
     */
    private function _renderNumber(string $class, int $elementId, ?string $key, int|string $value): Markup
    {
        // Get Upvote service
        $upvote = Upvote::$plugin->upvote;

        // Set classes
        $genericClass = 'upvote-el '.$class;
        $uniqueClass = $class.'-'.$upvote->setItemKey($elementId, $key, '-');

        // Set data ID
        $dataId = $upvote->setItemKey($elementId, $key);

        // Compile HTML
        $html = '<span data-id="'.$dataId.'" class="'.$genericClass.' '.$uniqueClass.'">'.$value.'</span>';

        // Return HTML
        return Template::raw($html);
    }

    /**
     * Render the Twig Markup for a particular icon.
     *
     * @param string $vote
     * @param int $elementId
     * @param null|string $key
     * @return Markup
     * @throws InvalidConfigException
     */
    private function _renderIcon(string $vote, int $elementId, ?string $key): Markup
    {
        $this->_includeCss();
        // Get Upvote service
        $upvote = Upvote::$plugin->upvote;
        // Establish basics
        $genericClass = 'upvote-el upvote-vote ';
        switch ($vote) {
            case Upvote::UPVOTE:
                $icon = Upvote::$plugin->upvote_vote->upvoteIcon;
                $js = $this->jsUpvote($elementId, $key);
                $genericClass .= 'upvote-upvote';
                $uniqueClass   = 'upvote-upvote-'.$upvote->setItemKey($elementId, $key, '-');
                break;
            case Upvote::DOWNVOTE:
                $icon = Upvote::$plugin->upvote_vote->downvoteIcon;
                $js = $this->jsDownvote($elementId, $key);
                $genericClass .= 'upvote-downvote';
                $uniqueClass   = 'upvote-downvote-'.$upvote->setItemKey($elementId, $key, '-');
                break;
        }
        // Set data ID
        $dataId = $upvote->setItemKey($elementId, $key);
        // Compile HTML
        $span = '<span data-id="'.$dataId.'" class="'.$genericClass.' '.$uniqueClass.'" onclick="'.$js.'">'.$icon.'</span>';
        return Template::raw($span);
    }

    // ========================================================================= //

    /**
     * Get the JavaScript command to perform an upvote.
     *
     * @param int $elementId
     * @param null|string $key
     * @param null|string $prefix
     * @return null|string
     * @throws InvalidConfigException
     */
    public function jsUpvote(int $elementId, ?string $key = null, ?string $prefix = null): ?string
    {
        if (!Upvote::$plugin->upvote->validKey($key)) {
            return false;
        }
        $this->_includeJs();
        $key = ($key ? "'$key'" : "null");
        return ($prefix?'javascript:':'')."upvote.upvote($elementId, $key)";
    }

    /**
     * Get the JavaScript command to perform a downvote.
     *
     * @param int $elementId
     * @param null|string $key
     * @param null|string $prefix
     * @return null|string
     * @throws InvalidConfigException
     */
    public function jsDownvote(int $elementId, ?string $key = null, ?string $prefix = null): ?string
    {
        if (!Upvote::$plugin->upvote->validKey($key)) {
            return null;
        }
        $this->_includeJs();
        $key = ($key ? "'$key'" : "null");
        return ($prefix?'javascript:':'')."upvote.downvote($elementId, $key)";
    }

    // ========================================================================= //

    /**
     * Include the necessary CSS assets.
     *
     * @throws InvalidConfigException
     */
    private function _includeCss(): void
    {
        // If CSS has been loaded, bail
        if ($this->_cssLoaded) {
            return;
        }

        // If CSS is disabled, bail
        if (in_array('css', $this->_disabled)) {
            return;
        }

        // Get view
        $view = Craft::$app->getView();

        // Include CSS resources
        if (Upvote::$plugin->getSettings()->allowFontAwesome) {
            $view->registerAssetBundle(FontAwesomeAssets::class);
        }
        $view->registerAssetBundle(CssAssets::class);

        // Mark CSS as included
        $this->_cssLoaded = true;
    }

    /**
     * Include the necessary JS assets.
     *
     * @throws InvalidConfigException
     */
    private function _includeJs(): void
    {
        // If JS has been loaded, bail
        if ($this->_jsLoaded) {
            return;
        }

        // If JS is disabled, bail
        if (in_array('js', $this->_disabled)) {
            return;
        }

        // Get view
        $view = Craft::$app->getView();

        // Include JS resources
        $view->registerAssetBundle(JsAssets::class);

        // Dev Mode
        if (Craft::$app->getConfig()->getGeneral()->devMode) {
            $view->registerJs('upvote.devMode = true;', $view::POS_END);
        }

        // Action trigger
        $view->registerJs('upvote.actionUrl = "'.UrlHelper::actionUrl().'";', $view::POS_END);

        // Mark JS as included
        $this->_jsLoaded = true;
    }

    // ========================================================================= //

    /**
     * Set new custom icons for upvote & downvote buttons.
     *
     * @param array $iconMap
     */
    public function setIcons(array $iconMap = []): void
    {
        Upvote::$plugin->upvote_vote->setIcons($iconMap);
    }

    /**
     * Sort query by "highest rated".
     *
     * @param ElementQuery $elements
     * @param null|string $key
     */
    public function sort(ElementQuery $elements, ?string $key = null): void
    {
        Upvote::$plugin->upvote_query->orderByTally($elements, $key);
    }

    /**
     * Disable native CSS and/or JS.
     *
     * @param string|array $resources
     */
    public function disable(string|array $resources = []): void
    {
        // If not a string or array, bail
        if (!is_string($resources) && !is_array($resources)) {
            return;
        }
        // If string, convert to array
        if (is_string($resources)) {
            $resources = [$resources];
        }
        $this->_disabled = array_map('strtolower', $resources);
    }

    // ========================================================================= //

    /**
     * Whether the plugin contains legacy data.
     *
     * @return bool
     */
    public function hasLegacyData(): bool
    {
        return Upvote::$plugin->upvote->hasLegacyData();
    }

}
