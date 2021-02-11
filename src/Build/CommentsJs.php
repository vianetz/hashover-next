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
    private JavaScriptBuild $build;

    public function __construct(JavaScriptBuild $build)
    {
        $this->build = $build;
    }

    public function generate(Setup $setup): string
    {
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
        $this->build->registerFile('addratings.js', ['include' => ($setup->allowsLikes || $setup->allowsDislikes)]);
        $this->build->registerFile('optionalmethod.js');
        $this->build->registerFile('markdown.js', ['include' => $setup->usesMarkdown]);
        $this->build->registerFile('embedimage.js', [
            'include' => $setup->allowsImages,
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
            'include' => $setup->usesAjax,
            'dependencies' => ['htmlchildren.js'],
        ]);
        $this->build->registerFile('messages.js');
        $this->build->registerFile('ajaxpost.js', [
            'include' => $setup->usesAjax,
            'dependencies' => [
                'addcomments.js',
                'htmlchildren.js',
            ],
        ]);
        $this->build->registerFile('ajaxedit.js', [
            'include' => $setup->usesAjax,
            'dependencies' => ['htmlchildren.js'],
        ]);
        $this->build->registerFile('postrequest.js', ['include' => $setup->usesAjax]);
        $this->build->registerFile('postcomment.js');
        $this->build->registerFile('permalinkfile.js');
        $this->build->registerFile('cancelswitcher.js');
        $this->build->registerFile('formattingonclick.js');
        $this->build->registerFile('duplicateproperties.js');
        $this->build->registerFile('formevents.js');
        $this->build->registerFile('replytocomment.js');
        $this->build->registerFile('editcomment.js');
        $this->build->registerFile('likecomment.js', [
            'include' => ($setup->allowsLikes || $setup->allowsDislikes),
            'dependencies' => ['mouseoverchanger.js'],
        ]);
        $this->build->registerFile('addcontrols.js');
        $this->build->registerFile('appendcss.js', ['include' => $setup->appendsCss]);
        $this->build->registerFile('appendrss.js', ['include' => $setup->appendsRss]);
        $this->build->registerFile('showinterfacelink.js', [
            'include' => $setup->collapsesInterface,
            'dependencies' => ['showinterface.js'],
        ]);
        $this->build->registerFile('showmorelink.js', [
            'include' => $setup->collapsesComments,
            'dependencies' => [
                'showmorecomments.js',
                'hidemorelink.js',
            ],
        ]);
        $this->build->registerFile('init.js');
        $this->build->registerFile('instantiate.js', ['include' => ! isset($_GET['nodefault'])]);

        return $setup->minifiesJavascript ? $this->build->build() : $this->build->getJs();
    }
}
