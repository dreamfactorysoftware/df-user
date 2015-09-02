<?php
namespace DreamFactory\Core\User\Resources\System;

use DreamFactory\Core\Contracts\ServiceResponseInterface;
use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Exceptions\NotFoundException;
use DreamFactory\Core\Models\EmailTemplate;
use DreamFactory\Core\Utility\ResourcesWrapper;
use DreamFactory\Core\Utility\ServiceHandler;
use DreamFactory\Core\Utility\Session;
use DreamFactory\Library\Utility\ArrayUtils;
use DreamFactory\Core\Models\BaseSystemModel;
use DreamFactory\Core\Resources\System\BaseSystemResource;
use DreamFactory\Library\Utility\Enums\Verbs;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Services\Email\BaseService as EmailService;
use DreamFactory\Core\Models\Service;
use Log;

class User extends BaseSystemResource
{
    /**
     * {@inheritdoc}
     */
    protected function getSelectionCriteria()
    {
        $criteria = parent::getSelectionCriteria();

        $condition = ArrayUtils::get($criteria, 'condition');

        if (!empty($condition)) {
            $condition .= ' AND is_sys_admin = "0" ';
        } else {
            $condition = ' is_sys_admin = "0" ';
        }

        ArrayUtils::set($criteria, 'condition', $condition);

        return $criteria;
    }

    /**
     * {@inheritdoc}
     */
    protected function retrieveById($id, array $related = [])
    {
        /** @var BaseSystemModel $modelClass */
        $modelClass = $this->model;
        $criteria = $this->getSelectionCriteria();
        $fields = ArrayUtils::get($criteria, 'select');
        $model = $modelClass::whereIsSysAdmin(0)->with($related)->find($id, $fields);

        $data = (!empty($model)) ? $model->toArray() : [];

        return $data;
    }

    protected function postProcess()
    {
        $sendInvite = $this->request->getParameterAsBool('send_invite');

        switch ($this->action) {
            case Verbs::POST:
            case Verbs::PUT:
            case Verbs::PATCH:
            case Verbs::MERGE:
                if ($sendInvite) {
                    $response = $this->response;

                    if ($response instanceof ServiceResponseInterface) {
                        $response = $response->getContent();
                    }

                    if (is_array($response)) {
                        $records = ArrayUtils::get($response, ResourcesWrapper::DEFAULT_WRAPPER);
                        if (ArrayUtils::isArrayNumeric($records)) {
                            foreach ($records as $record) {
                                $id = ArrayUtils::get($record, 'id');

                                try {
                                    static::sendInvite($id, ($this->action === Verbs::POST));
                                } catch (\Exception $e) {
                                    Log::error('Error processing user invitation: ' . $e->getMessage());
                                }
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

        parent::postProcess();
    }

    protected static function sendInvite($userId, $deleteOnError = false)
    {
        /** @type BaseSystemModel $user */
        $user = \DreamFactory\Core\Models\User::find($userId);

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
                throw new InternalServerErrorException('No email service configured for user invite. See system configuration.');
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
                $currentUser = Session::user();

                $data = [
                    'to'                    => $email,
                    'subject'               => 'Welcome to DreamFactory',
                    'first_name'            => $user->first_name,
                    'last_name'             => $user->last_name,
                    'confirm_code'          => $user->confirm_code,
                    'display_name'          => $user->name,
                    'from_name'             => $currentUser->first_name . ' ' . $currentUser->last_name
                ];
            } catch (\Exception $e) {
                throw new InternalServerErrorException("Error creating user invite.\n{$e->getMessage()}",
                    $e->getCode());
            }

            $emailService->sendEmail($data, $emailTemplate->body_text, $emailTemplate->body_html);
        } catch (\Exception $e) {
            if ($deleteOnError) {
                $user->delete();
            }
            throw new InternalServerErrorException("Error processing user invite.\n{$e->getMessage()}", $e->getCode());
        }
    }
}