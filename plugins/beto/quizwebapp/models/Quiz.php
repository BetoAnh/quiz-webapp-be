<?php
namespace Beto\Quizwebapp\Models;

use Model;
use RainLab\User\Models\User;
use Cache;

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

    public $belongsToMany = [
        'favorited_by' => [
            User::class,
            'table' => 'beto_quizwebapp_quiz_favorites',
            'key' => 'quiz_id',
            'otherKey' => 'user_id',
            'timestamps' => true,
        ],
        'recent_learners' => [
            User::class,
            'table' => 'beto_quizwebapp_user_quiz_recent',
            'key' => 'quiz_id',
            'otherKey' => 'user_id',
            'timestamps' => false,
            'pivot' => ['last_learned_at'],
        ],
    ];

    public $hasMany = [
        'questions' => [Question::class]
    ];

    public $rules = [
        'title' => 'required|string|max:255'
    ];

    public function afterSave()
    {
        $this->flushCategoryCache();
        $this->flushHomeCache();
    }

    public function afterDelete()
    {
        $this->flushCategoryCache();
        $this->flushHomeCache();
    }

    protected $originalCategoryId;

    public function beforeSave()
    {
        $this->originalCategoryId = $this->getOriginal('category_id');
    }

    protected function flushHomeCache()
    {
        Cache::tags(['home_latest'])->flush();
        Cache::tags(['home_featured'])->flush();
    }

    protected function flushCategoryCache()
    {
        if (!empty($this->category_id)) {
            Cache::forget("quiz:category:{$this->category_id}:detail");
        }

        if (
            !empty($this->originalCategoryId)
            && $this->originalCategoryId !== $this->category_id
        ) {
            Cache::forget("quiz:category:{$this->originalCategoryId}:detail");
        }
    }

    public function scopePublicOrOwner($query, $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('visibility', 'public');
            if ($user) {
                $q->orWhere('author_id', $user->id);
            }
        });
    }
}
