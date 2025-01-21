<?php

namespace App\Repositories;

use App\Interfaces\GeneralInterface;
use Illuminate\Database\Eloquent\Model;

class GeneralRepository implements GeneralInterface
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

    public function getSpeseficeColum($colum, $conditions = [])
    {
        return $this->model->where($conditions)->pluck($colum);
    }

    public function getMultiColum($colums = [], $conditions = [])
    {
        return $this->model->where($conditions)->get($colums);
    }

    public function create($input)
    {
        return $this->model->create($input);
    }

    public function update($model, $input): Model
    {
        $model->update($input);
        return $model->fresh();
    }

    public function delete($model)
    {
        return $model->delete();
    }

    public function find(int $id): Model
    {
        return $this->model->findOrFail($id);
    }
}
