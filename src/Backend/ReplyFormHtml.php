<?php
declare(strict_types=1);

namespace HashOver\Backend;

use HashOver\Avatars;
use HashOver\Cookies;
use HashOver\Domain\Templater;
use HashOver\HTMLTag;
use HashOver\Login;
use HashOver\Setup;

final class ReplyFormHtml extends FormHtmlAbstract
{
    public function render($permalink = '[permalink]', $url = '[url]', $thread = '[thread]', $title = '[title]', $file = '[file]'): string
    {
        $templateVars = array_merge($this->getDefaultTemplateVars(), [
            'permalink' => $permalink,
            'url' => $url,
            'thread' => $thread,
            'pageTitle' => $title,
            'file' => $file,
        ]);

        return $this->templater->parseTheme('comment-form-reply.latte', $templateVars);
    }
}