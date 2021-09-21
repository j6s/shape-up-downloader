<?php

declare(strict_types=1);

namespace J6s\ShapeUpDownloader\Service;

use function Safe\preg_replace;

class RegexService
{
    /**
     * `preg_replace` with type guarantees.
     */
    public function replace(string $pattern, string $replacement, string $subject): string
    {
        $result = preg_replace($pattern, $replacement, $subject);

        if (is_string($result)) {
            return $result;
        }

        return implode($replacement);
    }
}
