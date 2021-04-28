<?php
declare(strict_types=1);

namespace HashOver\Domain;

use HashOver\Avatars;
use HashOver\Crypto;
use HashOver\Locale;
use HashOver\Misc;
use HashOver\Setup;
use HashOver\Thread;

final class SendNotification
{
    private Setup $setup;
    private Locale $locale;
    private EmailSender $emailSender;
    private Crypto $crypto;
    private Avatars $avatars;
    private Templater $templater;
    private Thread $thread;
    private Config $config;

    public function __construct(
        EmailSender $emailSender,
        Setup $setup,
        Crypto $crypto,
        Avatars $avatars,
        Templater $templater,
        Thread $thread,
        Config $config
    ) {
        $this->setup = $setup;
        $this->locale = new Locale($setup);
        $this->emailSender = $emailSender;
        $this->crypto = $crypto;
        $this->avatars = $avatars;
        $this->thread = $thread;
        $this->templater = $templater;
        $this->config = $config;
    }

    /**
     * Send notification email to
     * 1. Admin
     * 2. In case of an reply to the email address that was replied to (in case that it has subscribed to updates)
     *
     * @param array<mixed> $data
     */
    public function send(string $file, array $data, string $email, ?string $replyTo = null, ?string $name = null): void
    {
        $templateVars = [];
        $name = $name ?: $this->setup->defaultName;
        $permalink = $this->filePermalink($file);

        $newComment = $this->locale->text['new-comment'];

        // E-mail hash for Gravatar or empty for default avatar
        $hash = Misc::getArrayItem($data, 'email_hash') ?: '';

        $templateVars['avatar'] = $this->avatars->getGravatar($hash, true, 128);
        $templateVars['name'] = $name;
        $templateVars['domain'] = $this->setup->website;
        $templateVars['textComment'] = $this->indentWordwrap($data['body']);
        $templateVars['from'] = sprintf($this->locale->text['from'], $name);
        $templateVars['comment'] = $this->locale->text['comment'];
        $templateVars['page'] = $this->locale->text['page'];
        $templateVars['newComment'] = $newComment;
        $templateVars['permalink'] = $this->setup->pageURL . '#' . $permalink;
        $templateVars['url'] = $this->setup->pageURL;
        $templateVars['title'] = $this->setup->pageTitle;
        $templateVars['sentBy'] = sprintf($this->locale->text['sent-by'], $this->setup->website);
        $reply = $this->thread->data->read($replyTo);

        // Check if the reply comment read successfully
        if ($reply !== false) {
            // If so, decide name of recipient
            $reply_name = Misc::getArrayItem($reply, 'name') ?: $this->setup->defaultName;

            $templateVars['replyName'] = $reply_name;
            $templateVars['inReplyTo'] = sprintf($this->locale->text['thread'], $reply_name);

            // Add indented body of recipient's comment to data
            $templateVars['textReply'] = $this->indentWordwrap($reply['body']);

            // And add HTML version of the reply comment to data
            if ($this->setup->mailType !== 'text') {
                $templateVars['htmlReply'] = $reply['body'];
            }
        }

        $textBody = $this->templater->parseTheme('email-notification.txt', $templateVars);
        $this->emailSender->subject($newComment . ' - ' . $this->setup->website);
        $this->emailSender->text($textBody);

        if ($this->setup->mailType !== 'text') {
            $templateVars['htmlComment'] = $data['body'];
            $htmlBody = $this->templater->parseTheme('email-notification.html', $templateVars);
            $this->emailSender->html($htmlBody);
        }

        // Only send internal notification if it's not admin posting
        if ($email !== $this->config->get('notificationEmail')) {
            $this->emailSender->to($this->config->get('notificationEmail'));
            $this->emailSender->from($this->config->get('noreplyEmail'), $this->config->get('noreplyFrom'));
            $this->emailSender->send();
        }

        if ($reply === false || empty($reply['email']) || empty($reply['encryption'])
            || Misc::getArrayItem($reply, 'notifications') === 'no') {
            return;
        }

        // Otherwise, decrypt reply e-mail address
        $replyEmail = $this->crypto->decrypt($reply['email'], $reply['encryption']);

        // Check if reply e-mail is different than login's and admin's
        if ($replyEmail === $email || $replyEmail === $this->config->get('notificationEmail')) {
            return;
        }

        $this->emailSender->to($replyEmail);
        $this->emailSender->from($this->config->get('noreplyEmail'), $this->config->get('noreplyFrom'));
        $this->emailSender->send();
    }

    /**
     * Converts a file name (1-2) to a permalink (hashover-c1r1)
     */
    private function filePermalink(string $file): string
    {
        return 'hashover-c' . str_replace('-', 'r', $file);
    }

    /**
     * Wordwraps text after adding indentation
     */
    private function indentWordwrap(string $text): string
    {
        // Line ending styles to convert
        $styles = array(
            "\r\n",
            "\r"
        );

        // Convert line endings to UNIX-style
        $text = str_replace($styles, "\n", $text);

        // Wordwrap the text to 64 characters long
        $text = wordwrap($text, 64, "\n", true);

        // Split the text by paragraphs
        $paragraphs = explode("\n\n", $text);

        // Indent the first line of each paragraph
        array_walk($paragraphs, function (&$paragraph) {
            $paragraph = '    ' . $paragraph;
        });

        // Indent all other lines of each paragraph
        $paragraphs = str_replace("\n", "\r\n    ", $paragraphs);

        return implode("\r\n\r\n", $paragraphs);
    }
}