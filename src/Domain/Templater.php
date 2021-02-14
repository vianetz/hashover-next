<?php
declare(strict_types=1);

// Copyright (C) 2015-2019 Jacob Barkdull
// This file is part of HashOver.
//
// HashOver is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// HashOver is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with HashOver.  If not, see <http://www.gnu.org/licenses/>.

namespace HashOver\Domain;

use HashOver\Setup;
use Latte\Engine;

final class Templater
{
    private Engine $latte;
    private Setup $setup;

    public function __construct(Setup $setup, Engine $latte)
    {
        $this->setup = $setup;
        $this->latte = $latte;
    }

    /**
     * @throws \Exception
     */
    public function loadFile(string $file): string
    {
        $path = $this->setup->getAbsolutePath('public/' . $this->setup->getThemePath($file, false));

        // Attempt to read template HTML file
        $content = @file_get_contents($path);

        // Check if template file read successfully
        if ($content !== false) {
            // If so, return trimmed HTML template
            return trim($content);
        }

        throw new \Exception('Failed to load template file "' . $path . '".');
    }

    public function parseTemplate(string $fileName, array $templateVars = array()): string
    {
        return $this->latte->renderToString($fileName, $templateVars);
    }

    public function parseTheme($file, array $template = []): string
    {
        $path = $this->setup->getAbsolutePath('public/' . $this->setup->getThemePath($file, false));
        return $this->parseTemplate($path, $template);
    }
}
