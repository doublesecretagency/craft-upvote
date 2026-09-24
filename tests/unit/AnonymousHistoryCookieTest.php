<?php
namespace doublesecretagency\upvote\tests\unit;

use PHPUnit\Framework\TestCase;

/**
 * Regression tests for issue #43: the VoteHistory cookie must only be set
 * once an anonymous visitor actually casts (or removes) a vote.
 *
 * Upvote::init() calls UpvoteService::getAnonymousHistory() on EVERY web
 * request. Before the fix, a visitor with no VoteHistory cookie had an empty
 * one written back on their first request, so every first-time visitor got a
 * Set-Cookie header. That defeats full-page caching (Cloudflare will not
 * cache a response that sets a cookie), which is what #18 originally tried
 * to fix for the "empty cookie already exists" case.
 *
 * There is no Craft bootstrap in this suite, so the request/response cycle
 * cannot be exercised here. These are source-level tests pinning the shape
 * that guarantees the behavior: the per-request read never writes, and the
 * only writers are the vote cast/remove paths in Vote.php. End-to-end cookie
 * behavior was verified by driving the sandbox with curl (fresh visitor,
 * first vote, returning visitor, removal back to empty, requireLogin on).
 */
class AnonymousHistoryCookieTest extends TestCase
{
    private string $pluginRoot;
    private string $upvoteServiceSource;
    private string $voteSource;
    private string $pluginSource;

    protected function setUp(): void
    {
        $this->pluginRoot = dirname(__DIR__, 2);

        $this->upvoteServiceSource = $this->_read('src/services/UpvoteService.php');
        $this->voteSource          = $this->_read('src/services/Vote.php');
        $this->pluginSource        = $this->_read('src/Upvote.php');
    }

    // ========================================================================= //
    // The per-request read never writes a cookie
    // ========================================================================= //

    /**
     * The core regression. getAnonymousHistory() runs on every request, so if
     * it ever calls saveUserHistoryCookie() again, every visitor gets a cookie.
     */
    public function testGetAnonymousHistoryNeverSavesTheCookie(): void
    {
        $body = $this->_methodBody($this->upvoteServiceSource, 'getAnonymousHistory');

        $this->assertStringNotContainsString('saveUserHistoryCookie', $body);
    }

    /**
     * Guard against a sneakier reintroduction: writing the response cookie
     * directly instead of going through the helper.
     */
    public function testGetAnonymousHistoryNeverTouchesResponseCookies(): void
    {
        $body = $this->_methodBody($this->upvoteServiceSource, 'getAnonymousHistory');

        $this->assertStringNotContainsString('getResponse()', $body);
        $this->assertStringNotContainsString('new Cookie', $body);
    }

    /**
     * With no cookie present, the history still has to be initialized to an
     * empty array, or later reads of $history (aliased by reference in init())
     * would see stale data.
     */
    public function testMissingCookieInitializesEmptyHistory(): void
    {
        $body = $this->_methodBody($this->upvoteServiceSource, 'getAnonymousHistory');

        $this->assertMatchesRegularExpression(
            '/\}\s*else\s*\{[^}]*\$this->anonymousHistory\s*=\s*\[\];[^}]*\}/',
            $body
        );
    }

    /**
     * An existing cookie is read and decoded, never rewritten (the #18 case).
     */
    public function testExistingCookieIsDecodedIntoHistory(): void
    {
        $body = $this->_methodBody($this->upvoteServiceSource, 'getAnonymousHistory');

        $this->assertMatchesRegularExpression(
            '/if\s*\(\$cookies->has\(\$this->userCookie\)\)/',
            $body
        );
        $this->assertMatchesRegularExpression(
            '/\$this->anonymousHistory\s*=\s*Json::decode\(\$cookieValue\);/',
            $body
        );
    }

    /**
     * Documents WHY the read path matters: it is wired to every plugin boot.
     * If this ever moves somewhere lazier, the regression surface shrinks, but
     * the tests above should still hold.
     */
    public function testPluginInitLoadsAnonymousHistoryOnEveryRequest(): void
    {
        $body = $this->_methodBody($this->pluginSource, 'init');

        $this->assertStringContainsString('$this->upvote->getAnonymousHistory();', $body);
    }

    // ========================================================================= //
    // The cookie IS written when a vote is cast or removed
    // ========================================================================= //

