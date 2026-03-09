<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Labs\LabCreate;
use App\Livewire\Admin\Labs\LabEdit;
use App\Livewire\Admin\Labs\LabIndex;
use App\Livewire\Lab\Dashboard as LabDashboard;
use App\Livewire\Lab\Invoices\InvoiceIndex;
use App\Livewire\Lab\Orders\OrderCreate;
use App\Livewire\Lab\Orders\OrderIndex;
use App\Livewire\Lab\Orders\OrderShow;
use App\Livewire\Lab\Patients\PatientCreate;
use App\Livewire\Lab\Patients\PatientEdit;
use App\Livewire\Lab\Patients\PatientIndex;
use App\Livewire\Lab\Results\ReleaseIndex as ResultReleaseIndex;
use App\Livewire\Lab\Results\ResultIndex;
use App\Livewire\Lab\Samples\CollectionIndex as SampleCollectionIndex;
use App\Livewire\Lab\Samples\ReceiveIndex as SampleReceiveIndex;
use App\Livewire\Lab\Samples\RejectedIndex as SampleRejectedIndex;
use App\Livewire\Lab\Settings\LabSettings;
use App\Livewire\Lab\Tests\TestCategoryIndex;
use App\Livewire\Lab\Tests\TestIndex;
use App\Livewire\Lab\Users\UserIndex;
use App\Livewire\Lab\Worklists\WorklistIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    if (auth()->user()?->hasRole('superadmin')) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('lab.dashboard');
})->middleware('auth')->name('dashboard');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:superadmin'])->group(function () {
    Route::get('/dashboard', AdminDashboard::class)->name('dashboard');
    Route::get('/labs', LabIndex::class)->name('labs.index');
    Route::get('/labs/create', LabCreate::class)->name('labs.create');
    Route::get('/labs/{lab}/edit', LabEdit::class)->name('labs.edit');
});

Route::prefix('lab')->name('lab.')->middleware(['auth', 'role:lab_admin|lab_incharge|receptionist|technician'])->group(function () {
    Route::get('/dashboard', LabDashboard::class)->name('dashboard');

    Route::get('/patients', PatientIndex::class)->name('patients.index');
    Route::get('/patients/create', PatientCreate::class)->name('patients.create');
    Route::get('/patients/{patient}/edit', PatientEdit::class)->name('patients.edit');

    Route::get('/test-categories', TestCategoryIndex::class)->name('test-categories.index');
    Route::get('/tests', TestIndex::class)->name('tests.index');

    Route::get('/orders', OrderIndex::class)->name('orders.index');
    Route::get('/orders/create', OrderCreate::class)->name('orders.create');
    Route::get('/orders/{order}', OrderShow::class)->name('orders.show');
    Route::get('/orders/{order}/report', [ReportController::class, 'orderReport'])->name('orders.report');

    Route::get('/samples', SampleCollectionIndex::class)->name('samples.collection');
    Route::get('/samples/receive', SampleReceiveIndex::class)->name('samples.receive');
    Route::get('/samples/rejected', SampleRejectedIndex::class)->name('samples.rejected');

    Route::get('/worklists', WorklistIndex::class)->name('worklists.index');

    Route::get('/results', ResultIndex::class)->name('results.index');
    Route::get('/results/release', ResultReleaseIndex::class)->name('results.release');

    Route::get('/invoices', InvoiceIndex::class)->name('invoices.index');

    Route::get('/settings', LabSettings::class)->name('settings')->middleware('role:lab_admin');
    Route::get('/users', UserIndex::class)->name('users.index')->middleware('role:lab_admin');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';