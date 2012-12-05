<?php
/**
 * Copyright (c) 2012 by Bluz PHP Team
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * @namespace
 */
namespace Application\Users;

use Application\UsersActions;

/**
 * Crud
 *
 * @category Application
 * @package  Users
 *
 * @author   Anton Shevchuk
 * @created  30.10.12 16:11
 */
class Crud extends \Bluz\Crud\Crud
{
    /**
     * @throws \Bluz\Crud\ValidationException
     */
    public function validateCreate()
    {
        // login validator
        $login = $this->getData('login');
        if (empty($login)) {
            $this->addError('login', 'Login can\'t be empty');
        }
        if ($this->getTable()->findRowWhere(['login' => $login])) {
            $this->addError('login', 'User with login "'.htmlentities($this->getData('login')).'" already exists');
        }

        // email validator
        $email = $this->getData('email');
        if (empty($email)) {
            $this->addError('email', 'Email can\'t be empty');
        }
        if ($this->getTable()->findRowWhere(['email' => $email])) {
            $this->addError('email', 'User with email "'.htmlentities($email).'" already exists');
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            list($user, $domain) = explode("@", $email, 2);
            if (!checkdnsrr($domain,"MX") && !checkdnsrr($domain,"A")) {
                $this->addError('email', 'Email has invalid domain name');
            }
        } else {
            $this->addError('email', 'Email is invalid');
        }

        // password
        $password = $this->getData('password');
        if (empty($password)) {
            $this->addError('password', 'Password can\'t be empty');
        }

        if ($password !== $this->getData('password2')) {
            $this->addError('password2', 'Password is not equal');
        }
        // validate entity
        if (sizeof($this->errors)) {
            throw new \Bluz\Crud\ValidationException('Validation error, please check errors stack');
        }
    }

    /**
     * @throws \Bluz\Crud\ValidationException
     */
    public function validateUpdate()
    {
        // name validator
        $login = $this->getData('login');
        if (empty($login)) {
            $this->addError('login', 'Login can\'t be empty');
        }

        // email validator
        $email = $this->getData('email');
        if (empty($email)) {
            $this->addError('email', 'Email can\'t be empty');
        }

        // validate entity
        if (sizeof($this->errors)) {
            throw new \Bluz\Crud\ValidationException('Validation error, please check errors stack');
        }
    }

    /**
     * @throws \Bluz\Crud\ValidationException
     * @return boolean
     */
    public function create()
    {
        $this->validateCreate();

        /** @var $row Row */
        $row = $this->getTable()->create();
        $row->setFromArray($this->data);
        $row->status = Row::STATUS_PENDING;

        $userId = $row->save();

        // create auth
        $password = $this->getData('password');
        $authRow = \Application\Auth\Table::getInstance()->generateEquals($row, $password);

        // create activation token
        // valid for 5 days
        $actionRow = UsersActions\Table::getInstance()->generate($userId, UsersActions\Row::ACTION_ACTIVATION, 5);

        // send activation email
        // generate activation URL
        $activationUrl = $this->getApplication()->getRouter()->getFullUrl(
            'users',
            'activation',
            ['code' => $actionRow->code, 'id' => $userId]
        );

        $subject =  "Bluz Activation";

        $headers = "Content-type: text/plain; charset=utf-8 \r\n"
            . "From: Bluz <dark@nixsolutions.com> \r\n"
            . "Reply-To: Bluz <dark@nixsolutions.com>\r\n";

        $body = "Hello {$row->login},\r\n"
              . "Thank you for registering at our site. Your account is created and must be activated before you can use it.\r\n"
              . "To activate the account click on the following link or copy-paste it in your browser:\r\n"
              . " $activationUrl \r\n"
              . "After activation you may login to site using the following username and password:\r\n"
              . " Username: {$row->login}\r\n"
              . " Password: $password\r\n"
              . "\r\n"
              . "WBR, site team"
        ;


        mail(
            $this->getData('email'),
            $subject,
            $body,
            $headers,
            '-fdark@nixsolutions.com'
        );

        // show notification and redirect
        $this->getApplication()->getMessages()->addSuccess(
            "Your account has been created and an activation link has been sent to the e-mail address you entered.<br/>".
            "Note that you must activate the account by clicking on the activation link when you get the e-mail before you can login."
        );
        $this->getApplication()->redirectTo('index', 'index');

        // disable default crud behaviour
        return false;
    }
}
