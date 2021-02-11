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

namespace HashOver\Admin\Handler;

final class LoginHandler extends AbstractHandler
{
    public function __invoke(): void
    {
        // Check if the user submitted login information
        if (!empty ($_POST['name']) and !empty ($_POST['password'])) {
            // If so, attempt to log them in
            $this->hashover->login->setAdminLogin();

            // Check if user is admin
            if ($this->hashover->login->isAdmin() === true) {
                // If so, login as admin
                $this->hashover->login->adminLogin();
            } else {
                // If not, logout
                $this->hashover->login->clearLogin();

                // Sleep 5 seconds
                sleep(5);
            }

            $this->redirect();
        }

        // Check if we're logging out
        if (isset ($_GET['logout'])) {
            // If so, attempt to log the user out
            $this->hashover->login->clearLogin();

            // Get path to main admin page
            $admin_path = $this->hashover->setup->getHttpPath('admin');

            // And redirect user to main admin page
            $this->redirect($admin_path . '/');
        }

        // Template data
        $template = array(
            'title' => $this->hashover->locale->text['login'],
            'sub-title' => $this->hashover->locale->text['admin-required'],
            'name' => $this->hashover->locale->text['name'],
            'password' => $this->hashover->locale->text['password'],
            'login' => $this->hashover->locale->text['login']
        );

        echo $this->hashover->templater->parseTemplate(APP_DIR . '/templates/admin/login.html', $template);
    }
}