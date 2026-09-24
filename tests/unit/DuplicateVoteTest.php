<?php
namespace doublesecretagency\upvote\tests\unit;

use PHPUnit\Framework\TestCase;

/**
 * Regression tests for duplicate anonymous votes.
 *
 * Before the fix, _updateUserHistoryCookie() always returned true, so the
 * server accepted a repeat anonymous vote on the same element and counted it
 * again (only the front-end JS prevented it). The logged-in path already
 * rejected duplicates via _updateUserHistoryDatabase().
 *
 * The fix has two halves, and both are load-bearing:
 *
 * 1. _updateUserHistoryCookie() bails with false when the item is already in
 *    the anonymous history.
 * 2. castVote() only writes $upvote->history[$itemKey] AFTER the vote has been
 *    accepted. For anonymous users, $history is a reference to
 *    $anonymousHistory (see UpvoteService::init()), so writing it first would
 *    make the duplicate check in (1) reject every vote, including the first.
 *
 * No Craft bootstrap here, so these are source-level tests. End-to-end
 * behavior (duplicate rejected, tally unchanged, swap and remove still work,
 * revote after removal accepted) was verified in the sandbox with curl, in
 * both anonymous and requireLogin modes.
 */
class DuplicateVoteTest extends TestCase
{
    private string $voteSource;
    private string $upvoteServiceSource;
    private string $controllerSource;

    protected function setUp(): void
    {
        $root = dirname(__DIR__, 2);

        foreach ([
            'voteSource'          => 'src/services/Vote.php',
            'upvoteServiceSource' => 'src/services/UpvoteService.php',
            'controllerSource'    => 'src/controllers/VoteController.php',
        ] as $property => $relative) {
            $path = "{$root}/{$relative}";
            $this->assertFileExists($path);
            $this->{$property} = file_get_contents($path);
        }
    }

    // ========================================================================= //
    // The anonymous path rejects duplicates
    // ========================================================================= //

    public function testCookiePathBailsWhenItemIsAlreadyInHistory(): void
    {
        $body = $this->_methodBody($this->voteSource, '_updateUserHistoryCookie');

        $this->assertMatchesRegularExpression(
            '/if\s*\(isset\(\$upvote->anonymousHistory\[\$item\]\)\)\s*\{\s*return false;\s*\}/',
            $body
        );
    }

    /**
     * The check has to run before the vote is written, or it would always see
     * the vote it just added.
     */
    public function testCookiePathChecksBeforeWritingOrSaving(): void
    {
        $body = $this->_methodBody($this->voteSource, '_updateUserHistoryCookie');

        $check = strpos($body, 'isset($upvote->anonymousHistory[$item])');
        $write = strpos($body, '$upvote->anonymousHistory[$item] = $vote;');
        $save  = strpos($body, '$this->saveUserHistoryCookie();');

        $this->assertNotFalse($check);
        $this->assertNotFalse($write);
        $this->assertNotFalse($save);
        $this->assertLessThan($write, $check);
        $this->assertLessThan($save, $check);
    }

    /**
     * Parity with the logged-in path, which already had this check.
     */
    public function testDatabasePathStillRejectsDuplicates(): void
    {
        $body = $this->_methodBody($this->voteSource, '_updateUserHistoryDatabase');

        $this->assertMatchesRegularExpression(
            '/if\s*\(isset\(\$history\[\$item\]\)\)\s*\{\s*return false;\s*\}/',
            $body
        );
    }

    /**
     * Both paths turn a false into the "already voted" message.
     */
    public function testCastVoteReturnsAlreadyVotedForBothPaths(): void
    {
        $body = $this->_methodBody($this->voteSource, 'castVote');

        $this->assertMatchesRegularExpression(
            '/_updateUserHistoryDatabase\([^)]*\)\)\s*\{\s*return \$this->alreadyVoted;/',
            $body
        );
        $this->assertMatchesRegularExpression(
            '/_updateUserHistoryCookie\([^)]*\)\)\s*\{\s*return \$this->alreadyVoted;/',
            $body
        );
    }

    // ========================================================================= //
    // castVote() only records history once the vote is accepted
    // ========================================================================= //

    /**
     * The half of the fix most likely to be undone by a tidy-up: moving the
     * history write back to the top of castVote() silently rejects every
     * anonymous vote.
     */
    public function testHistoryIsWrittenOnlyAfterBothPathsAccept(): void
    {
        $body = $this->_methodBody($this->voteSource, 'castVote');

        $write        = strpos($body, '$upvote->history[$itemKey] = $vote;');
        $lastRejected = strrpos($body, 'return $this->alreadyVoted;');
        $totals       = strpos($body, '$this->_updateElementTotals(');

        $this->assertNotFalse($write, 'castVote() should still record the vote in history');
        $this->assertSame(1, substr_count($body, '$upvote->history[$itemKey] = $vote;'));
        $this->assertGreaterThan($lastRejected, $write);
        $this->assertLessThan($totals, $write);
    }

    /**
     * Before-vote listeners see the history as it was before this vote.
     */
    public function testHistoryIsNotWrittenBeforeTheBeforeVoteEvent(): void
    {
        $body = $this->_methodBody($this->voteSource, 'castVote');

        $event = strpos($body, 'EVENT_BEFORE_VOTE');
        $write = strpos($body, '$upvote->history[$itemKey] = $vote;');

        $this->assertNotFalse($event);
        $this->assertGreaterThan($event, $write);
    }

    /**
     * Documents why the ordering matters: $history aliases $anonymousHistory
     * by reference when login is not required.
     */
    public function testHistoryAliasesAnonymousHistoryByReference(): void
    {
        $body = $this->_methodBody($this->upvoteServiceSource, 'init');

        $this->assertStringContainsString('$this->history =& $this->anonymousHistory;', $body);
    }

    // ========================================================================= //
    // Swap and remove are unaffected
    // ========================================================================= //

    /**
     * Swap removes the existing vote first, so the duplicate check never
     * blocks the opposite vote cast immediately after.
     */
    public function testSwapRemovesBeforeCasting(): void
    {
        $body = $this->_methodBody($this->controllerSource, 'actionSwap');

        $remove = strpos($body, '->removeVote(');
        $cast   = strpos($body, '$this->_castVote(');

        $this->assertNotFalse($remove);
        $this->assertNotFalse($cast);
        $this->assertLessThan($cast, $remove);
    }

    /**
     * Removing a vote clears it from the anonymous history, so the element can
     * be voted on again afterward.
     */
    public function testRemoveClearsTheAnonymousHistoryItem(): void
    {
        $body = $this->_methodBody($this->voteSource, '_removeVoteFromCookie');

        $this->assertStringContainsString('unset($upvote->anonymousHistory[$item]);', $body);
    }

    // ========================================================================= //
    // Helpers
    // ========================================================================= //

    /**
     * Extract a method's body by matching braces from its declaration.
     */
    private function _methodBody(string $source, string $method): string
    {
        $found = preg_match(
            '/function\s+' . preg_quote($method, '/') . '\s*\([^)]*\)[^{]*\{/',
            $source,
            $m,
            PREG_OFFSET_CAPTURE
        );
        $this->assertSame(1, $found, "Method {$method}() should exist");

        // Walk forward from the opening brace until it balances
        $start = $m[0][1] + strlen($m[0][0]);
        $depth = 1;
        $length = strlen($source);
        for ($i = $start; $i < $length && $depth > 0; $i++) {
            if ('{' === $source[$i]) {
                $depth++;
            } elseif ('}' === $source[$i]) {
                $depth--;
            }
        }

        return substr($source, $start, $i - $start - 1);
    }
}
