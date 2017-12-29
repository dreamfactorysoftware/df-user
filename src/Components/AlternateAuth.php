<?php

namespace DreamFactory\Core\User\Components;

use DreamFactory\Core\Enums\Verbs;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Exceptions\RestException;
use DreamFactory\Core\Exceptions\UnauthorizedException;
use DreamFactory\Core\Models\User;
use DreamFactory\Core\Utility\ResourcesWrapper;
use DreamFactory\Core\Utility\Session;
use Illuminate\Support\Arr;
use ServiceManager;

class AlternateAuth
{
    /** remember me parameter */
    const REMEMBER_ME = 'remember_me';

    /** @var string */
    protected $service;

    /** @var string */
    protected $table;

    /** @var string */
    protected $usernameField;

    /** @var string */
    protected $passwordField;

    /** @var string */
    protected $emailField;

    /** @var array */
    protected $otherFields = [];

    /** @var array */
    protected $filters = [];

    /**
     * AlternateAuth constructor.
     *
     * @param $serviceId
     * @param $table
     * @param $usernameField
     * @param $passwordField
     * @param $emailField
     * @throws \Exception
     */
    public function __construct($serviceId, $table, $usernameField, $passwordField, $emailField)
    {
        $this->setService($serviceId);
        $this->setTable($table);
        $this->setUsernameField($usernameField);
        $this->setPasswordField($passwordField);
        $this->setEmailField($emailField);
    }

    /**
     * Handles login action including creating shadow user if needed
     *
     * @param \DreamFactory\Core\Contracts\ServiceRequestInterface $request
     *
     * @return array
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     * @throws \DreamFactory\Core\Exceptions\RestException
     * @throws \DreamFactory\Core\Exceptions\UnauthorizedException
     * @throws \Exception
     */
    public function handleLogin($request)
    {
        $filterString = $this->generateFilter($request);
        $remoteUser = $this->getRemoteUser($filterString);
        $password = $request->input($this->passwordField);
        $passwordHash = array_get($remoteUser, $this->passwordField);

        if ($this->verifyPassword($password, $passwordHash)) {
            $user = $this->createShadowUser($remoteUser);
            $forever = $request->input(static::REMEMBER_ME);
            $appId = Session::get('app.id');
            Session::setUserInfoWithJWT($user, $forever, $appId);

            return Session::getPublicInfo();
        } else {
            throw new UnauthorizedException('Invalid credential supplied.');
        }
    }

    /**
     * Generates filter string based on request parameter and configured options
     *
     * @param \DreamFactory\Core\Contracts\ServiceRequestInterface $request
     *
     * @return string
     */
    protected function generateFilter($request)
    {
        $this->filters[$this->usernameField] = trim($request->input($this->usernameField));
        foreach ($this->otherFields as $of) {
            $of = trim($of);
            $this->filters[$of] = $request->input($of);
        }

        $string = '';
        $multiple = 0;
        foreach ($this->filters as $f => $v) {
            if (!empty($string)) {
                $string .= " AND ";
                $multiple = 1;
            }
            if (is_bool($v) || 'true' === strtolower($v) || 'false' === strtolower($v)) {
                if ($v === true || $v === 'true') {
                    $v = 1;
                } else {
                    $v = 0;
                }
            }
            $string .= "($f=$v)";
        }

        return ($multiple) ? "(" . $string . ")" : $string;
    }

    /**
     * Retrieves the user from remote source
     *
     * @param $filter
     *
     * @return mixed
     * @throws \DreamFactory\Core\Exceptions\BadRequestException
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     * @throws \DreamFactory\Core\Exceptions\RestException
     * @throws \DreamFactory\Core\Exceptions\UnauthorizedException
     * @throws \Exception
     */
    protected function getRemoteUser($filter)
    {
        $resource = '_table/' . $this->table;
        $response = ServiceManager::handleRequest(
            $this->service,
            Verbs::GET,
            $resource,
            ['filter' => $filter],
            [], null, null, false
        );
        $status = $response->getStatusCode();

        if ($status === 200) {
            $result = ResourcesWrapper::unwrapResources($response->getContent());
            if (!Arr::isAssoc($result)) {
                if (count($result) > 1) {
                    throw new InternalServerErrorException('An unexpected error occurred. More than one user found with your credentials!');
                }
                if (count($result) === 0) {
                    throw new UnauthorizedException('Invalid user information provided.');
                }
                $result = $result[0];
            }

            return $result;
        } else {
            $message = 'DB service responded with code ' . $status;
            if ($status >= 400) {
                $content = $response->getContent();
                $message = array_get($content, 'error.message', $message);
            } else {
                $status = 500;
            }
            throw new RestException($status, $message);
        }
    }

