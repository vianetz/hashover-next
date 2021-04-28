<?php
declare(strict_types=1);

namespace HashOver\Backend;

final class CommentsHtml extends FormHtmlAbstract
{
    public function render(array $commentCounts, ?string $comments, ?string $popularComments, bool $needsHashoverWrapper = true): string
    {
        $templateVars = array_merge($this->getDefaultTemplateVars(), [
            'isMobile' => $this->setup->isMobile,
            'isVectorImage' => $this->setup->imageFormat === 'svg',
            'instanceNumber' => $this->setup->instanceNumber,
            'isLoginRequired' => $this->setup->requiresLogin && ! $this->login->userIsLoggedIn,
            'enableCollapseInterface' => $this->setup->collapsesInterface,
            'cookie' => [
                'message' => $this->cookies->getValue('message') ?? $this->cookies->getValue('error'),
                'isError' => $this->cookies->getValue('error') !== null,
            ],
            'formAction' => $this->setup->getBackendPath('form-actions'),
            'enableAvatars' => $this->setup->iconMode !== 'none',
            'enableLabels' => $this->setup->usesLabels,
            'reply' => $this->cookies->getValue('replied'), // check if comment is a failed reply
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
            'enableSubscribe' => $this->setup->subscribesUser,
            'enableLogin' => $this->setup->allowsLogin,
            'commentCounts' => $commentCounts,
            'collapseLimit' => $this->setup->collapseLimit,
            'defaultSorting' => $this->setup->defaultSorting,
            'comments' => $comments,
            'popularComments' => $popularComments,
            'enableRssAppend' => $this->setup->appendsRss,
            'rssUrl' => $this->setup->getHttpPath('api/rss.php') . '?url=' . $this->setup->pageURL,
            'enableDisplayTitle' => $this->setup->displaysTitle && ! empty($this->setup->pageTitle),
        ]);

        return $this->templater->parseTheme(
            $needsHashoverWrapper ? 'comments-latte-with-wrapper.latte' : 'comments.latte',
            $templateVars
        );
    }
}