    /**
     * Without this, removing the read-path write would silently break anonymous
     * voting entirely: votes would never persist between requests.
     */
    public function testCastingAnAnonymousVoteSavesTheCookie(): void
    {
        $body = $this->_methodBody($this->voteSource, '_updateUserHistoryCookie');

        $this->assertMatchesRegularExpression(
            '/\$upvote->anonymousHistory\[\$item\]\s*=\s*\$vote;/',
            $body
        );
        $this->assertStringContainsString('$this->saveUserHistoryCookie();', $body);
    }

    /**
     * castVote() routes anonymous votes (requireLogin off) to the cookie path.
     */
    public function testCastVoteUsesCookiePathWhenLoginIsNotRequired(): void
    {
        $body = $this->_methodBody($this->voteSource, 'castVote');

        $this->assertMatchesRegularExpression(
            '/if\s*\(\$settings->requireLogin\)\s*\{[\s\S]*?_updateUserHistoryDatabase[\s\S]*?\}\s*else\s*\{[\s\S]*?_updateUserHistoryCookie/',
            $body
        );
    }

    /**
     * Removing a vote rewrites the cookie, but only when there was something
     * to remove. A removal attempt by a visitor with no history must not
     * create a cookie either.
     */
    public function testRemovingAVoteSavesTheCookieOnlyAfterFindingIt(): void
    {
        $body = $this->_methodBody($this->voteSource, '_removeVoteFromCookie');

        $bailEmpty   = strpos($body, 'if (!$upvote->anonymousHistory)');
        $bailMissing = strpos($body, 'if (!isset($upvote->anonymousHistory[$item]))');
        $save        = strpos($body, '$this->saveUserHistoryCookie();');

        $this->assertNotFalse($bailEmpty, 'Should bail when there is no anonymous history');
        $this->assertNotFalse($bailMissing, 'Should bail when the item is not in the history');
        $this->assertNotFalse($save, 'Should save the cookie after removing the item');
        $this->assertLessThan($save, $bailEmpty);
        $this->assertLessThan($save, $bailMissing);
    }

    // ========================================================================= //
    // Nothing else in the plugin writes the cookie
    // ========================================================================= //

    /**
     * The strongest guard: saveUserHistoryCookie() may only be called from the
     * two vote-driven paths in Vote.php. A new caller anywhere else in src/
     * (a controller, the Twig variable, a service init) fails this test, which
     * is the prompt to check it does not fire on requests with no vote.
     */
    public function testSaveUserHistoryCookieIsOnlyCalledFromVotePaths(): void
    {
        // Collect every call site across the plugin source
        $callers = [];
        foreach ($this->_phpFiles() as $relative) {
            $source = $this->_read($relative);
            if (preg_match_all('/->saveUserHistoryCookie\(\)/', $source, $m)) {
                $callers[$relative] = count($m[0]);
            }
        }

        $this->assertSame(['src/services/Vote.php' => 2], $callers);

        // And within Vote.php, those two calls live in the cast and remove helpers
        $this->assertStringContainsString(
            'saveUserHistoryCookie',
            $this->_methodBody($this->voteSource, '_updateUserHistoryCookie')
        );
        $this->assertStringContainsString(
            'saveUserHistoryCookie',
            $this->_methodBody($this->voteSource, '_removeVoteFromCookie')
        );
    }

    /**
     * The cookie name is part of the public contract (issue #43 names it).
     * Read from source rather than via reflection, so the assertion always
     * describes the same file the other tests read.
     */
    public function testCookieIsNamedVoteHistory(): void
    {
        $this->assertMatchesRegularExpression(
            "/public string \\\$userCookie = 'VoteHistory';/",
            $this->upvoteServiceSource
        );
    }

    // ========================================================================= //
    // Helpers
    // ========================================================================= //

    /**
     * Read a file relative to the plugin root.
     */
    private function _read(string $relative): string
    {
        $path = $this->pluginRoot . '/' . $relative;
        $this->assertFileExists($path);
        return file_get_contents($path);
    }

    /**
     * Every PHP file under src/, relative to the plugin root.
     *
     * @return string[]
     */
    private function _phpFiles(): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->pluginRoot . '/src', \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ('php' === $file->getExtension()) {
                $files[] = substr($file->getPathname(), strlen($this->pluginRoot) + 1);
            }
        }
        sort($files);
        return $files;
    }

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
