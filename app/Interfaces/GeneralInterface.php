<?php

namespace App\Interfaces;


interface GeneralInterface
{

    public function withData(array $with);

    public function getData($with = []);

    public function getPaginated($with = [], $number = 15);

    public function getBy($conditions = [], $with = []);

    public function create($input);

    public function update($model, $input);

    public function delete($model);

}
