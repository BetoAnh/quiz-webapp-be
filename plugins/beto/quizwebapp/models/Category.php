<?php
namespace Beto\Quizwebapp\Models;

use Model;

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

}
