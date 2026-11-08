<?php

namespace App\Support;

use InvalidArgumentException;

final class SubRipCaption
{
    public static function toWebVtt(string $contents): string
    {
        if (str_contains($contents, "\0") || ! mb_check_encoding($contents, 'UTF-8')) {
            throw new InvalidArgumentException('Caption files must contain valid UTF-8 text.');
        }

        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;
        $contents = str_replace(["\r\n", "\r"], "\n", $contents);
        $blocks = preg_split('/\n[ \t]*\n+/', trim($contents));
        $timestamp = '([0-9]{2,6}:[0-5][0-9]:[0-5][0-9],[0-9]{3})';
        $cues = [];
        $previousStart = -1;

        foreach ($blocks as $block) {
            $lines = explode("\n", $block);
            if (count($lines) < 3
                || ! ctype_digit(trim($lines[0]))
                || ! preg_match('/^'.$timestamp.'[ \t]+-->[ \t]+'.$timestamp.'[ \t]*$/', $lines[1], $matches)) {
                throw new InvalidArgumentException('The SRT file must contain numbered cues with valid start and end times.');
            }

            $start = self::milliseconds($matches[1]);
            $end = self::milliseconds($matches[2]);
            $text = implode("\n", array_slice($lines, 2));
            if ($end <= $start || $start < $previousStart || trim($text) === '' || str_contains($text, '-->')) {
                throw new InvalidArgumentException('SRT cues must have text, ordered start times, and end times after their start.');
            }

            $previousStart = $start;
            $cues[] = trim($lines[0])."\n".str_replace(',', '.', $matches[1]).' --> '.str_replace(',', '.', $matches[2])."\n".$text;
        }

        return "WEBVTT\n\n".implode("\n\n", $cues)."\n";
    }

    private static function milliseconds(string $timestamp): int
    {
        [$hours, $minutes, $seconds, $milliseconds] = sscanf($timestamp, '%d:%d:%d,%d');

        return (($hours * 3600 + $minutes * 60 + $seconds) * 1000) + $milliseconds;
    }
}
