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

namespace doublesecretagency\upvote;

use Craft;
use craft\base\Element;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RegisterElementTableAttributesEvent;
use craft\events\SetElementTableAttributeHtmlEvent;
use craft\services\Plugins;
use craft\web\twig\variables\CraftVariable;
use doublesecretagency\upvote\models\Settings;
use doublesecretagency\upvote\services\Query;
use doublesecretagency\upvote\services\UpvoteService;
use doublesecretagency\upvote\services\Vote;
use doublesecretagency\upvote\variables\UpvoteVariable;
use yii\base\Event;

/**
 * Class Upvote
 * @since 2.0.0
 */
class Upvote extends Plugin
{

    /**
     * Actual vote values.
     */
    const UPVOTE   =  1;
    const DOWNVOTE = -1;

    /**
     * @event VoteEvent The event that is triggered before a vote is cast.
     */
    const EVENT_BEFORE_VOTE = 'beforeVote';

    /**
     * @event VoteEvent The event that is triggered after a vote is cast.
     */
    const EVENT_AFTER_VOTE = 'afterVote';

    /**
     * @event UnvoteEvent The event that is triggered before a vote is removed.
     */
    const EVENT_BEFORE_UNVOTE = 'beforeUnvote';

    /**
     * @event UnvoteEvent The event that is triggered after a vote is removed.
     */
    const EVENT_AFTER_UNVOTE = 'afterUnvote';

    /**
     * @var Upvote Self-referential plugin property.
     */
    public static Upvote $plugin;

    /**
     * @inheritdoc
     */
    public bool $hasCpSettings = true;

    /**
     * @inheritdoc
     */
    public string $schemaVersion = '2.0.0';

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        // Load plugin components
        $this->setComponents([
            'upvote'       => UpvoteService::class,
            'upvote_query' => Query::class,
            'upvote_vote'  => Vote::class,
        ]);

        // Load anonymous history (if relevant)
        $this->upvote->getAnonymousHistory();

        // Load logged-in user history (if relevant)
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_LOAD_PLUGINS,
            function () {
                // If no current user
                if (!Craft::$app->user->getIdentity()) {
                    return;
                }
                // If control panel, bail
                if (Craft::$app->getRequest()->getIsCpRequest()) {
                    return;
                }
                // Load user history
                $this->upvote->getUserHistory();
            }
        );

        // Register variables
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                $variable = $event->sender;
                $variable->set('upvote', UpvoteVariable::class);
            }
        );

        // Register element index columns
        Event::on(
            Element::class,
            Element::EVENT_REGISTER_TABLE_ATTRIBUTES,
            function (RegisterElementTableAttributesEvent $event) {
                $event->tableAttributes['upvote_voteTally']      = ['label' => Craft::t('upvote', 'Vote Tally')];
                $event->tableAttributes['upvote_totalVotes']     = ['label' => Craft::t('upvote', 'Total Votes')];
                $event->tableAttributes['upvote_totalUpvotes']   = ['label' => Craft::t('upvote', 'Total Upvotes')];
                $event->tableAttributes['upvote_totalDownvotes'] = ['label' => Craft::t('upvote', 'Total Downvotes')];
            }
        );

        // Register element index column HTML
        Event::on(
            Element::class,
            Element::EVENT_SET_TABLE_ATTRIBUTE_HTML,
            function(SetElementTableAttributeHtmlEvent $event) {
            $element = $event->sender;
            switch ($event->attribute) {
                case 'upvote_voteTally':
                    $event->html = Upvote::$plugin->upvote_query->tally($element->id);
                    $event->handled = true;
                    break;
                case 'upvote_totalVotes':
                    $event->html = Upvote::$plugin->upvote_query->totalVotes($element->id);
                    $event->handled = true;
                    break;
                case 'upvote_totalUpvotes':
                    $event->html = Upvote::$plugin->upvote_query->totalUpvotes($element->id);
                    $event->handled = true;
                    break;
                case 'upvote_totalDownvotes':
                    $event->html = Upvote::$plugin->upvote_query->totalDownvotes($element->id);
                    $event->handled = true;
                    break;
            }
        });

    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): ?string
    {
        $view = Craft::$app->getView();
        $overrideKeys = array_keys(Craft::$app->getConfig()->getConfigFromFile('upvote'));
        return $view->renderTemplate('upvote/settings', [
            'settings' => $this->getSettings(),
            'overrideKeys' => $overrideKeys,
            'docsUrl' => $this->documentationUrl,
        ]);
    }

}
