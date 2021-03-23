<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Dcat\Admin\Traits\ModelTree;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\EloquentSortable\Sortable;
use Dcat\Admin\Models\MenuCache;

class Menu extends Model implements Sortable
{
    use HasFactory;
    use HasDateTimeFormatter,
        MenuCache,
        ModelTree {
            allNodes as treeAllNodes;
            ModelTree::boot as treeBoot;
        }

    /**
     * @var array
     */
    protected $sortable = [
        'sort_when_creating' => true,
    ];

    protected $fillable = [
        'type', 'name', 'url', 'key', 'parent_id'
    ];

    protected $primaryKey = 'id';

    public function getKeyName()
    {
        return $this->primaryKey;
    }

    public function getParentColumn()
    {
        return 'parent_id';
    }

    public function menus()
    {
        return $this->hasMany(self::class, 'parent_id', 'id');
    }

    /**
     * Get all elements.
     *
     * @param bool $force
     *
     * @return array
     */
    public function allNodes(bool $force = false): array
    {
        if ($force || $this->queryCallbacks) {
            return $this->fetchAll();
        }

        return $this->remember(function () {
            return $this->fetchAll();
        });
    }

    /**
     * Fetch all elements.
     *
     * @return array
     */
    public function fetchAll(): array
    {
        return $this->withQuery()->treeAllNodes();
        /*
        return $this->withQuery(function ($query) {
            if (static::withPermission()) {
                $query = $query->with('permissions');
            }

            return $query->with('roles');
        })->treeAllNodes();
        */
    }

    public function roles(): BelongsToMany
    {
        $pivotTable = config('admin.database.role_menu_table');

        $relatedModel = config('admin.database.roles_model');

        return $this->belongsToMany($relatedModel, $pivotTable, 'menu_id', 'role_id');
    }

    /**
     * Detach models from the relationship.
     *
     * @return void
     */
    protected static function boot()
    {
        static::treeBoot();

        static::deleting(function ($model) {
            $model->roles()->detach();
            $model->permissions()->detach();

            $model->flushCache();
        });

        static::saved(function ($model) {
            $model->flushCache();
        });
    }
}
/*
class Menu2 extends Model implements Sortable
{
    public function roles(): BelongsToMany
    {
        $pivotTable = config('admin.database.role_menu_table');

        $relatedModel = config('admin.database.roles_model');

        return $this->belongsToMany($relatedModel, $pivotTable, 'menu_id', 'role_id');
    }

    public static function withRole()
    {
        return (bool) config('admin.permission.enable');
    }
}
*/
