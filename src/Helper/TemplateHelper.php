<?php
declare(strict_types=1);

namespace HashOver\Helper;

use HashOver\Setup;

final class TemplateHelper
{
    private Setup $setup;

    public function __construct(Setup $setup)
    {
        $this->setup = $setup;
    }

    /**
     * Returns pseudo-namespace prefixed string
     */
    public function prefix(string $id = '', bool $template = true): string
    {
        // Return template prefix in JavaScript mode
        if ($template && $this->mode !== 'php') {
            return '{hashover}-' . $id; // @todo
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
}