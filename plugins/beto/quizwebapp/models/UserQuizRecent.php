<?php
namespace Beto\Quizwebapp\Models;

use Model;
use RainLab\User\Models\User;
use Beto\Quizwebapp\Models\Quiz;


/**
 * Model
 */
class UserQuizRecent extends Model
{
    use \October\Rain\Database\Traits\Validation;


    /**
     * @var string table in the database used by the model.
     */
    public $table = 'beto_quizwebapp_user_quiz_recent';

    protected $fillable = [
        'user_id',
        'quiz_id',
        'last_learned_at',
    ];
    protected $dates = [
        'last_learned_at',
        'created_at',
        'updated_at',
    ];

    public $timestamps = true;

    public $belongsTo = [
        'user' => [User::class],
        'quiz' => [Quiz::class],
    ];
    /**
     * @var array rules for validation.
     */
    public $rules = [
    ];

}
