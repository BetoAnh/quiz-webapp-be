<?php
namespace Beto\Quizwebapp\Models;

use Model;

/**
 * Model
 */
class AIQuota extends Model
{
    use \October\Rain\Database\Traits\Validation;


    /**
     * @var string table in the database used by the model.
     */
    public $table = 'beto_quizwebapp_ai_quota';

    protected $fillable = [
        'user_id',
        'date',
        'used',
        'limit',
    ];

    public $belongsTo = [
        'user' => [\RainLab\User\Models\User::class]
    ];
    /**
     * @var array rules for validation.
     */
    public $rules = [
    ];

}
