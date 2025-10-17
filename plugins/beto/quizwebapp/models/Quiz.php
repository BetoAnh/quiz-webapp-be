<?php
namespace Beto\Quizwebapp\Models;

use Model;
use RainLab\User\Models\User;

class Quiz extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Sluggable;

    protected $table = 'beto_quizwebapp_quizzes';

    // Chỉ cho phép mass assign đúng field cần
    protected $fillable = ['title', 'description', 'visibility', 'author_id'];

    public $slugs = ['slug' => 'title'];

    public $belongsTo = [
        'author' => [User::class, 'key' => 'author_id'],
        'category' => [Category::class, 'key' => 'category_id'],
        'level' => [Level::class, 'key' => 'level_id'],
    ];

    public $hasMany = [
        'questions' => [Question::class]
    ];

    public $rules = [
        'title' => 'required|string|max:255'
    ];
}
