<?php
declare(strict_types=1);

namespace HashOver\Backend;

use HashOver\Avatars;
use HashOver\HTMLTag;
use HashOver\Login;
use HashOver\Setup;

final class Avatar
{
    private Setup $setup;
    private Login $login;
    private Avatars $avatars;

    public function __construct(Setup $setup, Login $login, Avatars $avatars)
    {
        $this->setup = $setup;
        $this->login = $login;
        $this->avatars = $avatars;
    }

    public function getAvatarHtml(string $text): string
    {
        if ($this->setup->iconMode === 'image') {
            if ($this->login->userIsLoggedIn) {
                // Image source is avatar image
                $hash = ! empty($this->login->email) ? md5(mb_strtolower(trim($this->login->email))) : '';
                $avatar_src = $this->avatars->getGravatar($hash);
            } else {
                // Logged out
                // Image source is local default image
                $avatar_src = $this->setup->getImagePath('first-comment');
            }

            $avatar = new HTMLTag('div', [
                'style' => 'background-image: url(\'' . $avatar_src . '\');'
            ], false);
        } else {
            // Avatars set to count
            // Create element displaying comment number user will be
            $avatar = new HTMLTag('span', $text, false);
        }

        return $avatar->asHTML();
    }
}