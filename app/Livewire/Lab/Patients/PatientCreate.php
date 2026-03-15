<?php

namespace App\Livewire\Lab\Patients;

use App\Models\Patient;
use Carbon\Carbon;
use Livewire\Component;

class PatientCreate extends Component
{
    public string $name        = '';
    public string $cnic        = '';
    public string $phone       = '';
    public string $email       = '';
    public string $gender      = 'male';
    public string $dob         = '';
    public string $age         = '';
    public string $age_unit    = 'years';
    public string $address     = '';
    public string $referred_by = '';

    public function updatedDob(): void
    {
        if (! $this->dob) {
            return;
        }

        $birth = Carbon::parse($this->dob);
        $years = $birth->diffInYears(now());

        if ($years >= 1) {
            $this->age      = (string) $years;
            $this->age_unit = 'years';
        } elseif (($months = $birth->diffInMonths(now())) >= 1) {
            $this->age      = (string) $months;
            $this->age_unit = 'months';
        } else {
            $this->age      = (string) $birth->diffInDays(now());
            $this->age_unit = 'days';
        }
    }

    public function save(): void
    {
        $this->validate([
            'name'        => 'required|string|max:255',
            'cnic'        => ['nullable', 'regex:/^\d{5}-\d{7}-\d{1}$/'],
            'phone'       => 'nullable|string|max:20',
            'email'       => 'nullable|email',
            'gender'      => 'required|in:male,female,other',
            'dob'         => 'nullable|date|before_or_equal:today',
            'age'         => 'nullable|integer|min:0|max:150',
            'age_unit'    => 'required|in:years,months,days',
            'address'     => 'nullable|string',
            'referred_by' => 'nullable|string|max:255',
        ]);

        $patient = Patient::create([
            'name'        => $this->name,
            'cnic'        => $this->cnic,
            'phone'       => $this->phone,
            'email'       => $this->email,
            'gender'      => $this->gender,
            'dob'         => $this->dob ?: null,
            'age'         => $this->age ?: null,
            'age_unit'    => $this->age_unit,
            'address'     => $this->address,
            'referred_by' => $this->referred_by,
        ]);

        session()->flash('success', "Patient {$patient->name} registered successfully.");
        $this->redirect(route('lab.patients.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.lab.patients.create')
            ->layout('layouts.lab', ['title' => 'Register Patient']);
    }
}
