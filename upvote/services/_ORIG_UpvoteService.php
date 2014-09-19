<?php
namespace Craft;

class UpvoteService extends BaseApplicationComponent
{
    
    public $settings; // Plugin settings

    public $voteRecord;

    private $_ipVotes = array();

    //private $_cookie;
    //private $_cookieName;
    //private $_cookieExpires;

    public function init()
    {
        parent::init();
        $plugin = craft()->plugins->getPlugin('twothumbs');
        if (!$plugin) {
            throw new Exception('Couldn’t find the Two Thumbs plugin!');
        }
        //$this->settings = $plugin->getSettings();
        $this->settings = array(
            'allowDownvotes' => false
        );
        /*
        $this->_cookieName = substr(md5('votes'), -33);
        $this->_cookieExpires = strtotime('Jan. 1, 2099');
        */
        $this->_loadIpVotes();
    }

    // ==================================================== //
    // CALLED VIA TwoThumbs_VoteFieldType::modifyElementsQuery()
    // ==================================================== //

    // Modify fieldtype query
    public function modifyQuery(DbCommand $query, $params)
    {
        // REPLACE HARD-CODED TABLE/PREFIX NAMES
        $where = ':start < UNIX_TIMESTAMP(craft_twothumbs_votes.dateCreated) AND UNIX_TIMESTAMP(craft_twothumbs_votes.dateCreated) < :end';
        $pdo = array(
            ':start' => (array_key_exists('start', $params) ? strtotime($params['start']) :  0),
            ':end'   => (array_key_exists('end', $params)   ? strtotime($params['end'])   :  time()+(60*60*24)),
        );

        $query
            ->join('twothumbs_votes', 'elements.id=craft_twothumbs_votes.elementId')
            ->group('craft_twothumbs_votes.elementId')
            ->addSelect('SUM(vote) AS totalVotes')
        ;

        return $query;
    }

    // ==================================================== //

    /*
    // Most popular item(s) in specified time period
    public function mostPopular($startTime = null, $endTime = null, $limit = 1)
    {

        $where = ':start < UNIX_TIMESTAMP(craft_twothumbs_votes.dateCreated) AND UNIX_TIMESTAMP(craft_twothumbs_votes.dateCreated) < :end'; // !!! HACK FOR X BANDS !!!
        //$where = ':start < UNIX_TIMESTAMP(dateCreated) AND UNIX_TIMESTAMP(dateCreated) < :end';
        $pdo = array(
            ':start' => ($startTime ? strtotime($startTime) :  0),
            ':end'   => ($endTime   ? strtotime($endTime)   :  time()+(60*60*24)),
        );

        $sql = craft()->db->createCommand();

        $sql
            ->select('craft_twothumbs_votes.elementId') // !!! HACK FOR X BANDS !!!
            //->select('elementId')
            ->from('twothumbs_votes')
            ->where($where, $pdo)
            ->group('elementId')
            ->order('SUM(vote) DESC')
            ->limit($limit)
        ;

        // !!!!!!!!
        // HACK FOR X BANDS
        $sql
            ->join('content', 'craft_content.elementId = craft_twothumbs_votes.elementId')
            ->andWhere('field_active = "[\"enabled\"]"')
        ;
        // !!!!!!!!


        // TODO:
        // Replace X Bands hack (probably need fieldtype)

        // TODO:
        // Doesn't include elements with no votes (see X Bands solution)

        $elements = array();
        foreach ($sql->queryAll() as $row) {
            $elements[] = craft()->elements->getElementById($row['elementId']);
        }

        if (!count($elements)) {
            return null;
        } else if (1 == $limit) {
            return $elements[0];
        } else {
            return $elements;
        }
        
    }
    */

    // Number of rows where element ID matches and vote = 1
    public function totalLikes($elementId)
    {
        return TwoThumbs_VoteRecord::model()->countByAttributes(array(
            'elementId' => $elementId,
            'vote'      => 1,
        ));
    }

    // Number of rows where element ID matches and vote = -1
    public function totalDislikes($elementId)
    {
        return TwoThumbs_VoteRecord::model()->countByAttributes(array(
            'elementId' => $elementId,
            'vote'      => -1,
        ));
    }

