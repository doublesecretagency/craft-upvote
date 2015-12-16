<?php
namespace Craft;

class UpvotePlugin extends BasePlugin
{

	public function init()
	{
		parent::init();
		// Enums
		$this->_loadEnums();
		// Plugin Settings
		craft()->upvote->settings = $this->getSettings();
		craft()->upvote->getAnonymousHistory();
		// Events
		if (2.3 <= craft()->getVersion()) {
			// NEW EVENT (Craft v2.3)
			craft()->on('elements.saveElement', function(Event $event) {
				craft()->upvote->initElementTally($event->params['element']->id, $event->params['isNewElement']);
			});
		} else {
			// ORIGINAL EVENTS
			craft()->on('assets.saveAsset', function(Event $event) {
				craft()->upvote->initElementTally($event->params['asset']->id);
			});
			craft()->on('categories.saveCategory', function(Event $event) {
				craft()->upvote->initElementTally($event->params['category']->id, $event->params['isNewCategory']);
			});
			craft()->on('entries.saveEntry', function(Event $event) {
				craft()->upvote->initElementTally($event->params['entry']->id, $event->params['isNewEntry']);
			});
			craft()->on('tags.saveTag', function(Event $event) {
				craft()->upvote->initElementTally($event->params['tag']->id, $event->params['isNewTag']);
			});
			craft()->on('users.saveUser', function(Event $event) {
				craft()->upvote->initElementTally($event->params['user']->id, $event->params['isNewUser']);
			});
		}
	}

	public function getName()
	{
		return Craft::t('Upvote');
	}

	public function getDescription()
	{
		return 'Allows users to upvote/downvote or "like", any type of element.';
	}

	public function getDocumentationUrl()
	{
		return 'https://craftpl.us/plugins/upvote';
	}

	public function getVersion()
	{
		return '1.2.0 rc';
	}

	public function getSchemaVersion()
	{
		return '1.0.2'; // Bump to 1.2.0 with migration!
	}

	public function getDeveloper()
	{
		return 'Double Secret Agency';
	}

	public function getDeveloperUrl()
	{
		return 'https://craftpl.us/plugins/upvote';
		//return 'http://doublesecretagency.com';
	}

	public function getSettingsHtml()
	{
		return craft()->templates->render('upvote/_settings', array(
			'settings' => craft()->upvote->settings
		));
	}

	protected function defineSettings()
	{
		return array(
			'requireLogin'     => array(AttributeType::Bool, 'default' => true),
			'allowDownvoting'  => array(AttributeType::Bool, 'default' => true),
			'allowVoteRemoval' => array(AttributeType::Bool, 'default' => true),
			'allowFontAwesome' => array(AttributeType::Bool, 'default' => true),
			'keepVoteLog'      => array(AttributeType::Bool, 'default' => false),
		);
	}

	private function _loadEnums()
	{
		require('enums/Vote.php');
	}

	public function onAfterInstall()
	{
		craft()->upvote->initAllElementTallies();
	}

}
