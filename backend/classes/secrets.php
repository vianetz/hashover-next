<?php namespace HashOver;

// Copyright (C) 2010-2019 Jacob Barkdull
// This file is part of HashOver.
//
// I, Jacob Barkdull, hereby release this work into the public domain.
// This applies worldwide. If this is not legally possible, I grant any
// entity the right to use this work for any purpose, without any
// conditions, unless such conditions are required by law.
//
//--------------------
//
// IMPORTANT NOTICE:
//
// To retain your settings and maintain proper functionality, when
// downloading or otherwise upgrading to a new version of HashOver it
// is important that you preserve this file, unless directed otherwise.
//
// It is also important to choose UNIQUE values for the encryption key,
// admin name, and admin password, as not doing so puts HashOver at
// risk of being hijacked. Allowing someone to delete comments and/or
// edit existing comments to post spam, impersonate you or your
// visitors in order to push some sort of agenda/propaganda, to defame
// you or your visitors, or to imply endorsement of some product(s),
// service(s), and/or political ideology.

/** @deprecated */
class Secrets
{
    protected string $notificationEmail = 'example@example.com';
    protected string $noreplyEmail = 'noreply@example.com';
    protected string $noreplyFrom = 'noreply@example.com';
    protected string $encryptionKey = '8CharKey';
    protected string $adminName = 'admin';
    protected string  $adminPassword = 'passwd';

    protected string $databaseType = 'sqlite';
    protected string $databaseName = 'hashover';
    protected string $databaseHost = 'localhost';
    protected string $databasePort = '3306';
    protected string $databaseUser = 'root';
    protected string $databasePassword = 'password';
    protected string $databaseCharset = 'utf8';

    protected string $smtpHost = 'smtp.gmail.com';
    protected int $smtpPort = 465;
    protected string $smtpCrypto = 'ssl';
    protected bool $smtpAuth = true;
    protected string $smtpUser = 'user';
    protected string $smtpPassword = 'password';

    public function __construct()
    {
        $this->encryptionKey = $_ENV['ENCRYPTION_KEY'];
        $this->notificationEmail = $_ENV['NOTIFICATION_EMAIL'];
        $this->noreplyEmail = $_ENV['NOREPLY_EMAIL'];
        $this->noreplyFrom = $_ENV['NOREPLY_FROM'];
        $this->smtpHost = $_ENV['SMTP_HOST'];
        $this->smtpPort = $_ENV['SMTP_PORT'];
        $this->smtpUser = $_ENV['SMTP_USER'];
        $this->smtpPassword = $_ENV['SMTP_PASSWORD'];
        $this->adminName = $_ENV['ADMIN_NAME'];
        $this->adminPassword = $_ENV['ADMIN_PASSWORD'];
        $this->databaseName = $_ENV['DB_NAME'];
        $this->databaseHost = $_ENV['DB_HOST'];
        $this->databasePort = $_ENV['DB_PORT'] ?? $this->databasePort;
        $this->databaseUser = $_ENV['DB_USER'];
        $this->databasePassword = $_ENV['DB_PASSWORD'];
        $this->databaseType = $_ENV['DB_TYPE'];
    }
}
