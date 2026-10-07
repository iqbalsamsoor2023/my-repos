<?php

namespace App\Enums\UserHealth;

enum BloodType: string
{
    case OMINUS = 'O-';
    case OPLUS = 'O+';
    case AMINUS = 'A-';
    case APLUS = 'A+';
    case BPMINUS = 'B-';
    case BPPLUS = 'B+';
    case ABPLUS = 'AB+';
    case ABMINUS = 'AB-';
}
