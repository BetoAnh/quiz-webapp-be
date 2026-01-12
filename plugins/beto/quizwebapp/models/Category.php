<?php
namespace Beto\Quizwebapp\Models;

use Model;
use Cache;

/**
 * Model
 */
class Category extends Model
{
    use \October\Rain\Database\Traits\Validation;


    /**
     * @var string table in the database used by the model.
     */
    public $table = 'beto_quizwebapp_categories';
    public $belongsTo = [
        'parent' => [Category::class, 'key' => 'parent_id'],
    ];


    /**
     * @var array rules for validation.
     */
    public $rules = [
    ];

    public function afterSave()
    {
        Cache::forget('quiz:categories:tree');
        Cache::forever('quiz:version', microtime(true));
    }

    public function afterDelete()
    {
        Cache::forget('quiz:categories:tree');
        Cache::forever('quiz:version', microtime(true));
    }

}
