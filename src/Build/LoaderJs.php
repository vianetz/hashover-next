<?php
declare(strict_types=1);

// Copyright (C) 2018-2019 Jacob Barkdull
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

namespace HashOver\Build;

use HashOver\Settings;
use HashOver\Statistics;

final class LoaderJs implements MinifiedJs
{
    private Settings $settings;
    private Statistics $statistics;
    private JavaScriptBuild $build;
    
    public function __construct(JavaScriptBuild $build, Settings $settings)
    {
        $this->build = $build;
        $this->settings = $settings;
    }
    
    public function generate(): string
    {
        $this->build->registerFile('loader-constructor.js');
        $this->build->registerFile('onready.js');
        $this->build->registerFile('script.js');
        $this->build->registerFile('rootpath.js');
        $this->build->registerFile('cfgqueries.js');

        $output = $this->build->build(
            $this->settings->minifiesJavascript,
            $this->settings->minifyLevel
        );

        return $output;
    }
}
