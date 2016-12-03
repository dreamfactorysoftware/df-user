<?php
namespace DreamFactory\Core\User\Resources\System;

use DreamFactory\Core\Components\Registrar;
use DreamFactory\Core\Contracts\EmailServiceInterface;
use DreamFactory\Core\Contracts\ServiceResponseInterface;
use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Exceptions\NotFoundException;
use DreamFactory\Core\Models\EmailTemplate;
use DreamFactory\Core\Models\User as UserModel;
use DreamFactory\Core\Resources\System\BaseSystemResource;
use DreamFactory\Core\Utility\ResourcesWrapper;
use DreamFactory\Library\Utility\ArrayUtils;
use Log;
use ServiceManager;

class User extends BaseSystemResource
{
    /**
     * @var string DreamFactory\Core\Models\BaseSystemModel Model Class name.
     */
    protected static $model = UserModel::class;

    /**
     * {@inheritdoc}
     */
    protected function getSelectionCriteria()
    {
        $criteria = parent::getSelectionCriteria();

        $condition = array_get($criteria, 'condition');

        if (!empty($condition)) {
            $condition = "($condition) AND is_sys_admin = '0'";
        } else {
            $condition = " is_sys_admin = '0'";
        }

        $criteria['condition'] = $condition;

        return $criteria;
    }

