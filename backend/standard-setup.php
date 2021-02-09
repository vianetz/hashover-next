<?php
declare(strict_types=1);

// Copyright (C) 2017-2019 Jacob Barkdull
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

namespace HashOver;

// Use UTF-8 character set
ini_set('default_charset', 'UTF-8');

require __DIR__ . '/../vendor/autoload.php';

function setup_autoloader($method = 'echo')
{
    // Register a class autoloader
    spl_autoload_register(function ($uri) {
        $uri = strtolower($uri);

        // Convert to UNIX style
        $uri = str_replace('\\', '/', $uri);

        $file = basename($uri) . '.php';

        @include __DIR__ . '/classes/' . $file;
    });
}
