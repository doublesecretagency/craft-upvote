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
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\elements\db\ElementQuery;

use doublesecretagency\upvote\Upvote;
use doublesecretagency\upvote\web\assets\CssAssets;
use doublesecretagency\upvote\web\assets\JsAssets;
use doublesecretagency\upvote\web\assets\FontAwesomeAssets;

/**
 * Class UpvoteVariable
 * @since 2.0.0
 */
class UpvoteVariable
{

    const CONTAINER = 'container';
    const NUMBER = 'number';
    const BOTH = 'both';

    private $_disabled = [];

    private $_cssIncluded  = false;
    private $_jsIncluded   = false;

    //
    public function userHistory($userId = null)
    {
        // Ensure the user ID is valid (defaults to current user)
        Upvote::$plugin->upvote->validateUserId($userId);

        return Upvote::$plugin->upvote_query->userHistory($userId);
    }

    // ========================================================================

    //
    public function upvote($elementId, $key = null)
    {
        return $this->_renderIcon(Upvote::UPVOTE, $elementId, $key);
    }

    //
    public function downvote($elementId, $key = null)
    {
        return $this->_renderIcon(Upvote::DOWNVOTE, $elementId, $key);
    }

    // ========================================================================

    //
    public function tally($elementId, $key = null, $format = self::CONTAINER)
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

    // Output total votes of element
    public function totalVotes($elementId, $key = null, $format = self::CONTAINER)
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

    // Output total upvotes of element
    public function totalUpvotes($elementId, $key = null, $format = self::CONTAINER)
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

    // Output total downvotes of element
    public function totalDownvotes($elementId, $key = null, $format = self::CONTAINER)
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

    // ========================================================================

    //
    private function _numericFormat($format): bool
    {
        // Whether we need the numeric value
        return in_array($format, [static::NUMBER, static::BOTH]);
    }

    //
    private function _getValue($method, $elementId, $key, $format)
    {
        // Return the numeric value
        if ($this->_numericFormat($format)) {
            return Upvote::$plugin->upvote_query->{$method}($elementId, $key);
        }

        // Default to non-breaking space
        return '&nbsp;';
    }

    // ========================================================================

    //
    private function _renderNumber($class, $elementId, $key, $value)
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

    //
    private function _renderIcon($vote, $elementId, $key)
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

    //
    public function jsUpvote($elementId, $key = null, $prefix = false)
    {
        if (!Upvote::$plugin->upvote->validKey($key)) {
            return false;
        }
        $this->_includeJs();
        $key = ($key ? "'$key'" : "null");
        return ($prefix?'javascript:':'')."upvote.upvote($elementId, $key)";
    }

    //
    public function jsDownvote($elementId, $key = null, $prefix = false)
    {
        if (!Upvote::$plugin->upvote->validKey($key)) {
            return false;
        }
        $this->_includeJs();
        $key = ($key ? "'$key'" : "null");
        return ($prefix?'javascript:':'')."upvote.downvote($elementId, $key)";
    }

    // Include CSS
    private function _includeCss()
    {
        // If CSS has been included, bail
        if ($this->_cssIncluded) {
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
        $this->_cssIncluded = true;
    }

    // Include JS
    private function _includeJs()
    {
        // If JS has been included, bail
        if ($this->_jsIncluded) {
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
        $this->_jsIncluded = true;
    }

    // ========================================================================

    // Customize icons
    public function setIcons($iconMap = [])
    {
        return Upvote::$plugin->upvote_vote->setIcons($iconMap);
    }

    // Sort by "highest rated"
    public function sort(ElementQuery $elements, $key = null)
    {
        Upvote::$plugin->upvote_query->orderByTally($elements, $key);
    }

    // Disable native CSS and/or JS
    public function disable($resources = [])
    {
        // If not a string or array, bail
        if (!is_string($resources) && !is_array($resources)) {
            return false;
        }
        // If string, convert to array
        if (is_string($resources)) {
            $resources = [$resources];
        }
        $this->_disabled = array_map('strtolower', $resources);
    }

    // ========================================================================

    // Whether the plugin contains legacy data
    public function hasLegacyData()
    {
        return Upvote::$plugin->upvote->hasLegacyData();
    }

}
