<?php
namespace DreamFactory\Core\User\Resources;

use DreamFactory\Core\Contracts\EmailServiceInterface;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Exceptions\NotFoundException;
use DreamFactory\Core\Exceptions\ServiceUnavailableException;
use DreamFactory\Core\Exceptions\UnauthorizedException;
use DreamFactory\Core\Models\EmailTemplate;
use DreamFactory\Core\Models\User;
use DreamFactory\Core\Resources\UserPasswordResource;
use ServiceManager;

class Password extends UserPasswordResource
{
    /**
     * {@inheritdoc}
     */
    protected function sendPasswordResetEmail(User $user)
    {
        $email = $user->email;

        /** @var \DreamFactory\Core\User\Services\User $parent */
        $parent = $this->getParent();

        if (!empty($parent->passwordEmailServiceId)) {
            try {
                /** @var EmailServiceInterface $emailService */
                $emailService = ServiceManager::getServiceById($parent->passwordEmailServiceId);

                if (empty($emailService)) {
                    throw new ServiceUnavailableException("Bad email service identifier.");
                }

                $data = [];
                if (!empty($parent->passwordEmailTemplateId)) {
                    // find template in system db
                    $template = EmailTemplate::whereId($parent->passwordEmailTemplateId)->first();
                    if (empty($template)) {
                        throw new NotFoundException("Email Template id '{$parent->passwordEmailTemplateId}' not found");
                    }

                    $data = $template->toArray();
                }

                if (empty($data) || !is_array($data)) {
                    throw new ServiceUnavailableException("No data found in default email template for password reset.");
                }

                $data['to'] = $email;
                $data['content_header'] = 'Password Reset';
                $data['first_name'] = $user->first_name;
                $data['last_name'] = $user->last_name;
                $data['name'] = $user->name;
                $data['phone'] = $user->phone;
                $data['email'] = $user->email;
                $data['link'] = url(\Config::get('df.confirm_reset_url')) .
                    '?code=' . $user->confirm_code .
                    '&email=' . $email .
                    '&username=' . $user->username;
                $data['confirm_code'] = $user->confirm_code;

                $bodyHtml = array_get($data, 'body_html');
                $bodyText = array_get($data, 'body_text');

                if (empty($bodyText) && !empty($bodyHtml)) {
                    $bodyText = strip_tags($bodyHtml);
                    $bodyText = preg_replace('/ +/', ' ', $bodyText);
                }

                $emailService->sendEmail($data, $bodyText, $bodyHtml);

                return true;
            } catch (\Exception $ex) {
                throw new InternalServerErrorException("Error processing password reset.\n{$ex->getMessage()}");
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected static function isAllowed(User $user)
    {
        if (null === $user) {
            throw new NotFoundException("User not found in the system.");
        }

        if ($user->is_sys_admin) {
            throw new UnauthorizedException('You are not authorized to reset/change password for the account ' .
                $user->email);
        }

        return true;
    }
}