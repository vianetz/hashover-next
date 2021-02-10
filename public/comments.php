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
    $setup = new Setup();

    // Throw exception if requested by remote server
    $setup->refererCheck();

    $setup->loadFrontendSettings();

    $statistics = new Statistics();
    $statistics->executionStart();

    $javascript = new JavaScriptBuild('../../frontend');

    $javascript->registerFile('constructor.js');
    $javascript->registerFile('onready.js');
    $javascript->registerFile('script.js');
    $javascript->registerFile('geturl.js');
    $javascript->registerFile('gettitle.js');
    $javascript->registerFile('cfgqueries.js');
    $javascript->registerFile('getclienttime.js');
    $javascript->registerFile('getbackendqueries.js');
    $javascript->registerFile('ajax.js');
    $javascript->registerFile('backendpath.js', ['dependencies' => ['rootpath.js']]);
    $javascript->registerFile('instantiator.js');
    $javascript->registerFile('createthread.js');
    $javascript->registerFile('createelement.js');
    $javascript->registerFile('classes.js');
    $javascript->registerFile('getmainelement.js');
    $javascript->registerFile('displayerror.js');
    $javascript->registerFile('prefix.js');
    $javascript->registerFile('regex.js');
    $javascript->registerFile('eoltrim.js');
    $javascript->registerFile('strings.js');
    $javascript->registerFile('permalinks.js');
    $javascript->registerFile('addratings.js', ['include' => ($setup->allowsLikes || $setup->allowsDislikes)]);
    $javascript->registerFile('optionalmethod.js');
    $javascript->registerFile('markdown.js', ['include' => $setup->usesMarkdown]);
    $javascript->registerFile('embedimage.js', ['include' => $setup->allowsImages, 'dependencies' => ['openembeddedimage.js']]);
    $javascript->registerFile('parsecomment.js');
    $javascript->registerFile('getelement.js');
    $javascript->registerFile('eachclass.js');
    $javascript->registerFile('parseall.js');
    $javascript->registerFile('sortcomments.js', [
        'dependencies' => [
            'cloneobject.js',
            'getallcomments.js',
        ],
    ]);
    $javascript->registerFile('appendcomments.js', [
        'include' => $setup->usesAjax,
        'dependencies' => ['htmlchildren.js'],
    ]);
    $javascript->registerFile('messages.js');
    $javascript->registerFile('ajaxpost.js', [
        'include' => $setup->usesAjax,
        'dependencies' => [
            'addcomments.js',
            'htmlchildren.js',
        ],
    ]);
    $javascript->registerFile('ajaxedit.js', [
        'include' => $setup->usesAjax,
        'dependencies' => ['htmlchildren.js'],
    ]);
    $javascript->registerFile('postrequest.js', ['include' => $setup->usesAjax]);
    $javascript->registerFile('postcomment.js');
    $javascript->registerFile('permalinkfile.js');
    $javascript->registerFile('cancelswitcher.js');
    $javascript->registerFile('formattingonclick.js');
    $javascript->registerFile('duplicateproperties.js');
    $javascript->registerFile('formevents.js');
    $javascript->registerFile('replytocomment.js');
    $javascript->registerFile('editcomment.js');
    $javascript->registerFile('likecomment.js', [
        'include' => ($setup->allowsLikes || $setup->allowsDislikes),
        'dependencies' => ['mouseoverchanger.js'],
    ]);
    $javascript->registerFile('addcontrols.js');
    $javascript->registerFile('appendcss.js', ['include' => $setup->appendsCss]);
    $javascript->registerFile('appendrss.js', ['include' => $setup->appendsRss]);
    $javascript->registerFile('showinterfacelink.js', [
        'include' => $setup->collapsesInterface,
        'dependencies' => ['showinterface.js'],
    ]);
    $javascript->registerFile('showmorelink.js', [
        'include' => $setup->collapsesComments,
        'dependencies' => [
            'showmorecomments.js',
            'hidemorelink.js',
        ],
    ]);
    $javascript->registerFile('init.js');
    $javascript->registerFile('instantiate.js', ['include' => ! isset($_GET['nodefault'])]);

    $output = $javascript->build(
        $setup->minifiesJavascript,
        $setup->minifyLevel
    );

    echo $output, PHP_EOL;

    echo $statistics->executionEnd();
} catch (\Throwable $error) {
    /** @var \Psr\Log\LoggerInterface $logger */
    $logger = $container->get(\Monolog\Logger::class);
    $logger->error($error->getMessage());
}
