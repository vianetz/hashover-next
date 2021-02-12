<?php namespace HashOver;

// Copyright (C) 2010-2019 Jacob Barkdull
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

use HashOver\Domain\Translator;

/**
 * @deprecated
 */
class Translations extends \ArrayObject
{
    private $translator;

    public function __construct(Setup $setup)
    {
        $this->translator = new Translator($setup);
    }

    public function offsetGet($offset)
    {
        return $this->translator->translate($offset);
    }
}

/**
 * @deprecated
 */
class Locale
{
    private $setup;

    public function __construct(Setup $setup)
    {
        $this->text = new Translations($setup);
        $this->setup = $setup;
    }

    // Return file permissions locale with directory and PHP user
    public function permissionsInfo($file)
    {
        // PHP user, or www-data
        $php_user = Misc::getArrayItem($_SERVER, 'USER') ?: 'www-data';

        return sprintf(
            $this->text['permissions-info'],
            $this->setup->getHttpPath($file),
            $php_user
        );
    }
}
