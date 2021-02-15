<?php
declare(strict_types=1);

namespace HashOver\Backend;

use HashOver\Avatars;
use HashOver\Cookies;
use HashOver\Domain\Templater;
use HashOver\HTMLTag;
use HashOver\Login;
use HashOver\Setup;

final class EditFormHtml extends FormHtmlAbstract
{
    public function render($permalink = '[permalink]', $url = '[url]', $thread = '[thread]', $title = '[title]', $file = '[file]', $name = '[name]', $email = '[email]', $website = '[website]', $body = '[body]', $status = '', $subscribed = true)
    {
        $templateVars = array_merge($this->getDefaultTemplateVars(), [
            'permalink' => $permalink,
            'url' => $url,
            'thread' => $thread,
            'pageTitle' => $title,
            'file' => $file,
            'approvalStatus' => $status,
            'comment' => $body,
        ]);

        return $this->templater->parseTheme('comment-form-edit.latte', $templateVars);
    }
}