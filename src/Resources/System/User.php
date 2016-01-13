<?php
namespace DreamFactory\Core\User\Resources\System;

use DreamFactory\Core\Contracts\ServiceResponseInterface;
use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Exceptions\NotFoundException;
use DreamFactory\Core\Models\EmailTemplate;
use DreamFactory\Core\Models\Service;
use DreamFactory\Core\Models\User as UserModel;
use DreamFactory\Core\Resources\System\BaseSystemResource;
use DreamFactory\Core\Services\Email\BaseService as EmailService;
use DreamFactory\Core\Utility\ResourcesWrapper;
use DreamFactory\Core\Utility\ServiceHandler;
use DreamFactory\Library\Utility\ArrayUtils;
use DreamFactory\Library\Utility\Enums\Verbs;
use Log;

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

        $condition = ArrayUtils::get($criteria, 'condition');

        if (!empty($condition)) {
            $condition .= " AND is_sys_admin = '0'";
        } else {
            $condition = " is_sys_admin = '0'";
        }

        ArrayUtils::set($criteria, 'condition', $condition);

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
        $fields = ArrayUtils::get($criteria, 'select');
        $model = $modelClass::whereIsSysAdmin(0)->with($related)->find($id, $fields);

        $data = (!empty($model)) ? $model->toArray() : [];

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function handlePOST()
    {
        return $this->handleInvitation(parent::handlePOST());
    }

    /**
     * {@inheritdoc}
     */
    protected function handlePATCH()
    {
        return $this->handleInvitation(parent::handlePATCH());
    }

    /**
     * {@inheritdoc}
     */
    protected function handlePUT()
    {
        return $this->handleInvitation(parent::handlePUT());
    }

    /**
     * @param $response
     *
     * @return mixed
     * @throws \Exception
     */
    protected function handleInvitation($response)
    {
        try {
            $sendInvite = $this->request->getParameterAsBool('send_invite');

            switch ($this->action) {
                case Verbs::POST:
                case Verbs::PUT:
                case Verbs::PATCH:
                case Verbs::MERGE:
                    if ($sendInvite) {
                        if ($response instanceof ServiceResponseInterface) {
                            $response = $response->getContent();
                        }

                        if (is_array($response)) {
                            $records = ArrayUtils::get($response, ResourcesWrapper::DEFAULT_WRAPPER);
                            if (ArrayUtils::isArrayNumeric($records)) {
                                $passed = true;
                                foreach ($records as $record) {
                                    $id = ArrayUtils::get($record, 'id');

                                    try {
                                        static::sendInvite($id, ($this->action === Verbs::POST));
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
                                $id = ArrayUtils::get($response, 'id');
                                if (empty($id)) {
                                    throw new InternalServerErrorException('Invalid user id in response.');
                                }
                                static::sendInvite($id, ($this->action === Verbs::POST));
                            }
                        }
                    }
                    break;
            }

            return $response;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param            $userId
     * @param bool|false $deleteOnError
     *
     * @throws \DreamFactory\Core\Exceptions\BadRequestException
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     * @throws \DreamFactory\Core\Exceptions\NotFoundException
     * @throws \Exception
     */
    protected static function sendInvite($userId, $deleteOnError = false)
    {
        /** @type UserModel $user */
        $user = UserModel::find($userId);

        if (empty($user)) {
            throw new NotFoundException('User not found with id ' . $userId . '.');
        }

        if ('y' === strtolower($user->confirm_code)) {
            throw new BadRequestException('User with this identifier has already confirmed this account.');
        }

        try {
            $userService = Service::getCachedByName('user');
            $config = $userService['config'];

            if (empty($config)) {
                throw new InternalServerErrorException('Unable to load system configuration.');
            }

            $emailServiceId = $config['invite_email_service_id'];
            $emailTemplateId = $config['invite_email_template_id'];

            if (empty($emailServiceId)) {
                throw new InternalServerErrorException('No email service configured for user invite.');
            }

            if (empty($emailTemplateId)) {
                throw new InternalServerErrorException("No default email template for user invite.");
            }

            /** @var EmailService $emailService */
            $emailService = ServiceHandler::getServiceById($emailServiceId);
            $emailTemplate = EmailTemplate::find($emailTemplateId);

            if (empty($emailTemplate)) {
                throw new InternalServerErrorException("No data found in default email template for user invite.");
            }

            try {
                $email = $user->email;
                $code = \Hash::make($email);
                $user->confirm_code = base64_encode($code);
                $user->save();
                $templateData = $emailTemplate->toArray();

                $data = array_merge($templateData, [
                    'to'             => $email,
                    'confirm_code'   => $user->confirm_code,
                    'link'           => url(\Config::get('df.confirm_invite_url')) . '?code=' . $user->confirm_code,
                    'first_name'     => $user->first_name,
                    'last_name'      => $user->last_name,
                    'name'           => $user->name,
                    'email'          => $user->email,
                    'phone'          => $user->phone,
                    'content_header' => ArrayUtils::get($templateData, 'subject',
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
    }
}