<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Model;

interface GeneralInterface
{
    public function withData(array $with);
    public function getPaginated($with = [], $number = 15);
    public function getBy($conditions = [], $with = []);
    public function create(array $input): Model;
    public function update(int $id, array $input): bool;
    public function destroy(int $id): bool;

}
