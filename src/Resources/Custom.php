<?php

namespace DreamFactory\Core\User\Resources;

use DreamFactory\Core\Exceptions\UnauthorizedException;
use DreamFactory\Core\User\Models\UserCustom;
use DreamFactory\Core\Utility\Session as SessionUtility;

class Custom extends \DreamFactory\Core\System\Resources\Custom
{
    const RESOURCE_NAME = 'custom';

    /**
     * @var string DreamFactory\Core\Models\BaseSystemModel Model Class name.
     */
    protected static $model = UserCustom::class;

    /**
     * {@inheritdoc}
     */
    protected function handleGET()
    {
        static::checkUser();

        return parent::handleGET();
    }

    /**
     * {@inheritdoc}
     */
    protected function handlePOST()
    {
        static::checkUser();

        return parent::handlePOST();
    }

    /**
     * {@inheritdoc}
     */
    protected function handleDELETE()
    {
        static::checkUser();

        return parent::handleDELETE();
    }

    /**
     * Checks to see if there is a valid logged in user.
     * @throws \DreamFactory\Core\Exceptions\UnauthorizedException
     */
    private static function checkUser()
    {
        $userId = SessionUtility::getCurrentUserId();
        if (empty($userId)) {
            throw new UnauthorizedException('There is no valid session for the current request.');
        }
    }
}