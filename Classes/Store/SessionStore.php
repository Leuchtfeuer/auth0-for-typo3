<?php
namespace Bitmotion\Auth0\Store;

use Auth0\SDK\Store\SessionStore as Auth0SessionStore;

class SessionStore extends Auth0SessionStore
{
    public function __construct($base_name = \Auth0\SDK\Store\SessionStore::BASE_NAME)
    {
        \Auth0\SDK\Store\SessionStore::__construct($base_name);
    }

    public function getUserInfo(): array
    {
        return $this->get('user') ?? [];
    }

    public function deleteUserInfo(): void
    {
        $this->delete('user');
    }
}
