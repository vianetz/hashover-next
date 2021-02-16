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

use HashOver\Backend\CommentsHtml;
use HashOver\Backend\EditFormHtml;
use HashOver\Backend\ReplyFormHtml;
use HashOver\Misc;
use HashOver\Setup;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class Comments extends Javascript
{
    private \HashOver $hashover;
    private ResponseInterface $response;
    private Setup $setup;
    private CommentsHtml $commentsHtml;
    private ReplyFormHtml $replyFormHtml;
    private EditFormHtml $editFormHtml;

    public function __construct(
        ResponseInterface $response,
        \HashOver $hashover,
        Setup $setup,
        CommentsHtml $commentsHtml,
        ReplyFormHtml $replyFormHtml,
        EditFormHtml $editFormHtml
    ) {
        $this->response = $response;
        $this->hashover = $hashover;
        $this->setup = $setup;
        $this->commentsHtml = $commentsHtml;
        $this->replyFormHtml = $replyFormHtml;
        $this->editFormHtml = $editFormHtml;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->setup->enableStatistics) {
            $this->hashover->statistics->executionStart();
        }

        $response = $this->setNonCache($this->response);
        $response = $this->setContentType($request, $response);

        $this->hashover->setMode(\HashOver::HASHOVER_MODE_JSON);

        $this->setup->setPageURL($request);
        $this->setup->setPageTitle('request');
        $this->setup->setThreadName('request');
        $this->setup->setWebsite('request');
        $this->setup->setInstance('request');

        $this->setup->loadFrontendSettings($request);

        $this->hashover->initiate();
        $this->hashover->parsePrimary();
        $this->hashover->parsePopular();
        $this->hashover->finalize();

        $this->hashover->defaultMetadata();

        $data = [];

        // Check if backend sorting and collapsing is enabled
        if ($this->setup->collapsesComments === true
            && $this->setup->usesAjax === true) {
            // If so, sort the comments first
            $this->hashover->sortPrimary();

            // Then collapse the comments
            $this->hashover->collapseComments();
        }

        // Check if we're preparing HashOver
        if ($this->setup->getRequest('prepare') !== false) {
            $data['locale'] = [
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
                'website' => $this->hashover->locale->text['website'],
            ];

            $data['setup'] = [
                'server-eol' => PHP_EOL,
                'collapse-limit' => $this->setup->collapseLimit,
                'default-sorting' => $this->setup->defaultSorting,
                'default-name' => $this->setup->defaultName,
                'user-is-logged-in' => $this->hashover->login->userIsLoggedIn,
                'user-is-admin' => $this->hashover->login->userIsAdmin,
                'http-root' => $this->setup->httpRoot,
                'http-backend' => $this->setup->httpBackend,
                'allows-dislikes' => $this->setup->allowsDislikes,
                'allows-likes' => $this->setup->allowsLikes,
                'image-extensions' => $this->setup->imageTypes,
                'image-placeholder' => $this->setup->getImagePath('place-holder'),
                'stream-mode' => ($this->setup->replyMode === 'stream'),
                'stream-depth' => $this->setup->streamDepth,
                'theme-css' => $this->setup->getThemePath('comments.css', true),
                'rss-api' => $this->setup->getHttpPath('api/rss.php'),
                'image-format' => $this->setup->imageFormat,
                'device-type' => $this->setup->isMobile ? 'mobile' : 'desktop',
                'collapses-interface' => $this->setup->collapsesInterface,
                'collapses-comments' => $this->setup->collapsesComments,
                'allows-images' => $this->setup->allowsImages,
                'uses-markdown' => $this->setup->usesMarkdown,
                'uses-cancel-buttons' => $this->setup->usesCancelButtons,
                'uses-auto-login' => $this->setup->usesAutoLogin,
                'uses-ajax' => $this->setup->usesAjax,
                'allows-login' => $this->setup->allowsLogin,
                'form-fields' => $this->setup->formFields,
            ];

            $data['ui'] = [
                'user-avatar' => $this->hashover->ui->userAvatar(),
                'name-link' => $this->hashover->ui->nameElement('a'),
                'name-span' => $this->hashover->ui->nameElement('span'),
                'parent-link' => $this->hashover->ui->parentThreadLink(),
                'edit-link' => $this->hashover->ui->formLink('[href]', 'edit'),
                'reply-link' => $this->hashover->ui->formLink('[href]', 'reply'),
                'like-link' => $this->hashover->ui->likeLink('like'),
                'dislike-link' => $this->hashover->ui->likeLink('dislike'),
                'like-count' => $this->hashover->ui->likeCount('likes'),
                'dislike-count' => $this->hashover->ui->likeCount('dislikes'),
                'name-wrapper' => $this->hashover->ui->nameWrapper(),
                'date-link' => $this->hashover->ui->dateLink(),
                'comment-wrapper' => $this->hashover->ui->commentWrapper(),
                'theme' => $this->hashover->templater->loadFile('comments.js.html'),
                'reply-form' => $this->replyFormHtml->render(),
                'edit-form' => $this->editFormHtml->render(),
            ];
        }

        $data['instance'] = [
            'primary-count' => $this->hashover->thread->primaryCount - 1,
            'total-count' => $this->hashover->thread->totalCount - 1,
            'page-url' => $this->setup->pageURL,
            'page-title' => $this->setup->pageTitle,
            'thread-name' => $this->setup->threadName,
            'file-path' => $this->setup->filePath,
            'initial-html' => $this->commentsHtml->render($this->hashover->ui->commentCounts, $this->hashover->ui->comments, $this->hashover->ui->popularComments, false),
            'comments' => $this->hashover->comments,
        ];

        // Count according to `$showsReplyCount` setting
        $show_comments = $this->hashover->getCommentCount('show-comments', 'show-comment');

        $postComment = $this->locale->text['post-a-comment'];
        if ($this->setup->displaysTitle !== false && ! empty($this->setup->pageTitle)) {
            $postComment = sprintf(
                $this->locale->text['post-a-comment-on'],
                $this->setup->pageTitle
            );
        }

        if ($this->setup->collapsesInterface) {
            $data['instance']['post-a-comment'] = $postComment;
            $data['instance']['show-comments'] = $show_comments;
        }
    
        // Text for "Show X Other Comment(s)" link
        if ($this->setup->collapsesComments) {
            // Check if at least 1 comment is to be shown
            if ($this->setup->collapseLimit >= 1) {
                // Shorter variables
                $total_count = $this->hashover->thread->totalCount;
                $collapse_limit = $this->setup->collapseLimit;

                // Get number of comments after collapse limit
                $other_count = ($total_count - 1) - $collapse_limit;

                // Subtract deleted comment counts
                if (! $this->setup->countsDeletions) {
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

        if ($this->setup->enableStatistics) {
            $this->hashover->statistics->executionEnd();

            $data['statistics'] = [
                'execution-time' => $this->hashover->statistics->executionTime,
                'script-memory' => $this->hashover->statistics->scriptMemory,
                'system-memory' => $this->hashover->statistics->systemMemory,
            ];
        }

        $response->getBody()->write(Misc::jsonData($data));

        return $response;
    }
}
