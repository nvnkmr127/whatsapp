<?php

/*
Project         :   WhatsApp Marketing & Automation Platform with Bots, Chats, Bulk Sender & AI
@package        :   Laravel
Laravel Version :   11.41.3
PHP Version     :   8.2.18
Created Date    :   14-01-2025
Copyright       :   Corbital Technologies LLP
Author          :   CORBITALTECH™
Author URL      :   https://codecanyon.net/user/corbitaltech
Support         :   contact@corbitaltech.dev
License         :   Licensed under Codecanyon Licence
*/

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
