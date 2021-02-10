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

namespace HashOver\Backend;

use Psr\Log\LoggerInterface;

final class EmailSender
{
    private \Swift_Message $message;
    private LoggerInterface $logger;
    private \Swift_Transport $transport;

    public function __construct(LoggerInterface $logger, string $smtpHost, int $smtpPort, string $smtpUser, string $smtpPassword)
    {
        $this->message = new \Swift_Message();
        $this->logger = $logger;

        $this->transport = (new \Swift_SmtpTransport($smtpHost, $smtpPort))
            ->setUsername($smtpUser)
            ->setPassword($smtpPassword);
    }

    public function to(string $email, string $name = null): void
    {
        $this->message->setTo($email, $name);
    }

    public function replyTo(string $email, string $name = null): void
    {
        $this->message->setReplyTo($email, $name);
    }

    public function from(string $email, string $name = null): void
    {
        $this->message->setFrom($email, $name);
    }

    public function subject(string $text): void
    {
        $this->message->setSubject($text);
    }

    public function text(string $text): void
    {
        $this->message->addPart($text);
    }

    public function html(string $html): void
    {
        $this->message->addPart($html, 'text/html');
    }

    public function send(): bool
    {
        try {
            $this->logger->info('Sending email to ' . implode(', ', array_keys($this->message->getTo())) . ' with subject ' . $this->message->getSubject());
            return (new \Swift_Mailer($this->transport))->send($this->message) > 0;
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
            return false;
        }
    }
}
