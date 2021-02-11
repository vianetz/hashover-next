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

use HashOver\Setup;

final class CommentsJs implements MinifiedJs
{
    private Setup $setup;
    private JavaScriptBuild $build;

    public function __construct(JavaScriptBuild $build, Setup $setup)
    {
        $this->setup = $setup;
        $this->build = $build;
    }

    public function generate(): string
    {
        $this->setup->loadFrontendSettings();

        $this->build->registerFile('constructor.js');
        $this->build->registerFile('onready.js');
        $this->build->registerFile('script.js');
        $this->build->registerFile('geturl.js');
        $this->build->registerFile('gettitle.js');
        $this->build->registerFile('cfgqueries.js');
        $this->build->registerFile('getclienttime.js');
        $this->build->registerFile('getbackendqueries.js');
        $this->build->registerFile('ajax.js');
        $this->build->registerFile('backendpath.js', ['dependencies' => ['rootpath.js']]);
        $this->build->registerFile('instantiator.js');
        $this->build->registerFile('createthread.js');
        $this->build->registerFile('createelement.js');
        $this->build->registerFile('classes.js');
        $this->build->registerFile('getmainelement.js');
        $this->build->registerFile('displayerror.js');
        $this->build->registerFile('prefix.js');
        $this->build->registerFile('regex.js');
        $this->build->registerFile('eoltrim.js');
        $this->build->registerFile('strings.js');
        $this->build->registerFile('permalinks.js');
        $this->build->registerFile('addratings.js', ['include' => ($this->setup->allowsLikes || $this->setup->allowsDislikes)]);
        $this->build->registerFile('optionalmethod.js');
        $this->build->registerFile('markdown.js', ['include' => $this->setup->usesMarkdown]);
        $this->build->registerFile('embedimage.js', [
            'include' => $this->setup->allowsImages,
            'dependencies' => ['openembeddedimage.js'],
        ]);
        $this->build->registerFile('parsecomment.js');
        $this->build->registerFile('getelement.js');
        $this->build->registerFile('eachclass.js');
        $this->build->registerFile('parseall.js');
        $this->build->registerFile('sortcomments.js', [
            'dependencies' => [
                'cloneobject.js',
                'getallcomments.js',
            ],
        ]);
        $this->build->registerFile('appendcomments.js', [
            'include' => $this->setup->usesAjax,
            'dependencies' => ['htmlchildren.js'],
        ]);
        $this->build->registerFile('messages.js');
        $this->build->registerFile('ajaxpost.js', [
            'include' => $this->setup->usesAjax,
            'dependencies' => [
                'addcomments.js',
                'htmlchildren.js',
            ],
        ]);
        $this->build->registerFile('ajaxedit.js', [
            'include' => $this->setup->usesAjax,
            'dependencies' => ['htmlchildren.js'],
        ]);
        $this->build->registerFile('postrequest.js', ['include' => $this->setup->usesAjax]);
        $this->build->registerFile('postcomment.js');
        $this->build->registerFile('permalinkfile.js');
        $this->build->registerFile('cancelswitcher.js');
        $this->build->registerFile('formattingonclick.js');
        $this->build->registerFile('duplicateproperties.js');
        $this->build->registerFile('formevents.js');
        $this->build->registerFile('replytocomment.js');
        $this->build->registerFile('editcomment.js');
        $this->build->registerFile('likecomment.js', [
            'include' => ($this->setup->allowsLikes || $this->setup->allowsDislikes),
            'dependencies' => ['mouseoverchanger.js'],
        ]);
        $this->build->registerFile('addcontrols.js');
        $this->build->registerFile('appendcss.js', ['include' => $this->setup->appendsCss]);
        $this->build->registerFile('appendrss.js', ['include' => $this->setup->appendsRss]);
        $this->build->registerFile('showinterfacelink.js', [
            'include' => $this->setup->collapsesInterface,
            'dependencies' => ['showinterface.js'],
        ]);
        $this->build->registerFile('showmorelink.js', [
            'include' => $this->setup->collapsesComments,
            'dependencies' => [
                'showmorecomments.js',
                'hidemorelink.js',
            ],
        ]);
        $this->build->registerFile('init.js');
        $this->build->registerFile('instantiate.js', ['include' => ! isset($_GET['nodefault'])]);

        return $this->setup->minifiesJavascript ? $this->build->build() : $this->build->getJs();
    }
}
