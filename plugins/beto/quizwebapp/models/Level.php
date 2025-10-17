<?php
namespace Beto\Quizwebapp\Models;

use Model;

/**
 * Model
 */
class Level extends Model
{
    use \October\Rain\Database\Traits\Validation;


    /**
     * @var string table in the database used by the model.
     */
    public $table = 'beto_quizwebapp_levels';

    public $belongsTo = [
        'parent' => [Level::class, 'key' => 'parent_id'],
    ];

    public $hasMany = [
        'children' => [Level::class, 'key' => 'parent_id']
    ];
    /**
     * @var array rules for validation.
     */
    public $rules = [
    ];
}
