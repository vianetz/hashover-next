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

namespace HashOver;

require __DIR__ . '/../backend/javascript-setup.php';

try {
    $settings = new Settings();

    $statistics = new Statistics();
    $statistics->executionStart();

    $javascript = new JavaScriptBuild('../../frontend');

    $javascript->registerFile('loader-constructor.js');
    $javascript->registerFile('onready.js');
    $javascript->registerFile('script.js');
    $javascript->registerFile('rootpath.js');
    $javascript->registerFile('cfgqueries.js');

    $output = $javascript->build(
        $settings->minifiesJavascript,
        $settings->minifyLevel
    );

    echo $output, PHP_EOL;

    echo $statistics->executionEnd();
} catch (\Throwable $error) {
    /** @var \Psr\Log\LoggerInterface $logger */
    $logger = $container->get(\Monolog\Logger::class);
    $logger->error($error->getMessage());
}
