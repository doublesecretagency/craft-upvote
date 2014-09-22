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
	}

	public function getName()
	{
		return Craft::t('Upvote');
	}

	public function getVersion()
	{
		return '0.8.4';
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
