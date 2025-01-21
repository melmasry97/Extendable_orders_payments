<?php

namespace App\Repositories;

use App\Interfaces\GeneralInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class GeneralRepository implements GeneralInterface
{
    public function __construct(protected Model $model)
    {
    }

    public function withData(array $with)
    {
        return $this->model->with($with);
    }

    public function getData($with = [])
    {
        return $this->withData($with)->get();
    }

    public function getPaginated($with = [], $number = 15)
    {
        return $this->withData($with)->paginate($number);
    }

    public function getBy($conditions = [], $with = [])
    {
        return $this->model->with($with)->where($conditions)->get();
    }

    public function getMultiColum($colums = [], $conditions = [])
    {
        return $this->model->where($conditions)->get($colums);
    }

    public function find(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $input): Model
    {
        return $this->model->create($input);
    }

    public function update(int $id, array $input): bool
    {
        return $this->model->find($id)->update($input);
    }

    public function destroy(int $id): bool
    {
        return $this->model->find($id)->delete();
    }
}
