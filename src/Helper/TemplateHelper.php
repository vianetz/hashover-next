<?php
declare(strict_types=1);

namespace HashOver\Helper;

use HashOver\Setup;

final class TemplateHelper
{
    private Setup $setup;
    private string $mode;

    public function __construct(Setup $setup, string $mode = \HashOver::HASHOVER_MODE_JAVASCRIPT)
    {
        $this->setup = $setup;
        $this->mode = $mode;
    }

    /**
     * Returns pseudo-namespace prefixed string
     */
    public function prefix(string $id = '', bool $isPrefixAsTemplate = true): string
    {
        // Return template prefix in JavaScript mode
        if ($isPrefixAsTemplate && $this->mode !== \HashOver::HASHOVER_MODE_PHP) {
            return '[hashover]-' . $id;
        }

        $prefix = 'hashover';

        // Return simple prefix if we're on first instance
        if ($this->setup->instanceNumber > 1) {
            $prefix .= '-' . $this->setup->instanceNumber;
        }

        if (! empty($id)) {
            return $prefix . '-' . $id;
        }

        return $prefix;
    }

    public function suffix(string $id, ?string $permalink = null): string
    {
        if (! empty($permalink)) {
            $id .= '-' . $permalink;
        }

        return $id;
    }

    public function createQueryString(string $href, array $queries = [])
    {
        // Merge given URL queries with existing page URL queries
        $queries = array_merge($this->setup->urlQueryList, $queries);

        // Add URL queries to path if URL has queries
        if (! empty($queries)) {
            $href .= '?' . http_build_query($queries);
        }

        return $href;
    }
}