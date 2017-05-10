<?php
namespace Craft;

class UpvotePlugin extends BasePlugin
{

	public function init()
	{
		parent::init();
		if (!craft()->isConsole()) {
			// Enums
			$this->_loadEnums();
			// Plugin Settings
			craft()->upvote->settings = $this->getSettings();
			craft()->upvote->getAnonymousHistory();
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
		return '1.2.3 rc 2';
	}

	public function getSchemaVersion()
	{
		return '1.2.0';
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

	// ================================================================= //

	public function defineAdditionalEntryTableAttributes()
	{
		return array(
			'upvote_voteTotal' => "Vote Total",
		);
	}

	public function getEntryTableAttributeHtml(EntryModel $entry, $attribute)
	{
		if ($attribute == 'upvote_voteTotal')
		{
			return craft()->upvote_query->tally($entry->id);
		}
	}

}
