<?php declare(strict_types=1);

namespace Xdg\DesktopEntry\KeyFile;

use Xdg\DesktopEntry\KeyFile\Exception\ParseError;
use Xdg\DesktopEntry\KeyFile\Internal\Syntax;

final class KeyFileParser
{
    const FLAG_NONE = 0x0;
    const FLAG_KEEP_TRANSLATIONS = 0x01;
    const FLAG_KEEP_COMMENTS = 0x02;

    public function parse(string $buffer, int $flags = 0): KeyFileInterface
    {
        $keepComments = $flags & self::FLAG_KEEP_COMMENTS;
        $keepTranslations = $flags & self::FLAG_KEEP_TRANSLATIONS;

        $keyFile = new KeyFile();
        $currentGroup = null;
        $currentComment = '';
        $groupComment = '';

        foreach (Syntax::splitLines($buffer) as $lineno => $line) {
            $line = trim($line);
            switch ($line[0] ?? '') {
                case '':
                    if ($keepComments && $currentComment) {
                        $currentComment .= "\n";
                    }
                    break;
                case '#':
                    if ($keepComments) {
                        $currentComment .= substr($line, 1) . "\n";
                    }
                    break;
                case '[':
                    $currentGroup = Syntax::parseGroupHeader($line, $lineno);
                    $groupComment = $currentComment;
                    $currentComment = '';
                    break;
                default:
                    if (!$currentGroup) {
                        throw ParseError::missingGroupHeader();
                    }
                    [$key, $value, $locale] = Syntax::parseKeyValuePair($line, $lineno);
                    $keyFile->setValueUnchecked($currentGroup, $key, $value, $locale);
                    if ($groupComment) {
                        $keyFile->setGroupComment($currentGroup, $groupComment);
                        $groupComment = '';
                    }
                    if ($currentComment) {
                        $keyFile->setKeyComment($currentGroup, $key, $currentComment, $locale);
                        $currentComment = '';
                    }
                    break;
            }
        }

        return $keyFile;
    }
}
