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

    private $_disabled = [];

    private $_cssIncluded  = false;
    private $_jsIncluded   = false;
    private $_csrfIncluded = false;

    //
    public function userHistory($userId = null)
    {
        // Ensure the user ID is valid (defaults to current user)
        Upvote::$plugin->upvote->validateUserId($userId);

        return Upvote::$plugin->upvote_query->userHistory($userId);
    }

    //
    public function tally($elementId, $key = null)
    {
        // Get Upvote service
        $upvote = Upvote::$plugin->upvote;
        // Set classes
        $genericClass = 'upvote-el upvote-tally';
        $uniqueClass  = 'upvote-tally-'.$upvote->setItemKey($elementId, $key, '-');
        // Set data ID
        $dataId = $upvote->setItemKey($elementId, $key);
        // Compile HTML
        $span = '<span data-id="'.$dataId.'" class="'.$genericClass.' '.$uniqueClass.'">&nbsp;</span>';
        // Return HTML
        return Template::raw($span);
    }

    //
    public function upvote($elementId, $key = null)
    {
        return $this->_renderIcon($elementId, $key, Upvote::UPVOTE);
    }

    //
    public function downvote($elementId, $key = null)
    {
        return $this->_renderIcon($elementId, $key, Upvote::DOWNVOTE);
    }

    // ========================================================================

    // Output total votes of element
    public function totalVotes($elementId, $key = null)
    {
        // If element ID is invalid, log error
        if (!$elementId || !is_numeric($elementId)) {
//            UpvotePlugin::log('Invalid element ID');
            return 0;
        }
        return Upvote::$plugin->upvote_query->totalVotes($elementId, $key);
    }

    // Output total upvotes of element
    public function totalUpvotes($elementId, $key = null)
    {
        // If element ID is invalid, log error
        if (!$elementId || !is_numeric($elementId)) {
//            UpvotePlugin::log('Invalid element ID');
            return 0;
        }
        return Upvote::$plugin->upvote_query->totalUpvotes($elementId, $key);
    }

    // Output total downvotes of element
    public function totalDownvotes($elementId, $key = null)
    {
        // If element ID is invalid, log error
        if (!$elementId || !is_numeric($elementId)) {
//            UpvotePlugin::log('Invalid element ID');
            return 0;
        }
        return Upvote::$plugin->upvote_query->totalDownvotes($elementId, $key);
    }

    // ========================================================================

    //
    private function _renderIcon($elementId, $key = null, $vote)
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
