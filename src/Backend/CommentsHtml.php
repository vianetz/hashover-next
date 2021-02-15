<?php
declare(strict_types=1);

namespace HashOver\Backend;

use HashOver\Avatars;
use HashOver\Cookies;
use HashOver\Domain\Templater;
use HashOver\Login;
use HashOver\Setup;

final class CommentsHtml
{
    private Templater $templater;
    private Login $login;
    private Setup $setup;
    private string $mode;
    private Cookies $cookies;
    private Avatars $avatars;

    public function __construct(Templater $templater, Setup $setup, Login $login, Cookies $cookies, Avatars $avatars, string $mode = 'javascript')
    {
        $this->templater = $templater;
        $this->setup = $setup;
        $this->login = $login;
        $this->mode = $mode;
        $this->cookies = $cookies;
        $this->avatars = $avatars;
    }

    public function render(array $commentCounts, ?string $comments, ?string $popularComments, bool $needsHashoverWrapper = true): string
    {
        $userName = $this->login->name ?? $this->setup->defaultName;
        $userWebsite = $this->login->website;
        $isUserTwitter = $userName[0] === '@';
        if ($isUserTwitter) {
            $userName = \mb_substr($userName, 1);
            if (empty($userWebsite)) {
                $userWebsite = 'https://twitter.com/' . $userName;
            }
        }

        $templateVars = [
            'setup' => $this->setup,
            'isMobile' => $this->setup->isMobile,
            'isVectorImage' => $this->setup->imageFormat === 'svg',
            'isLoggedIn' => $this->login->userIsLoggedIn,
            'userName' => $userName,
            'userWebsite' => $userWebsite,
            'userEmail' => $this->login->email,
            'isUserTwitter' => $isUserTwitter,
            'instanceNumber' => $this->setup->instanceNumber,
            'isLoginRequired' => $this->setup->requiresLogin && ! $this->login->userIsLoggedIn,
            'enableCollapseInterface' => $this->setup->collapsesInterface,
            'isPhpMode' => $this->mode === \HashOver::HASHOVER_MODE_PHP,
            'cookie' => [
                'message' => $this->cookies->getValue('message') ?? $this->cookies->getValue('error'),
                'isError' => $this->cookies->getValue('error') !== null,
            ],
            'formAction' => $this->setup->getBackendPath('form-actions'),
            'enableAvatars' => $this->setup->iconMode !== 'none',
            'enableLabels' => $this->setup->usesLabels,
            'comment' => $this->cookies->getValue('comment'),
            'isCommentFailed' => $this->cookies->getValue('failed-on') === 'comment',
            'isEmailFailed' => $this->cookies->getValue('failed-on') === 'email',
            'isNameFailed' => $this->cookies->getValue('failed-on') === 'name',
            'isWebsiteFailed' => $this->cookies->getValue('failed-on') === 'website',
            'isPasswordFailed' => $this->cookies->getValue('failed-on') === 'password',
            'failedOnField' => $this->cookies->getValue('failed-on'),
            'reply' => $this->cookies->getValue('replied'), // check if comment is a failed reply
            'commentFormat' => $this->setup->usesMarkdown ? 'markdown' : 'html',
            'url' => $this->setup->pageURL,
            'thread' => $this->setup->threadName,
            'pageTitle' => $this->setup->pageTitle,
            'enableRemoteAccess' => $this->setup->remoteAccess,
            'enableEmailField' => $this->setup->emailField !== 'off',
            'enableWebsiteField' => $this->setup->websiteField !== 'off',
            'enableNameField' => $this->setup->nameField !== 'off',
            'enablePasswordField' => $this->setup->passwordField !== 'off',
            'enableEmailRequired' => $this->setup->emailField === 'required',
            'enableWebsiteRequired' => $this->setup->websiteField === 'required',
            'enableNameRequired' => $this->setup->nameField === 'required',
            'enablePasswordRequired' => $this->setup->passwordField === 'required',
            'enableSubscribeByDefault' => $this->setup->subscribesUser,
            'enableLogin' => $this->setup->allowsLogin,
            'commentCounts' => $commentCounts,
            'collapseLimit' => $this->setup->collapseLimit,
            'defaultSorting' => $this->setup->defaultSorting,
            'comments' => $comments,
            'popularComments' => $popularComments,
            'enableRssAppend' => $this->setup->appendsRss,
            'rssUrl' => $this->setup->getHttpPath('api/rss.php') . '?url=' . $this->setup->pageURL,
            'enableDisplayTitle' => $this->setup->displaysTitle && ! empty($this->setup->pageTitle),
        ];

        return $this->templater->parseTheme(
            $needsHashoverWrapper ? 'comments-latte-with-wrapper.latte' : 'comments.latte',
            $templateVars
        );
    }
}