<?php
namespace Beto\Quizwebapp\Models;

use Model;

/**
 * Model
 */
class QuizTemp extends Model
{
    use \October\Rain\Database\Traits\Validation;


    /**
     * @var string table in the database used by the model.
     */
    public $table = 'beto_quizwebapp_quiz_temp';

    protected $fillable = [
        'user_id',
        'data',
        'status',
        'warning',
    ];

    protected $jsonable = [
        'data'
    ];
    public $attributes = [
        'status' => 'pending',
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
