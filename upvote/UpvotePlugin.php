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

	public function getVersion()
	{
		return '1.2.3 rc 2';
	}

	public function getSchemaVersion()
	{
		return '1.3.0';
	}

	public function getDeveloper()
	{
		return 'Double Secret Agency';
	}

	public function getDeveloperUrl()
	{
		return 'https://www.doublesecretagency.com/plugins';
	}

	public function getDocumentationUrl()
	{
		return 'https://www.doublesecretagency.com/plugins/upvote/docs';
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
			'upvote_voteTally'      => "Vote Tally",
			'upvote_totalVotes'     => "Total Votes",
			'upvote_totalUpvotes'   => "Total Upvotes",
			'upvote_totalDownvotes' => "Total Downvotes",
		);
	}

	public function getEntryTableAttributeHtml(EntryModel $entry, $attribute)
	{
		switch ($attribute) {
			case 'upvote_voteTally':
				return craft()->upvote_query->tally($entry->id);
				break;
			case 'upvote_totalVotes':
				return craft()->upvote_query->totalVotes($entry->id);
				break;
			case 'upvote_totalUpvotes':
				return craft()->upvote_query->totalUpvotes($entry->id);
				break;
			case 'upvote_totalDownvotes':
				return craft()->upvote_query->totalDownvotes($entry->id);
				break;
		}
	}

}