    /**
     * {@inheritdoc}
     */
    protected function retrieveById($id, array $related = [])
    {
        /** @var UserModel $modelClass */
        $modelClass = static::$model;
        $criteria = $this->getSelectionCriteria();
        $fields = array_get($criteria, 'select');
        $model = $modelClass::whereIsSysAdmin(0)->with($related)->find($id, $fields);

        $data = (!empty($model)) ? $model->toArray() : [];

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function handlePOST()
    {
        $response = parent::handlePOST();
        if ($this->request->getParameterAsBool('send_invite')) {
            $this->handleInvitation($response, true);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function handlePATCH()
    {
        $response = parent::handlePATCH();
        if ($this->request->getParameterAsBool('send_invite')) {
            $this->handleInvitation($response);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function handlePUT()
    {
        $response = parent::handlePUT();
        if ($this->request->getParameterAsBool('send_invite')) {
            $this->handleInvitation($response);
        }

        return $response;
    }

    /**
     * @param      $response
     * @param bool $delete_on_error
     *
     * @throws InternalServerErrorException
     * @throws \Exception
     */
    protected static function handleInvitation(&$response, $delete_on_error = false)
    {
        if ($response instanceof ServiceResponseInterface) {
            if ($response->getStatusCode() >= 300) {
                return;
            }
            $records = $response->getContent();
            if (is_array($records)) {
                $wrapped = false;
                if (array_key_exists(ResourcesWrapper::DEFAULT_WRAPPER, $records)) {
                    $wrapped = true;
                    $records = array_get($records, ResourcesWrapper::DEFAULT_WRAPPER);
                }
                if (ArrayUtils::isArrayNumeric($records)) {
                    $passed = true;
                    foreach ($records as &$record) {
                        $id = array_get($record, 'id');

                        try {
                            $code = static::sendInvite($id, $delete_on_error);
                            if (array_key_exists('confirm_code', $record)) {
                                array_set($record, 'confirm_code', $code);
                            }
                            if (array_key_exists('confirmed', $record)) {
                                array_set($record, 'confirmed', false);
                            }
                        } catch (\Exception $e) {
                            if (count($records) === 1) {
                                throw $e;
                            } else {
                                $passed = false;
                                Log::error('Error processing invitation for user id ' .
                                    $id .
                                    ': ' .
                                    $e->getMessage());
                            }
                        }
                    }
                    if (!$passed) {
                        throw new InternalServerErrorException('Not all users were created successfully. Check log for more details.');
                    }
                } else {
                    $id = array_get($records, 'id');
                    if (empty($id)) {
                        throw new InternalServerErrorException('Invalid user id in response.');
                    }
                    $code = static::sendInvite($id, $delete_on_error);
                    if (array_key_exists('confirm_code', $records)) {
                        array_set($records, 'confirm_code', $code);
                    }
                    if (array_key_exists('confirmed', $records)) {
                        array_set($records, 'confirmed', false);
                    }
                }
                if ($wrapped) {
                    $records = [ResourcesWrapper::DEFAULT_WRAPPER => $records];
                }
                $response->setContent($records);
            }
        }
    }

    /**
     * @param integer $userId
     * @param bool    $deleteOnError
     *
     * @throws \DreamFactory\Core\Exceptions\BadRequestException
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     * @throws \DreamFactory\Core\Exceptions\NotFoundException
     * @throws \Exception
     * @return string
     */
    protected static function sendInvite($userId, $deleteOnError = false)
    {
        /** @type UserModel $user */
        /** @noinspection PhpUndefinedMethodInspection */
        $user = UserModel::find($userId);

        if (empty($user)) {
            throw new NotFoundException('User not found with id ' . $userId . '.');
        }

        if ('y' === strtolower($user->confirm_code)) {
            throw new BadRequestException('User with this identifier has already confirmed this account.');
        }

        try {
            /** @var \DreamFactory\Core\User\Services\User $userService */
            $userService = ServiceManager::getService('user');
            if (empty($userService)) {
                throw new InternalServerErrorException('Unable to load user service.');
            }

            if (empty($userService->inviteEmailServiceId)) {
                throw new InternalServerErrorException('No email service configured for user invite.');
            }

            if (empty($userService->inviteEmailTemplateId)) {
                throw new InternalServerErrorException("No default email template for user invite.");
            }

            /** @var EmailServiceInterface $emailService */
            $emailService = ServiceManager::getServiceById($userService->inviteEmailServiceId);
            /** @var EmailTemplate $emailTemplate */
            /** @noinspection PhpUndefinedMethodInspection */
            $emailTemplate = EmailTemplate::find($userService->inviteEmailTemplateId);

            if (empty($emailTemplate)) {
                throw new InternalServerErrorException("No data found in default email template for user invite.");
            }

            try {
                $email = $user->email;
                $user->confirm_code = Registrar::generateConfirmationCode(\Config::get('df.confirm_code_length', 32));
                $user->save();
                $templateData = $emailTemplate->toArray();

                $data = array_merge($templateData, [
                    'to'             => $email,
                    'confirm_code'   => $user->confirm_code,
                    'link'           => url(\Config::get('df.confirm_invite_url')) .
                        '?code=' . $user->confirm_code .
                        '&email=' . $email,
                    'first_name'     => $user->first_name,
                    'last_name'      => $user->last_name,
                    'name'           => $user->name,
                    'display_name'   => $user->name,
                    'email'          => $user->email,
                    'phone'          => $user->phone,
                    'content_header' => array_get($templateData, 'subject',
                        'You are invited to try DreamFactory.'),
                    'instance_name'  => \Config::get('df.instance_name')
                ]);
            } catch (\Exception $e) {
                throw new InternalServerErrorException("Error creating user invite. {$e->getMessage()}",
                    $e->getCode());
            }

            $bodyText = $emailTemplate->body_text;
            if (empty($bodyText)) {
                //Strip all html tags.
                $bodyText = strip_tags($emailTemplate->body_html);
                //Change any multi spaces to a single space for clarity.
                $bodyText = preg_replace('/ +/', ' ', $bodyText);
            }

            $emailService->sendEmail($data, $bodyText, $emailTemplate->body_html);
        } catch (\Exception $e) {
            if ($deleteOnError) {
                $user->delete();
            }
            throw new InternalServerErrorException("Error processing user invite. {$e->getMessage()}", $e->getCode());
        }

        return $user->confirm_code;
    }
}