<?php

use App\Services\Credit\CreditService;
use App\Services\Document\DocumentService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('credits:renew', function (CreditService $credits) {
    $this->info('Renewed credits for '.$credits->renewDue().' user(s).');
})->purpose('Expire unused credits and grant the monthly plan allowance to users who are due');

Artisan::command('documents:purge', function (DocumentService $documents) {
    $this->info('Purged '.$documents->purgeExpired().' expired document(s).');
})->purpose('Delete uploaded documents past their retention period');

Schedule::command('credits:renew')->hourly()->withoutOverlapping();
Schedule::command('documents:purge')->daily();
