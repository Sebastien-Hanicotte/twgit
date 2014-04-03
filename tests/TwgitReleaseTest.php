<?php

/**
 * @package Tests
 * @author Geoffroy Aubry <geoffroy.aubry@hi-media.com>
 * @author Geoffroy Letournel <gletournel@hi-media.com>
 * @author Sebastien Hanicotte <shanicotte@hi-media.com>
 */
class TwgitReleaseTest extends TwgitTestCase
{

    /**
     * @shcovers inc/twgit_release.inc.sh::cmd_reset
     */
    public function testReset_ThrowExceptionWhenReleaseParameterMissing ()
    {
        $this->_remoteExec('git init');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);

        $this->setExpectedException('RuntimeException', 'Missing argument <release>!');
        $this->_localExec(TWGIT_EXEC . ' release reset');
    }

    /**
     * @shcovers inc/twgit_release.inc.sh::cmd_reset
     */
    public function testReset_ThrowExceptionWhenReleaseNotFound ()
    {
        $this->_remoteExec('git init');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);

        $this->setExpectedException('RuntimeException', "Remote branch '" . self::_remote('release-9.9.9') . "' not found!");
        $this->_localExec(TWGIT_EXEC . ' release reset 9.9.9');
    }

    /**
     * @shcovers inc/twgit_release.inc.sh::cmd_reset
     */
    public function testReset_WithMinorRelease ()
    {
        $this->_remoteExec('git init');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);
        $this->_localExec(TWGIT_EXEC . ' release start -I');

        $this->_localExec(TWGIT_EXEC . ' release reset 1.3.0 -I');
        $sMsg = $this->_localExec(TWGIT_EXEC . ' release list');
        $this->assertContains("Release: " . self::_remote('release-1.4.0'), $sMsg);
    }

    /**
     * @shcovers inc/twgit_release.inc.sh::cmd_reset
     */
    public function testReset_WithMajorRelease ()
    {
        $this->_remoteExec('git init');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);
        $this->_localExec(TWGIT_EXEC . ' release start -I');

        $this->_localExec(TWGIT_EXEC . ' release reset 1.3.0 -IM');
        $sMsg = $this->_localExec(TWGIT_EXEC . ' release list');
        $this->assertContains("Release: " . self::_remote('release-2.0.0'), $sMsg);
    }

    public function testReset_WithPrefix ()
    {
        $this->_remoteExec('git init');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);
        $this->_localExec(TWGIT_EXEC . ' release start -I');

        $sMsg = $this->_localExec(TWGIT_EXEC . ' release reset release-1.3.0 -IM');
    $this->assertContains("Assume release was '1.3.0' instead of 'release-1.3.0'", $sMsg);
    }

    /**
     */
    public function testStart_WithAmbiguousRef ()
    {
        $this->_remoteExec('git init');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);
        $this->_localExec('git branch v1.2.3 v1.2.3');

        $sMsg = $this->_localExec(TWGIT_EXEC . ' release start -I');
        $this->assertNotContains("warning: refname 'v1.2.3' is ambiguous.", $sMsg);
        $this->assertNotContains("fatal: Ambiguous object name: 'v1.2.3'.", $sMsg);
    }

    /**
     */
    public function testStart_ThrowExceptionWhenSpecifiedTagAlreadyExists ()
    {
        $this->_remoteExec('git init');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);
        $this->_localExec('git branch v1.2.3 v1.2.3');

        $this->setExpectedException('RuntimeException', "/!\ Tag 'v1.2.3' already exists! Try: twgit tag list");
        $sMsg = $this->_localExec(TWGIT_EXEC . ' release start -I 1.2.3');
    }

    /**
     */
    public function testStart_ThrowExceptionWhenSpecifiedValueIsNotATag ()
    {
        $this->_remoteExec('git init');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);
        $this->_localExec('git branch v1.2.3 v1.2.3');

        $this->setExpectedException(
            'RuntimeException',
            "/!\ Unauthorized tag name: 'toto'! Must use <major.minor.revision> format, e.g. '1.2.3'."
        );
        $sMsg = $this->_localExec(TWGIT_EXEC . ' release start -I toto');
    }

    /**
     */
    public function testStart_WithSpecifiedTag ()
    {
        $this->_remoteExec('git init');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);
        $this->_localExec('git branch v1.2.3 v1.2.3');

        $sMsg = $this->_localExec(TWGIT_EXEC . ' release start -I 10.0.2');
        $sMsg = $this->_localExec(TWGIT_EXEC . ' release list');
        $this->assertContains("Release: " . self::_remote('release-10.0.2'), $sMsg);
    }

    public function testStart_WithSpecifiedTagAndPrefix ()
    {
        $this->_remoteExec('git init');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);
        $this->_localExec('git branch v1.2.3 v1.2.3');

        $sMsg = $this->_localExec(TWGIT_EXEC . ' release start -I release-10.0.2');
    $this->assertContains("Assume release was '10.0.2' instead of 'release-10.0.2'", $sMsg);
    }

    /**
     */
    public function testStart_WithNoSpecifiedTag ()
    {
        $this->_remoteExec('git init');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);
        $this->_localExec('git branch v1.2.3 v1.2.3');

        $sMsg = $this->_localExec(TWGIT_EXEC . ' release start -I');
        $sMsg = $this->_localExec(TWGIT_EXEC . ' release list');
        $this->assertContains("Release: " . self::_remote('release-1.3.0'), $sMsg);
    }

    /**
     * @shcovers inc/common.inc.sh::is_initial_author
     */
    public function testStart_WithExistentReleaseSameAuthor ()
    {
        $this->_remoteExec('git init');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);
        $this->_localExec(TWGIT_EXEC . ' release start -I');
        $this->_localExec('git checkout $TWGIT_STABLE');

        $userName = $this->_localExec('git config user.name');
        $userEmail = $this->_localExec('git config user.email');

        $sResult = $this->_localExec(TWGIT_EXEC . ' release start -I');
        $sExpected = "Remote release '" . self::ORIGIN . "/release-1.3.0' was started by $userName <$userEmail>.";

        $this->assertContains("Check initial author...", $sResult);
        $this->assertNotContains($sExpected, $sResult);
    }

    /**
     * @shcovers inc/common.inc.sh::is_initial_author
     */
    public function testStart_WithExistentReleaseOtherAuthor ()
    {
        $this->_remoteExec('git init');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);
        $this->_localExec(TWGIT_EXEC . ' release start -I');
        $this->_localExec('git checkout $TWGIT_STABLE');

        $userName = $this->_localExec('git config user.name');
        $userEmail = $this->_localExec('git config user.email');

        $this->_localExec("git config --local user.name 'Other Name'");
        $this->_localExec("git config --local user.email 'Other@Email.com'");

        $sResult = $this->_localExec(TWGIT_EXEC . ' release start -I');
        $sExpected = "Remote release '" . self::ORIGIN . "/release-1.3.0' was started by $userName <$userEmail>.";

        $this->_localExec("git config --local --unset user.name");
        $this->_localExec("git config --local --unset user.email");

        $this->assertContains("Check initial author...", $sResult);
        $this->assertContains($sExpected, $sResult);
    }

    /**
     * Currently just check the tag annotation.
     */
    public function testFinish_WithMinorRelease ()
    {
        $this->_remoteExec('git init');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);
        $this->_localExec(TWGIT_EXEC . ' release start -I');

        $this->_localExec('echo \'2;The subject\' > .twgit_features_subject');
        $this->_localExec(TWGIT_EXEC . ' feature start 1');
        $this->_localExec(TWGIT_EXEC . ' feature start 2');
        $this->_localExec('git merge --no-ff feature-1; git commit --allow-empty -m "empty"; git push ' . self::ORIGIN . ';');
        $this->_localExec(TWGIT_EXEC . ' feature start 3');
        $this->_localExec(TWGIT_EXEC . ' feature start 4');

        $this->_localExec(TWGIT_EXEC . ' feature merge-into-release 2');
        $this->_localExec(TWGIT_EXEC . ' feature merge-into-release 4');
        $this->_localExec(TWGIT_EXEC . ' release finish -I');

        $sMsg = $this->_localExec('git show v1.3.0');
        $this->assertContains(
            "\n[twgit] Release finish: release-1.3.0"
            . "\n[twgit] Contains feature-4"
            . "\n[twgit] Contains feature-2: \"The subject\""
            . "\n[twgit] Contains feature-1\n\n"
            , $sMsg);
        $this->assertContains("Merge branch 'release-1.3.0' into " . self::STABLE, $sMsg);
    }

    public function testFinish_WithPrefix ()
    {
        $this->_remoteExec('git init');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);
        $sMsg = $this->_localExec(TWGIT_EXEC . ' release start release-1.3.0 -I');
        $this->assertContains("Assume release was '1.3.0' instead of 'release-1.3.0'", $sMsg);
        $sMsg = $this->_localExec(TWGIT_EXEC . ' release finish v1.3.0 -I');
        $this->assertContains("Assume tag was '1.3.0' instead of 'v1.3.0'", $sMsg);

        $sMsg = $this->_localExec('git show v1.3.0');
        $this->assertContains(
            "\n[twgit] Release finish: release-1.3.0"
            , $sMsg);
        $this->assertContains("Merge branch 'release-1.3.0' into " . self::STABLE, $sMsg);
    }

    /**
     * @shcovers inc/common.inc.sh::assert_clean_stable_branch_and_checkout
     */
    public function testFinish_ThrowExceptionWhenExtraCommitIntoStable ()
    {
        $this->_remoteExec('git init');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);
        $this->_localExec(TWGIT_EXEC . ' release start -I');

        $this->_localExec('git checkout ' . self::STABLE);
        $this->_localExec('git commit --allow-empty -m "extra commit!"');

        $this->setExpectedException(
            'RuntimeException',
            "Local '" . self::STABLE . "' branch is ahead of '" . self::$_remoteStable . "'! Commits on '" . self::STABLE . "' are out of process."
                . " Try: git checkout " . self::STABLE . " && git reset " . self::$_remoteStable
        );
        $sMsg = $this->_localExec(TWGIT_EXEC . ' release finish -I');
    }

    /**
     * @shcovers inc/common.inc.sh::assert_clean_stable_branch_and_checkout
     */
    public function testFinish_WithExtraCommitIntoStableThenReset ()
    {
        $this->_remoteExec('git init');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);
        $this->_localExec(TWGIT_EXEC . ' release start -I');

        $this->_localExec('git checkout ' . self::STABLE);
        $this->_localExec('git commit --allow-empty -m "extra commit!"');
        $this->_localExec('git checkout ' . self::STABLE . ' && git reset ' . self::$_remoteStable);

        $this->_localExec(TWGIT_EXEC . ' release finish -I');
        $sMsg = $this->_localExec('git tag');
        $this->assertContains('v1.3.0', $sMsg);
    }

    /*public function testFinish_ThrowExceptionWhenHotfixStartedAndFinishedAfterReleaseStartAndNotMerged ()
    {
        $this->_remoteExec('git init');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);
        $this->_localExec(TWGIT_EXEC . ' release start -I');
        $this->_localExec('cd ' . TWGIT_REPOSITORY_SECOND_LOCAL_DIR
            . ' && git init && git remote add ' . self::ORIGIN . ' ' . TWGIT_REPOSITORY_ORIGIN_DIR
            . ' && ' . TWGIT_EXEC . ' hotfix start -I');
        $this->_localExec('cd ' . TWGIT_REPOSITORY_SECOND_LOCAL_DIR
            . ' && ' . TWGIT_EXEC . ' hotfix finish -I');

        $this->setExpectedException(
            'RuntimeException',
            "/!\ You must merge the last tag into this release before close it."
            . " In release-1.3.0 branch: git merge --no-ff v1.2.4, then: git push " . self::ORIGIN . " release-1.3.0"
        );
        $sMsg = $this->_localExec(TWGIT_EXEC . ' release finish -I');
        $sMsg = $this->_localExec('git tag');
        $this->assertContains('v1.3.0', $sMsg);
    }

    public function testFinish_WithHotfixStartedAndFinishedAfterReleaseStartAndMerged ()
    {
        $this->_remoteExec('git init');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);
        $this->_localExec(TWGIT_EXEC . ' release start -I');
        $this->_localExec('cd ' . TWGIT_REPOSITORY_SECOND_LOCAL_DIR
            . ' && git init && git remote add ' . self::ORIGIN . ' ' . TWGIT_REPOSITORY_ORIGIN_DIR
            . ' && ' . TWGIT_EXEC . ' hotfix start -I');
        $this->_localExec('cd ' . TWGIT_REPOSITORY_SECOND_LOCAL_DIR
            . ' && ' . TWGIT_EXEC . ' hotfix finish -I');

        $this->_localExec('git fetch ' . self::ORIGIN . ' && git merge v1.2.4 && git push origin release-1.3.0');
        $sMsg = $this->_localExec(TWGIT_EXEC . ' release finish -I');
        $this->assertContains('v1.3.0', $sMsg);
    }*/

    /**
     * @shcovers inc/common.inc.sh::assert_clean_stable_branch_and_checkout
     */
    public function testRemove_ThrowExceptionWhenExtraCommitIntoStable ()
    {
        $this->_remoteExec('git init');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);
        $this->_localExec(TWGIT_EXEC . ' release start -I');

        $this->_localExec('git checkout ' . self::STABLE);
        $this->_localExec('git commit --allow-empty -m "extra commit!"');

        $this->setExpectedException(
            'RuntimeException',
            "Local '" . self::STABLE . "' branch is ahead of '" . self::$_remoteStable . "'! Commits on '" . self::STABLE . "' are out of process."
                . " Try: git checkout " . self::STABLE . " && git reset " . self::$_remoteStable
        );
        $sMsg = $this->_localExec(TWGIT_EXEC . ' release remove 1.3.0');
    }

    /**
     * @shcovers inc/common.inc.sh::assert_clean_stable_branch_and_checkout
     */
    public function testRemove_WithExtraCommitIntoStableThenReset ()
    {
        $this->_remoteExec('git init');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);
        $this->_localExec(TWGIT_EXEC . ' release start -I');

        $this->_localExec('git checkout ' . self::STABLE);
        $this->_localExec('git commit --allow-empty -m "extra commit!"');
        $this->_localExec('git checkout ' . self::STABLE . ' && git reset ' . self::$_remoteStable);

        $this->_localExec(TWGIT_EXEC . ' release remove 1.3.0');
        $sMsg = $this->_localExec('git tag');
        $this->assertContains('v1.3.0', $sMsg);
    }

    public function testRemove_WithPrefix ()
    {
        $this->_remoteExec('git init');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);
        $this->_localExec(TWGIT_EXEC . ' release start -I');

        $this->_localExec('git checkout ' . self::STABLE);
        $this->_localExec('git commit --allow-empty -m "extra commit!"');
        $this->_localExec('git checkout ' . self::STABLE . ' && git reset ' . self::$_remoteStable);

        $sMsg = $this->_localExec(TWGIT_EXEC . ' release remove release-1.3.0');
        $this->assertContains("Assume release was '1.3.0' instead of 'release-1.3.0'", $sMsg);
        $sMsg = $this->_localExec('git tag');
        $this->assertContains('v1.3.0', $sMsg);
    }

    /**
     * @shcovers inc/common.inc.sh::assert_clean_stable_branch_and_checkout
     */
    public function testReset_ThrowExceptionWhenExtraCommitIntoStable ()
    {
        $this->_remoteExec('git init');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);
        $this->_localExec(TWGIT_EXEC . ' release start -I');

        $this->_localExec('git checkout ' . self::STABLE);
        $this->_localExec('git commit --allow-empty -m "extra commit!"');

        $this->setExpectedException(
            'RuntimeException',
            "Local '" . self::STABLE . "' branch is ahead of '" . self::$_remoteStable . "'! Commits on '" . self::STABLE . "' are out of process."
                . " Try: git checkout " . self::STABLE . " && git reset " . self::$_remoteStable
        );
        $sMsg = $this->_localExec(TWGIT_EXEC . ' release reset 1.3.0 -I');
    }

    /**
     * @shcovers inc/common.inc.sh::assert_clean_stable_branch_and_checkout
     */
    public function testReset_WithExtraCommitIntoStableThenReset ()
    {
        $this->_remoteExec('git init');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);
        $this->_localExec(TWGIT_EXEC . ' release start -I');

        $this->_localExec('git checkout ' . self::STABLE);
        $this->_localExec('git commit --allow-empty -m "extra commit!"');
        $this->_localExec('git checkout ' . self::STABLE . ' && git reset ' . self::$_remoteStable);

        $this->_localExec(TWGIT_EXEC . ' release reset 1.3.0 -I');
        $sMsg = $this->_localExec('git tag');
        $this->assertContains('v1.3.0', $sMsg);
    }

    /**
     * @dataProvider providerTestListAboutBranchesOutOfProcess
     */
    public function testList_AboutBranchesOutOfProcess ($sLocalCmd, $sExpectedContent, $sNotExpectedContent)
    {
        $this->_remoteExec('git init && git commit --allow-empty -m "-" && git checkout -b feature-currentOfNonBareRepo');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);
        $this->_localExec('cd ' . TWGIT_REPOSITORY_SECOND_REMOTE_DIR . ' && git init');
        $this->_localExec('git remote add second ' . TWGIT_REPOSITORY_SECOND_REMOTE_DIR);

        $this->_localExec($sLocalCmd);
        $sMsg = $this->_localExec(TWGIT_EXEC . ' release list');
        if ( ! empty($sExpectedContent)) {
            $this->assertContains($sExpectedContent, $sMsg);
        }
        if ( ! empty($sNotExpectedContent)) {
            $this->assertNotContains($sNotExpectedContent, $sMsg);
        }
    }

    /**
     */
    public function testList_WithFullColoredGit ()
    {
        $this->_remoteExec('git init');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);

        $this->_localExec(TWGIT_EXEC . ' release start -I');
        $this->_localExec(
            "git config color.branch always\n"
            . "git config color.diff always\n"
            . "git config color.interactive always\n"
            . "git config color.status always\n"
            . "git config color.ui always\n"
        );

        $sMsg = $this->_localExec(TWGIT_EXEC . ' release list');
        $sExpected = "(i) Remote release NOT merged into '" . self::STABLE . "':\n"
                   . "Release: " . self::_remote('release-1.3.0') . " (from v1.2.3)";
        $this->assertContains($sExpected, $sMsg);
    }

    public function providerTestListAboutBranchesOutOfProcess ()
    {
        return array(
            array(':', '', 'Following branches are out of process'),
            array(':', '', 'Following local branches are ambiguous'),
            array(
                'git checkout -b feature-X && git push ' . self::ORIGIN . ' feature-X'
                    . ' && git checkout -b release-X && git push ' . self::ORIGIN . ' release-X'
                    . ' && git checkout -b hotfix-X && git push ' . self::ORIGIN . ' hotfix-X'
                    . ' && git checkout -b demo-X && git push ' . self::ORIGIN . ' demo-X'
                    . ' && git checkout -b master && git push ' . self::ORIGIN . ' master'
                    . ' && git checkout -b outofprocess && git push ' . self::ORIGIN . ' outofprocess'
                    . ' && git remote set-head ' . self::ORIGIN . ' ' . self::STABLE,
                "/!\ Following branches are out of process: '" . self::_remote('outofprocess') . "'!",
                'Following local branches are ambiguous'
            ),
            array(
                'git checkout -b outofprocess && git push ' . self::ORIGIN . ' outofprocess && git push second outofprocess'
                    . ' && git checkout -b out2 && git push ' . self::ORIGIN . ' out2 && git push second out2',
                "/!\ Following branches are out of process: '" . self::_remote('out2') . "', '" . self::_remote('outofprocess') . "'!",
                'Following local branches are ambiguous'
            ),
            array(
                'git branch v1.2.3 v1.2.3',
                "/!\ Following local branches are ambiguous: 'v1.2.3'!",
                'Following branches are out of process'
            ),
            array(
                'git checkout -b outofprocess && git push ' . self::ORIGIN . ' outofprocess && git branch v1.2.3 v1.2.3',
                "/!\ Following branches are out of process: '" . self::_remote('outofprocess') . "'!\n"
                    . "/!\ Following local branches are ambiguous: 'v1.2.3'!",
                ''
            ),
        );
    }
}
