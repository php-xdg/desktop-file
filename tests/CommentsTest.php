<?php declare(strict_types=1);

namespace Xdg\DesktopFile\Tests;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Xdg\DesktopFile\DesktopFile;

final class CommentsTest extends TestCase
{
    public function testFileComments(): void
    {
        $f = DesktopFile::parse(<<<'INI'
        # group comment
        [Group]
        Key=Value
        # end comment
        INI);
        Assert::assertSame('', $f->getStartComment());
        Assert::assertSame(" end comment\n", $f->getEndComment());
        //
        $f->setStartComment('Start Comment');
        $f->setEndComment('New End Comment');
        $expected = <<<'INI'
        #Start Comment

        # group comment
        [Group]
        Key=Value

        #New End Comment
        INI;
        Assert::assertSame($expected, trim((string)$f));
    }

    public function testGroupComments(): void
    {
        $f = DesktopFile::parse("#comment\n[Group]");
        Assert::assertSame("comment\n", $f->getGroupComment('Group'));
        $f->setGroupComment('Group', 'foo');
        Assert::assertSame("#foo\n[Group]", trim((string)$f));
    }

    public function testKeyComments(): void
    {
        $f = DesktopFile::parse("[G]\n#c\nk=v");
        Assert::assertSame("c\n", $f->getKeyComment('G', 'k'));
        $f->setKeyComment('G', 'k', 'success');
        Assert::assertSame("[G]\n#success\nk=v", trim((string)$f));
    }
}
