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

namespace HashOver\Handler;

use HashOver\Misc;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class Comments extends Javascript
{
    private \HashOver $hashover;
    private ResponseInterface $response;

    public function __construct(ResponseInterface $response, \HashOver $hashover)
    {
        $this->response = $response;
        $this->hashover = $hashover;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->hashover->setup->enableStatistics) {
            $this->hashover->statistics->executionStart();
        }

        $response = $this->setNonCache($this->response);
        $response = $this->setContentType($request, $response);

        $this->hashover->setMode(\HashOver::HASHOVER_MODE_JSON);
    
        $this->hashover->setup->setPageURL($request);
        $this->hashover->setup->setPageTitle('request');
        $this->hashover->setup->setThreadName('request');
        $this->hashover->setup->setWebsite('request');
        $this->hashover->setup->setInstance('request');
    
        $this->hashover->setup->loadFrontendSettings();
    
        $this->hashover->initiate();
        $this->hashover->parsePrimary();
        $this->hashover->parsePopular();
        $this->hashover->finalize();
    
        $this->hashover->defaultMetadata();
    
        $data = [];
    
        // Check if backend sorting and collapsing is enabled
        if ($this->hashover->setup->collapsesComments === true
            && $this->hashover->setup->usesAjax === true) {
            // If so, sort the comments first
            $this->hashover->sortPrimary();
    
            // Then collapse the comments
            $this->hashover->collapseComments();
        }
    
        // Check if we're preparing HashOver
        if ($this->hashover->setup->getRequest('prepare') !== false) {
            // If so, add locales to data
            $data['locale'] = array(
                'cancel' => $this->hashover->locale->text['cancel'],
                'dislike-comment' => $this->hashover->locale->text['dislike-comment'],
                'disliked-comment' => $this->hashover->locale->text['disliked-comment'],
                'disliked' => $this->hashover->locale->text['disliked'],
                'dislike' => $this->hashover->locale->text['dislike'],
                'dislikes' => $this->hashover->locale->text['dislikes'],
                'external-image-tip' => $this->hashover->locale->text['external-image-tip'],
                'field-needed' => $this->hashover->locale->text['field-needed'],
                'like-comment' => $this->hashover->locale->text['like-comment'],
                'liked-comment' => $this->hashover->locale->text['liked-comment'],
                'liked' => $this->hashover->locale->text['liked'],
                'like' => $this->hashover->locale->text['like'],
                'likes' => $this->hashover->locale->text['likes'],
                'today' => $this->hashover->locale->text['date-today'],
                'unlike' => $this->hashover->locale->text['unlike'],
                'commenter-tip' => $this->hashover->locale->text['commenter-tip'],
                'subscribed-tip' => $this->hashover->locale->text['subscribed-tip'],
                'unsubscribed-tip' => $this->hashover->locale->text['unsubscribed-tip'],
                'replies' => $this->hashover->locale->text['replies'],
                'reply' => $this->hashover->locale->text['reply'],
                'no-email-warning' => $this->hashover->locale->text['no-email-warning'],
                'invalid-email' => $this->hashover->locale->text['invalid-email'],
                'reply-needed' => $this->hashover->locale->text['reply-needed'],
                'comment-needed' => $this->hashover->locale->text['comment-needed'],
                'delete-comment' => $this->hashover->locale->text['delete-comment'],
                'loading' => $this->hashover->locale->text['loading'],
                'click-to-close' => $this->hashover->locale->text['click-to-close'],
                'email' => $this->hashover->locale->text['email'],
                'name' => $this->hashover->locale->text['name'],
                'password' => $this->hashover->locale->text['password'],
                'website' => $this->hashover->locale->text['website']
            );
    
            // Add setup information to data
            $data['setup'] = array(
                'server-eol' => PHP_EOL,
                'collapse-limit' => $this->hashover->setup->collapseLimit,
                'default-sorting' => $this->hashover->setup->defaultSorting,
                'default-name' => $this->hashover->setup->defaultName,
                'user-is-logged-in' => $this->hashover->login->userIsLoggedIn,
                'user-is-admin' => $this->hashover->login->userIsAdmin,
                'http-root' => $this->hashover->setup->httpRoot,
                'http-backend' => $this->hashover->setup->httpBackend,
                'allows-dislikes' => $this->hashover->setup->allowsDislikes,
                'allows-likes' => $this->hashover->setup->allowsLikes,
                'image-extensions' => $this->hashover->setup->imageTypes,
                'image-placeholder' => $this->hashover->setup->getImagePath('place-holder'),
                'stream-mode' => ($this->hashover->setup->replyMode === 'stream'),
                'stream-depth' => $this->hashover->setup->streamDepth,
                'theme-css' => $this->hashover->setup->getThemePath('comments.css'),
                'rss-api' => $this->hashover->setup->getHttpPath('api/rss.php'),
                'image-format' => $this->hashover->setup->imageFormat,
                'device-type' => ($this->hashover->setup->isMobile === true) ? 'mobile' : 'desktop',
                'collapses-interface' => $this->hashover->setup->collapsesInterface,
                'collapses-comments' => $this->hashover->setup->collapsesComments,
                'allows-images' => $this->hashover->setup->allowsImages,
                'uses-markdown' => $this->hashover->setup->usesMarkdown,
                'uses-cancel-buttons' => $this->hashover->setup->usesCancelButtons,
                'uses-auto-login' => $this->hashover->setup->usesAutoLogin,
                'uses-ajax' => $this->hashover->setup->usesAjax,
                'allows-login' => $this->hashover->setup->allowsLogin,
                'form-fields' => $this->hashover->setup->formFields
            );
    
            // And add UI HTML to data
            $data['ui'] = array(
                'user-avatar' => $this->hashover->ui->userAvatar(),
                'name-link' => $this->hashover->ui->nameElement('a'),
                'name-span' => $this->hashover->ui->nameElement('span'),
                'parent-link' => $this->hashover->ui->parentThreadLink(),
                'edit-link' => $this->hashover->ui->formLink('{href}', 'edit'),
                'reply-link' => $this->hashover->ui->formLink('{href}', 'reply'),
                'like-link' => $this->hashover->ui->likeLink('like'),
                'dislike-link' => $this->hashover->ui->likeLink('dislike'),
                'like-count' => $this->hashover->ui->likeCount('likes'),
                'dislike-count' => $this->hashover->ui->likeCount('dislikes'),
                'name-wrapper' => $this->hashover->ui->nameWrapper(),
                'date-link' => $this->hashover->ui->dateLink(),
                'comment-wrapper' => $this->hashover->ui->commentWrapper(),
                'theme' => $this->hashover->templater->parseTheme('comments.html'),
                'reply-form' => $this->hashover->ui->replyForm(),
                'edit-form' => $this->hashover->ui->editForm()
            );
        }
    
        // HashOver instance information
        $data['instance'] = array(
            'primary-count' => $this->hashover->thread->primaryCount - 1,
            'total-count' => $this->hashover->thread->totalCount - 1,
            'page-url' => $this->hashover->setup->pageURL,
            'page-title' => $this->hashover->setup->pageTitle,
            'thread-name' => $this->hashover->setup->threadName,
            'file-path' => $this->hashover->setup->filePath,
            'initial-html' => $this->hashover->ui->initialHTML(false),
            'comments' => $this->hashover->comments
        );
    
        // Count according to `$showsReplyCount` setting
        $show_comments = $this->hashover->getCommentCount('show-comments', 'show-comment');
    
        // Add locales for show interface button
        if ($this->hashover->setup->collapsesInterface !== false) {
            $data['instance']['post-a-comment'] = $this->hashover->ui->postComment;
            $data['instance']['show-comments'] = $show_comments;
        }
    
        // Text for "Show X Other Comment(s)" link
        if ($this->hashover->setup->collapsesComments !== false) {
            // Check if at least 1 comment is to be shown
            if ($this->hashover->setup->collapseLimit >= 1) {
                // Shorter variables
                $total_count = $this->hashover->thread->totalCount;
                $collapse_limit = $this->hashover->setup->collapseLimit;
    
                // Get number of comments after collapse limit
                $other_count = ($total_count - 1) - $collapse_limit;
    
                // Subtract deleted comment counts
                if ($this->hashover->setup->countsDeletions === false) {
                    $other_count -= $this->hashover->thread->collapsedDeletedCount;
                }
    
                // Check if there is more than one other comment
                if ($other_count !== 1) {
                    // If so, use the "Show X Other Comments" locale
                    $more_link_text = $this->hashover->locale->text['show-other-comments'];
                } else {
                    // If not, use the "Show X Other Comment" locale
                    $more_link_text = $this->hashover->locale->text['show-other-comment'];
                }
    
                // And inject the count into the locale string
                $more_link_text = sprintf($more_link_text, $other_count);
            } else {
                // If not, show count according to `$showsReplyCount` setting
                $more_link_text = $show_comments;
            }
    
            // Add "Show X Other Comment(s)" link to instance
            $data['instance']['more-link-text'] = $more_link_text;
        }

        if ($this->hashover->setup->enableStatistics) {
            $this->hashover->statistics->executionEnd();

            // HashOver statistics
            $data['statistics'] = array(
                'execution-time' => $this->hashover->statistics->executionTime,
                'script-memory' => $this->hashover->statistics->scriptMemory,
                'system-memory' => $this->hashover->statistics->systemMemory
            );
        }

        $response->getBody()->write(Misc::jsonData($data));

        return $response;
    }
}