    // Sum of all rows where element ID matches
    public function totalValue($elementId)
    {
        $voteRecords = TwoThumbs_VoteRecord::model()->findAllByAttributes(array(
            'elementId' => $elementId,
        ));
        if (!empty($voteRecords)) {
            $allVotes = array();
            foreach ($voteRecords as $r) {
                $record = $r->getAttributes();
                $allVotes[] = $record['vote'];
            }
            // Return sum of votes
            return array_sum($allVotes);
        } else {
            // Return zero
            return 0;
        }
    }

    // Value of user's vote for this element
    public function voteValue($elementId)
    {
        //if ($this->_isInCookie($elementId)) {
        //    return $this->_cookie[$elementId];
        if ($this->_isInIpVotes($elementId)) {
            return $this->_ipVotes[$elementId];
        } else {
            return false;
        }
    }

    // Weight of user's vote for this element
    public function voteWeight($vote)
    {
        return (Vote::Dislike == $vote ? -1 : 1);
    }

    // Whether or not user has voted on this element
    public function hasVoted($elementId)
    {
        //return $this->_isInCookie($elementId);
        return $this->_isInIpVotes($elementId);
    }

    // Cast a "Like" vote
    public function addLike($elementId)
    {
        return $this->_recordVote($elementId, Vote::Like);
    }

    // Cast a "Dislike" vote
    public function addDislike($elementId)
    {
        if ($this->settings['allowDownvotes']) {
            return $this->_recordVote($elementId, Vote::Dislike);
        } else {
            return false;
        }
    }

    // Record vote
    private function _recordVote($elementId, $vote)
    {
        if (isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'] != '') 
        {
            $ip_address = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        } else {
            $ip_address = $_SERVER['REMOTE_ADDR'];
        }

        //if (!$this->_isInCookie($elementId)) {
        if (!$this->_isInIpVotes($elementId)) {
            $voteRecord = new TwoThumbs_VoteRecord;
            $voteWeight = $this->voteWeight($vote);
            $attr = array(
                'elementId' => $elementId,
                'vote'      => $voteWeight,
                'ipAddress' => $ip_address,
            );
            $voteRecord->setAttributes($attr, false);
            //$this->_addToCookie($elementId, $voteWeight);
            return $voteRecord->save();
        }
    }

    // Check if vote exists in user data
    private function _isInIpVotes($elementId)
    {
        return array_key_exists($elementId, $this->_ipVotes);
    }
    
    // Get user vote data from cookie
    private function _loadIpVotes()
    {
        if (isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'] != '') 
        {
            $ip_address = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        } else {
            $ip_address = $_SERVER['REMOTE_ADDR'];
        }
        
        $myVotes = TwoThumbs_VoteRecord::model()->findAllByAttributes(array(
            'ipAddress' => $ip_address,
        ));
        foreach ($myVotes as $voteRecord) {
            $this->_ipVotes[$voteRecord->elementId] = (int) $voteRecord->vote;
        }
    }


    /*
    // Save user vote data as cookie
    private function _saveCookie()
    {
        $cookieData = base64_encode(json_encode($this->_cookie));
        return setcookie($this->_cookieName, $cookieData, $this->_cookieExpires, '/');
    }
    
    // Get user vote data from cookie
    private function _loadCookie()
    {
        if (array_key_exists($this->_cookieName, $_COOKIE)) {
            $this->_cookie = $_COOKIE[$this->_cookieName];
            $this->_cookie = base64_decode($this->_cookie);
            $this->_cookie = preg_replace('/%.*$/', '', $this->_cookie);
            $this->_cookie = json_decode($this->_cookie, true);
            return (bool) $this->_cookie;
        } else {
            $this->_cookie = array();
            return $this->_saveCookie();
        }
    }
    // Check if vote exists in user data
    private function _isInCookie($elementId)
    {
        return array_key_exists($elementId, $this->_cookie);
    }

    // Add vote to user data
    private function _addToCookie($elementId, $vote)
    {
        $this->_cookie[$elementId] = $vote;
        return $this->_saveCookie();
    }
    */

}