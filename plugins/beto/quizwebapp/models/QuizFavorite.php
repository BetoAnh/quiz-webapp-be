<?php
namespace Beto\Quizwebapp\Models;

use Model;
use Beto\Quizwebapp\Models\Quiz;
use RainLab\User\Models\User;

/**
 * Model
 */
class QuizFavorite extends Model
{
    use \October\Rain\Database\Traits\Validation;


    /**
     * @var string table in the database used by the model.
     */
    public $table = 'beto_quizwebapp_quiz_favorites';

    protected $fillable = [
        'user_id',
        'quiz_id',
    ];

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