    /**
     * Verifies the password hash
     *
     * @param $password
     * @param $hash
     *
     * @return bool
     */
    protected function verifyPassword($password, $hash)
    {
        // Check plain password.
        if($password === $hash){
            return true;
        }
        // Check md5 hash
        if (md5($password) === $hash) {
            return true;
        }
        // Check bcrypt hash
        return password_verify($password, $hash);
    }

    /**
     * Creates the shadow user if needed
     *
     * @param array $userInfo
     *
     * @return \DreamFactory\Core\Models\BaseModel|\Illuminate\Database\Eloquent\Model|null|static
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     * @throws \Exception
     */
    protected function createShadowUser($userInfo)
    {
        $email = filter_var(array_get($userInfo, $this->emailField), FILTER_SANITIZE_EMAIL);
        if (empty($email)) {
            throw new InternalServerErrorException(
                'Failed to retrieve alternate user\'s email address using field ' . $this->emailField . '.'
            );
        }

        $dfUser = User::whereEmail($email)->first();
        if (empty($dfUser)) {
            $altUser = [
                'email'      => $email,
                'first_name' => 'Alternate',
                'last_name'  => 'User',
                'name'       => 'Alternate User'
            ];
            $dfUser = User::create($altUser);
        }

        return $dfUser;
    }

    /**
     * Sets the db service name
     *
     * @param integer $id
     *
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    public function setService($id)
    {
        $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
        if (empty($id)) {
            throw new InternalServerErrorException('No service id provided.');
        }

        if (empty($this->service = ServiceManager::getServiceNameById($id))) {
            throw new InternalServerErrorException('No alternate db service found with id ' . $id);
        }
    }

    /**
     * Sets the table name
     *
     * @param string $table
     *
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    public function setTable($table)
    {
        $table = trim(filter_var($table, FILTER_SANITIZE_STRING));
        if (empty($table)) {
            throw new InternalServerErrorException('No table name provided.');
        }

        $this->table = $table;
    }

    /**
     * Sets the username field
     *
     * @param string $uf
     *
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    public function setUsernameField($uf)
    {
        $uf = trim(filter_var($uf, FILTER_SANITIZE_STRING));
        if (empty($uf)) {
            throw new InternalServerErrorException('No username field provided.');
        }

        $this->usernameField = $uf;
    }

    /**
     * Sets the password field
     *
     * @param string $pf
     *
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    public function setPasswordField($pf)
    {
        $pf = trim(filter_var($pf, FILTER_SANITIZE_STRING));
        if (empty($pf)) {
            throw new InternalServerErrorException('No password field provided.');
        }

        $this->passwordField = $pf;
    }

    /**
     * Sets the email field
     *
     * @param string $ef
     *
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    public function setEmailField($ef)
    {
        $ef = trim(filter_var($ef, FILTER_SANITIZE_STRING));
        if (empty($ef)) {
            throw new InternalServerErrorException('No email field provided.');
        }

        $this->emailField = $ef;
    }

    /**
     * Sets any additional field(s)
     *
     * @param string $of
     */
    public function setOtherFields($of)
    {
        $of = trim(filter_var($of, FILTER_SANITIZE_STRING));
        if (!empty($of)) {
            $fields = array_filter(explode(',', $of), function ($value){
                return trim(filter_var($value, FILTER_SANITIZE_STRING));
            });

            $this->otherFields = $fields;
        }
    }

    /**
     * Sets any filter(s)
     *
     * @param string|array $filters
     */
    public function setFilters($filters)
    {
        if(is_null($filters)){
            $filters = "";
        }
        if (is_string($filters)) {
            $filters = $this->parseFilters($filters);
        }

        $this->filters = array_merge($this->filters, $filters);
    }

    /**
     * Parses filter string
     *
     * @param string $filters
     *
     * @return array
     */
    protected function parseFilters($filters)
    {
        $parsed = [];
        if (!empty($filters) && is_string($filters)) {
            $filters = trim($filters);
            if (!empty($filters)) {
                $filterArray = array_filter(explode(',', $filters), function ($value){
                    return trim($value);
                });

                foreach ($filterArray as $filter) {
                    list($field, $value) = explode('=', $filter);
                    $field = trim($field);
                    $value = trim($value);
                    if (!empty($field)) {
                        $parsed[$field] = $value;
                    }
                }
            }
        }

        return $parsed;
    }
}