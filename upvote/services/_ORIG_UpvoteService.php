<?php
namespace Craft;

class UpvoteService extends BaseApplicationComponent
{

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

}