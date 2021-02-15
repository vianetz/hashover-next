<?php
declare(strict_types=1);

namespace HashOver\Backend;

use HashOver\Avatars;
use HashOver\Cookies;
use HashOver\Domain\Templater;
use HashOver\HTMLTag;
use HashOver\Login;
use HashOver\Setup;

final class ReplyFormHtml
{
    private Templater $templater;
    private Login $login;
    private Setup $setup;
    private string $mode;
    private Cookies $cookies;
    private Avatars $avatars;

    public function __construct(Templater $templater, Setup $setup, Login $login, Cookies $cookies, Avatars $avatars, string $mode = \HashOver::HASHOVER_MODE_JAVASCRIPT)
    {
        $this->templater = $templater;
        $this->setup = $setup;
        $this->login = $login;
        $this->mode = $mode;
        $this->cookies = $cookies;
        $this->avatars = $avatars;
    }

    public function render($permalink = '[permalink]', $url = '[url]', $thread = '[thread]', $title = '[title]', $file = '[file]'): string
    {
        $templateVars = [
            'permalink' => $permalink,
            'setup' => $this->setup,
            'isLoggedIn' => $this->login->userIsLoggedIn,
            'comment' => $this->cookies->getValue('comment'),
            'commentFormat' => $this->setup->usesMarkdown ? 'markdown' : 'html',
            'isPhpMode' => $this->mode === \HashOver::HASHOVER_MODE_PHP,
            'url' => $url,
            'thread' => $thread,
            'pageTitle' => $title,
            'file' => $file,
            'failedOnField' => $this->cookies->getValue('failed-on'),
        ];

        return $this->templater->parseTheme('comment-form-reply.latte', $templateVars);
    }
}