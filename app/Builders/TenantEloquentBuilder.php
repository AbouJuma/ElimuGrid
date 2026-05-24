<?php

namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\LazyCollection;

class TenantEloquentBuilder extends Builder
{
    /**
     * Check if we should bypass this query.
     */
    protected function shouldBypass(): bool
    {
        // Get the model's table name
        $table = $this->model->getTable();
        
        // If the table is 'users', we do not bypass since it exists globally
        if ($table === 'users' || str_ends_with($table, '_users')) {
            return false;
        }

        // Resolve school ID
        $schoolId = null;
        if (method_exists($this->model, 'resolveSchoolIdForTenancy')) {
            $schoolId = $this->model::resolveSchoolIdForTenancy();
        }

        return $schoolId === null;
    }

    public function get($columns = ['*'])
    {
        if ($this->shouldBypass()) {
            return $this->model->newCollection();
        }
        return parent::get($columns);
    }

    public function count($columns = '*')
    {
        if ($this->shouldBypass()) {
            return 0;
        }
        return parent::count($columns);
    }

    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        if ($this->shouldBypass()) {
            return new LengthAwarePaginator([], 0, $perPage ?: 15);
        }
        return parent::paginate($perPage, $columns, $pageName, $page);
    }

    public function simplePaginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        if ($this->shouldBypass()) {
            return new Paginator([], $perPage ?: 15);
        }
        return parent::simplePaginate($perPage, $columns, $pageName, $page);
    }

    public function cursorPaginate($perPage = null, $columns = ['*'], $cursorName = 'cursor', $cursor = null)
    {
        if ($this->shouldBypass()) {
            return new CursorPaginator([], $perPage ?: 15);
        }
        return parent::cursorPaginate($perPage, $columns, $cursorName, $cursor);
    }

    public function pluck($column, $key = null)
    {
        if ($this->shouldBypass()) {
            return new Collection();
        }
        return parent::pluck($column, $key);
    }

    public function exists()
    {
        if ($this->shouldBypass()) {
            return false;
        }
        return parent::exists();
    }

    public function aggregate($function, $columns = ['*'])
    {
        if ($this->shouldBypass()) {
            return 0;
        }
        return parent::aggregate($function, $columns);
    }

    public function insert(array $values)
    {
        if ($this->shouldBypass()) {
            return false;
        }
        return parent::insert($values);
    }

    public function update(array $values)
    {
        if ($this->shouldBypass()) {
            return 0;
        }
        return parent::update($values);
    }

    public function delete()
    {
        if ($this->shouldBypass()) {
            return 0;
        }
        return parent::delete();
    }

    public function cursor()
    {
        if ($this->shouldBypass()) {
            return new LazyCollection();
        }
        return parent::cursor();
    }
}
