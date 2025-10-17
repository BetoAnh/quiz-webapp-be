<?php
namespace Beto\Quizwebapp\Models;

use Model;

class Question extends Model
{
    use \October\Rain\Database\Traits\Validation;

    protected $table = 'beto_quizwebapp_questions';
    protected $fillable = ['quiz_id', 'text', 'options', 'correct_id'];

    public $belongsTo = [
        'quiz' => [Quiz::class]
    ];

    // Tự động encode/decode JSON cho field options
    protected $jsonable = ['options'];

    public $rules = [
        'text' => 'required',
        'options' => 'required|array',
        'correct_id' => 'required|integer'
    ];
}
