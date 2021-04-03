<?php
declare(strict_types=1);

namespace HashOver\Backend;

use HashOver\Avatars;
use HashOver\Cookies;
use HashOver\Domain\Templater;
use HashOver\Login;
use HashOver\Setup;

abstract class FormHtmlAbstract
{
    protected Templater $templater;
    protected Login $login;
    protected Setup $setup;
    protected string $mode;
    protected Cookies $cookies;
    protected Avatars $avatars;

    public function __construct(Templater $templater, Setup $setup, Login $login, Cookies $cookies, Avatars $avatars, string $mode = \HashOver::HASHOVER_MODE_JAVASCRIPT)
    {
        $this->templater = $templater;
        $this->setup = $setup;
        $this->login = $login;
        $this->mode = $mode;
        $this->cookies = $cookies;
        $this->avatars = $avatars;
    }

    protected function getDefaultTemplateVars()
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

        return [
            'setup' => $this->setup,
            'userName' => $userName,
            'userWebsite' => $userWebsite,
            'userEmail' => $this->login->email,
            'isUserTwitter' => $isUserTwitter,
            'isLoggedIn' => $this->login->userIsLoggedIn,
            'isUserAdmin' => $this->login->userIsAdmin,
            'comment' => $this->cookies->getValue('comment'),
            'commentFormat' => $this->setup->usesMarkdown ? 'markdown' : 'html',
            'isPhpMode' => $this->mode === \HashOver::HASHOVER_MODE_PHP,
            'failedOnField' => $this->cookies->getValue('failed-on'),
            'isCommentFailed' => $this->cookies->getValue('failed-on') === 'comment',
            'isEmailFailed' => $this->cookies->getValue('failed-on') === 'email',
            'isNameFailed' => $this->cookies->getValue('failed-on') === 'name',
            'isWebsiteFailed' => $this->cookies->getValue('failed-on') === 'website',
            'isPasswordFailed' => $this->cookies->getValue('failed-on') === 'password',
        ];
    }
}