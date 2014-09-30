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
		craft()->on('assets.saveAsset', function(Event $event) {
			craft()->upvote->initElementScore($event->params['asset'], true);
		});
		craft()->on('categories.saveCategory', function(Event $event) {
			craft()->upvote->initElementScore($event->params['category'], $event->params['isNewCategory']);
		});
		craft()->on('entries.saveEntry', function(Event $event) {
			craft()->upvote->initElementScore($event->params['entry'], $event->params['isNewEntry']);
		});
		craft()->on('tags.saveTag', function(Event $event) {
			craft()->upvote->initElementScore($event->params['tag'], $event->params['isNewTag']);
		});
		craft()->on('users.saveUser', function(Event $event) {
			craft()->upvote->initElementScore($event->params['user'], $event->params['isNewUser']);
		});
	}

	public function getName()
	{
		return Craft::t('Upvote');
	}

	public function getVersion()
	{
		return '0.9.5';
	}

	public function getDeveloper()
	{
		return 'Double Secret Agency';
	}

	public function getDeveloperUrl()
	{
		return 'https://github.com/lindseydiloreto/craft-upvote';
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
		);
	}

	private function _loadEnums()
	{
		require('enums/Vote.php');
	}
	
}
