<?php

use Illuminate\Support\Facades\Route;
use App\Imports\SchoolsImport;
use Maatwebsite\Excel\Facades\Excel;

Route::get('/', function () {
    return 'Wajah SMK Backend aktif.';
});

Route::get('/import-schools', function () {
    Excel::import(
        new SchoolsImport,
        storage_path('app/imports/data-smk.xlsx')
    );

    return 'Import selesai.';
});
