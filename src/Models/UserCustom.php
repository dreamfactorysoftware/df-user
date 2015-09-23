<?php

namespace DreamFactory\Core\User\Models;

use DreamFactory\Core\Enums\ApiOptions;
use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Exceptions\NotFoundException;
use DreamFactory\Core\Models\BaseCustomModel;
use DreamFactory\Library\Utility\ArrayUtils;
use DreamFactory\Core\Utility\Session as SessionUtility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use DreamFactory\Core\Exceptions\InternalServerErrorException;

/**
 * UserCustom
 *
 * @property integer $id
 * @property integer $user_id
 * @property string  $name
 * @property string  $value
 * @property string  $created_date
 * @property string  $last_modified_date
 * @method static \Illuminate\Database\Query\Builder|UserCustom whereId($value)
 * @method static \Illuminate\Database\Query\Builder|UserCustom whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|UserCustom whereName($value)
 * @method static \Illuminate\Database\Query\Builder|UserCustom whereCreatedDate($value)
 * @method static \Illuminate\Database\Query\Builder|UserCustom whereLastModifiedDate($value)
 */
class UserCustom extends BaseCustomModel
{
    protected $table = 'user_custom';

    protected $fillable = ['user_id', 'name', 'value'];

    protected $hidden = ['id', 'user_id'];

    protected $casts = ['id' => 'integer', 'user_id' => 'integer'];

    /**
     * {@inheritdoc}
     */
    public static function selectById($id, array $related = [], array $fields = ['*'])
    {
        $userId = SessionUtility::getCurrentUserId();
        $fields = static::cleanFields($fields);
        $response = static::whereUserId($userId)->whereName($id)->first();

        if (!empty($response)) {
            $response = $response->toArray();
        } else {
            $response = [];
        }

        return static::cleanResult($response, $fields);
    }

    /**
     * {@inheritdoc}
     */
    public static function selectByIds($ids, array $related = [], array $criteria = [])
    {
        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }
        $userId = SessionUtility::getCurrentUserId();
        $criteria = static::cleanCriteria($criteria);
        $response = static::whereUserId($userId)->whereIn('name', $ids)->get()->toArray();

        return static::cleanResult($response, ArrayUtils::get($criteria, 'select'));
    }

    /**
     * {@inheritdoc}
     */
    public static function selectByRequest(array $criteria = [], array $related = [])
    {
        $userId = SessionUtility::getCurrentUserId();
        $criteria = static::cleanCriteria($criteria);
        $response = static::whereUserId($userId)->get()->toArray();

        return static::cleanResult($response, ArrayUtils::get($criteria, 'select'));
    }

    /**
     * @param       $record
     * @param array $params
     *
     * @return array
     * @throws \DreamFactory\Core\Exceptions\BadRequestException
     * @throws \Exception
     */
    protected static function createInternal($record, $params = [])
    {
        $userId = SessionUtility::getCurrentUserId();
        $name = ArrayUtils::get($record, 'name');
        $modelExists = static::whereName($name)->whereUserId($userId)->first();
        if (!empty($modelExists)) {
            return $modelExists->updateInternal($modelExists->name, $record, $params);
        } else {
            ArrayUtils::set($record, 'user_id', $userId);
            return parent::createInternal($record, $params);
        }
    }

    /**
     * @param       $id
     * @param       $record
     * @param array $params
     *
     * @return array
     * @throws \DreamFactory\Core\Exceptions\BadRequestException
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     * @throws \DreamFactory\Core\Exceptions\NotFoundException
     */
    public static function updateInternal($id, $record, $params = [])
    {
        if (empty($record)) {
            throw new BadRequestException('There are no fields in the record to create . ');
        }

        if (empty($id)) {
            //Todo:perform logging below
            //Log::error( 'Update request with no id supplied: ' . print_r( $record, true ) );
            throw new BadRequestException('Identifying field "id" can not be empty for update request . ');
        }

        $userId = SessionUtility::getCurrentUserId();
        ArrayUtils::set($record, 'user_id', $userId);
        //Making sure name is not changed during update as it not be unique.
        ArrayUtils::set($record, 'name', $id);
        $model = static::whereUserId($userId)->whereName($id)->first();

        if (!$model instanceof Model) {
            throw new NotFoundException('No resource found for ' . $id);
        }

        $pk = $model->primaryKey;
        //	Remove the PK from the record since this is an update
        ArrayUtils::remove($record, $pk);

        try {
            $model->update($record);

            return static::buildResult($model, $params);
        } catch (\Exception $ex) {
            throw new InternalServerErrorException('Failed to update resource: ' . $ex->getMessage());
        }
    }

    /**
     * @param       $id
     * @param       $record
     * @param array $params
     *
     * @return array
     * @throws \DreamFactory\Core\Exceptions\BadRequestException
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     * @throws \DreamFactory\Core\Exceptions\NotFoundException
     */
    public static function deleteInternal($id, $record, $params = [])
    {
        if (empty($record)) {
            throw new BadRequestException('There are no fields in the record to create . ');
        }

        if (empty($id)) {
            //Todo:perform logging below
            //Log::error( 'Update request with no id supplied: ' . print_r( $record, true ) );
            throw new BadRequestException('Identifying field "id" can not be empty for update request . ');
        }

        $userId = SessionUtility::getCurrentUserId();
        $model = static::whereUserId($userId)->whereName($id)->first();

        if (!$model instanceof Model) {
            throw new NotFoundException('No resource found for ' . $id);
        }

        try {
            $result = static::buildResult($model, $params);
            $model->delete();

            return $result;
        } catch (\Exception $ex) {
            throw new InternalServerErrorException('Failed to delete resource: ' . $ex->getMessage());
        }
    }

    /**
     * @param \DreamFactory\Core\Models\BaseModel $model
     * @param array                               $params
     *
     * @return array
     */
    public static function buildResult($model, $params = [])
    {
        $pk = 'name';
        $id = $model->{$pk};
        $fields = ArrayUtils::get($params, ApiOptions::FIELDS, $pk);
        $related = ArrayUtils::get($params, ApiOptions::RELATED);

        if ($pk === $fields && empty($related)) {
            return [$pk => $id];
        }

        $fieldsArray = explode(',', $fields);
        $relatedArray = (!empty($related)) ? explode(',', $related) : [];

        $result = static::selectById($id, $relatedArray, $fieldsArray);

        return $result;
    }
}