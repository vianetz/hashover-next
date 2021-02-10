<?php
declare(strict_types=1);

namespace HashOver\Backend;

use HashOver\Avatars;
use HashOver\Crypto;
use HashOver\HTMLTag;
use HashOver\Locale;
use HashOver\Misc;
use HashOver\Setup;
use HashOver\Templater;
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

    public function __construct(EmailSender $emailSender, Setup $setup, Crypto $crypto, Avatars $avatars, Templater $templater, Thread $thread)
    {
        $this->setup = $setup;
        $this->locale = new Locale($setup);
        $this->emailSender = $emailSender;
        $this->crypto = $crypto;
        $this->avatars = $avatars;
        $this->templater = $templater;
        $this->thread = $thread;
    }

    /**
     * @param array<mixed> $data
     */
    public function send(string $file, array $data, string $email, string $notificationEmail, string $noreplyEmail, ?string $replyTo = null, ?string $name = null): void
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
        $templateVars['text-comment'] = $this->indentWordwrap($data['body']);
        $templateVars['from'] = sprintf($this->locale->text['from'], $name);
        $templateVars['comment'] = $this->locale->text['comment'];
        $templateVars['page'] = $this->locale->text['page'];
        $templateVars['new-comment'] = $newComment;
        $templateVars['permalink'] = $this->setup->pageURL . '#' . $permalink;
        $templateVars['url'] = $this->setup->pageURL;
        $templateVars['title'] = $this->setup->pageTitle;
        $templateVars['sent-by'] = sprintf($this->locale->text['sent-by'], $this->setup->website);
        $reply = $this->thread->data->read($replyTo);

        // Check if the reply comment read successfully
        if ($reply !== false) {
            // If so, decide name of recipient
            $reply_name = Misc::getArrayItem($reply, 'name') ?: $this->setup->defaultName;

            // Add reply name to data
            $templateVars['reply-name'] = $reply_name;

            // Add "In reply to" locale string to data
            $templateVars['in-reply-to'] = sprintf($this->locale->text['thread'], $reply_name);

            // Add indented body of recipient's comment to data
            $templateVars['text-reply'] = $this->indentWordwrap($reply['body']);

            // And add HTML version of the reply comment to data
            if ($this->setup->mailType !== 'text') {
                $templateVars['html-reply'] = $this->paragraphsTags($reply['body'], "\t\t\t\t");
            }
        }

        $text_body = $this->templater->parseTheme('email-notification.txt', $templateVars);
        $this->emailSender->subject($newComment . ' - ' . $this->setup->website);
        $this->emailSender->text($text_body);

        if ($this->setup->mailType !== 'text') {
            $templateVars['html-comment'] = $this->paragraphsTags($data['body'], "\t\t\t\t");
            $html_body = $this->templater->parseTheme('email-notification.html', $templateVars);
            $this->emailSender->html($html_body);
        }

        // Only send admin notification if it's not admin posting
        if ($email !== $notificationEmail) {
            $this->emailSender->to($notificationEmail);
            $this->emailSender->from($noreplyEmail);
            $this->emailSender->send();
        }

        // Do nothing else if reply comment failed to read
        if ($reply === false) {
            return;
        }

        // Do nothing else if reply comment lacks e-mail and decrypt info
        if (empty($reply['email']) || empty($reply['encryption'])) {
            return;
        }

        // Do nothing else if reply comment poster disabled notifications
        if (Misc::getArrayItem($reply, 'notifications') === 'no') {
            return;
        }

        // Otherwise, decrypt reply e-mail address
        $reply_email = $this->crypto->decrypt($reply['email'], $reply['encryption']);

        // Check if reply e-mail is different than login's and admin's
        if ($reply_email !== $email && $reply_email !== $notificationEmail) {
            // If so, set message to be sent to reply comment e-mail
            $this->emailSender->to($reply_email);

            // Check if users are allowed to reply by email
            if ($this->setup->allowsUserReplies) {
                // If so, set e-mail as coming from posting user
                $this->emailSender->from($email);
            } else {
                // If not, set e-mail as coming from noreply e-mail
                $this->emailSender->from($noreplyEmail);
            }

            $this->emailSender->send();
        }
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

    /**
     * Converts text paragraphs to HTML paragraph tags
     */
    private function paragraphsTags(string $text, string $indention = ''): string
    {
        $paragraphs = [];

        // Break comment into paragraphs
        $ps = preg_split('/(\r\n|\r|\n){2}/S', $text);

        // Wrap each paragraph in <p> tags and place <br> tags after each line
        for ($i = 0, $il = count($ps); $i < $il; $i++) {
            // Place <br> tags after each line
            $paragraph = preg_replace('/(\r\n|\r|\n)/S', '<br>\\1', $ps[$i]);

            // Create <p> tag
            $pTag = new HTMLTag('p', $paragraph);

            // Add paragraph to HTML
            $paragraphs[] = $pTag->asHTML($indention);
        }

        // Convert paragraphs array to string
        return implode("\r\n\r\n" . $indention, $paragraphs);
    }
}